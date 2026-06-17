---
name: iac-async-cloud-devops
description: >-
  Azure Bicep modular IaC, Nginx 1.26+ with PHP-FPM unix sockets and login rate limits,
  PgBouncer transaction pooling for PHP-FPM, and zero-inbound GitHub Actions self-hosted
  runner CI/CD for understandtech.app. Use when authoring or reviewing Bicep, cloud-init,
  origin web server configs, PgBouncer, NSG/firewall posture, or Phase 2/5 infrastructure.
---

# Infrastructure-as-Code (IaC) & Asynchronous Cloud DevOps

## Domain scope (verbatim)

Infrastructure-as-Code (IaC) & Asynchronous Cloud DevOpsThe application relies on keeping origin surface areas invisible to public threat vectors while managing elastic cloud dependencies.  Azure Bicep / IaC Scripting: Building decoupled cloud environments modularly (networking, compute tiers, data persistence) into predictable Bicep script layers.  High-Concurrence Web Configurations: Advanced mastery of Nginx (1.26+) configurations paired with PHP-FPM process mapping. Configuring dedicated UNIX domain sockets and adjusting rate-limit thresholds for sensitive endpoints like login modules.  Database Transaction Pooling: Deploying and configuring PgBouncer. Crucial knowledge of multiplexing hundreds of short-lived PHP-FPM application worker threads safely across highly restricted cloud connection pools without causing connection exhaustion.  Zero-Inbound CI/CD Architecture: Configuring self-hosted runner pipelines (like a GitHub Actions runner running under isolated systemd profiles) utilizing solely outbound HTTPS connections. This ensures your production servers maintain zero inbound open firewall ports from the general internet.

---

## Context: understandtech.app

**Stack (non-negotiable):** Azure Bicep, Ubuntu VM (Nginx 1.26 + PHP-FPM 8.3), PgBouncer, Azure PostgreSQL Flexible Server, Azure Managed Redis, Cloudflare edge (origin invisible to public). CI/CD via GitHub Actions self-hosted runner — **outbound-only**, no inbound ports for GitHub.

**Hard constraints (`.cursorrules`):**
- Secrets **never** in source — Azure Key Vault references only
- Core Moodle **never** committed — only `moodle-plugins/`, `infrastructure/`, `cloudflare-worker/`
- Origin reachable only via Cloudflare (NSG + Authenticated Origin Pulls)

**Repo entry points:**

| Layer | Path |
|-------|------|
| Bicep root | `infrastructure/bicep/main.bicep` |
| Modules | `infrastructure/bicep/modules/{network,data,vm,monitoring}.bicep` |
| Parameters | `infrastructure/bicep/parameters.prod.bicepparam` |
| VM bootstrap | `infrastructure/runner/cloud-init.yaml` |
| Nginx | `infrastructure/nginx/understandtech.conf`, `understandtech-rate-limit.conf` |
| PHP-FPM | `infrastructure/php-fpm/moodle.conf` |
| PgBouncer | `infrastructure/pgbouncer/pgbouncer.ini`, `userlist.txt` |
| Runner sudo | `infrastructure/runner/gha-runner-sudoers` |
| Scripts | `scripts/render-cloud-init.ps1`, `scripts/populate-keyvault-secrets.*` |
| Docs | `docs/playbook.md` (Phase 2, Phase 5), `docs/white-paper.md` (Improvement #4) |

---

## 1. Azure Bicep — modular IaC layers

### Architecture

Deploy at **subscription scope** (`targetScope = 'subscription'`). One resource group per environment; modules are decoupled layers:

```
main.bicep
├── modules/network.bicep    → VNet, subnets, NSG, Postgres private DNS
├── modules/data.bicep       → Postgres 16, Redis Enterprise, Storage, Key Vault
├── modules/monitoring.bicep → Log Analytics, Application Insights
└── modules/vm.bicep         → Ubuntu VM, public IP, managed identity, cloud-init
```

### NSG posture (zero public origin)

Inbound rules only:
1. **Admin SSH** — single `/32` from `adminIpAddress` param (priority 100)
2. **Cloudflare HTTPS** — per-CIDR allow on port 443 (priorities 200+)
3. **Deny-All-Inbound** — priority 4096

No GitHub IP ranges. Runner uses **outbound HTTPS** to poll jobs.

### Validate and deploy

```bash
az bicep build --file infrastructure/bicep/main.bicep
az deployment sub validate \
  --location eastus2 \
  --template-file infrastructure/bicep/main.bicep \
  --parameters infrastructure/bicep/parameters.prod.bicepparam

az deployment sub create \
  --name understandtech-prod-$(date +%Y%m%d) \
  --location eastus2 \
  --template-file infrastructure/bicep/main.bicep \
  --parameters infrastructure/bicep/parameters.prod.bicepparam
```

### Rules

- **PascalCase** resource names, **camelCase** parameters (repo convention)
- Parameter files use `using 'main.bicep'` — never embed secrets; use Key Vault post-deploy
- Pin VM image (Ubuntu 22.04 jammy if 24.04 unavailable in region)
- Override `vmSize` when capacity blocks (e.g. `Standard_D2s_v3`)
- Post-deploy: populate Key Vault, render cloud-init, deploy origin certs

### Common pitfalls

| Issue | Fix |
|-------|-----|
| SKU capacity / quota | Try alternate size or zone; check `az vm list-skus` |
| Postgres on burstable + heavy pooling | Use Flexible Server tier with adequate `max_connections` |
| Legacy Azure Cache for Redis | Use `Microsoft.Cache/redisEnterprise` (see `data.bicep`) |
| Stale cloud-init on VM | Re-render with `scripts/render-cloud-init.ps1` and update customData |

---

## 2. Nginx + PHP-FPM — high-concurrency async web

### Unix domain socket pairing

Nginx upstream and PHP-FPM pool **must** share the same socket path:

```nginx
upstream moodle_php {
    server unix:/run/php/moodle.sock;
}
```

```ini
; infrastructure/php-fpm/moodle.conf
listen = /run/php/moodle.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
```

Use `fastcgi_pass moodle_php;` — never TCP loopback for local origin (lower latency, no port exposure).

### PHP-FPM process mapping

| Setting | Production value | Rationale |
|---------|------------------|-----------|
| `pm` | `dynamic` | Scale workers with load |
| `pm.max_children` | `50` | Cap concurrent PHP workers |
| `pm.max_requests` | `500` | Mitigate memory leaks |
| `request_terminate_timeout` | `300` | Backups/imports |

Size `pm.max_children` against PgBouncer `default_pool_size` — many PHP workers multiplex onto fewer DB backends.

### Login rate limiting

Define zone in **http {}** context (`understandtech-rate-limit.conf`):

```nginx
limit_req_zone $binary_remote_addr zone=moodle_login:10m rate=5r/m;
```

Apply on sensitive endpoint:

```nginx
location = /login/index.php {
    limit_req zone=moodle_login burst=2 nodelay;
    fastcgi_pass moodle_php;
}
```

Adjust `rate=` and `burst=` together; test with `curl` from multiple IPs.

### Origin hardening

- Cloudflare Origin Certificate at `/etc/ssl/cloudflare/`
- **Authenticated Origin Pulls** — `ssl_verify_client on`
- Deny `config.php`, `install.php`, `admin/cli/`, dotfiles
- `client_max_body_size 200M` aligned with PHP upload limits

### Deploy paths

| Repo file | VM path |
|-----------|---------|
| `infrastructure/nginx/understandtech.conf` | `/etc/nginx/sites-available/understandtech.conf` |
| `infrastructure/nginx/understandtech-rate-limit.conf` | `/etc/nginx/conf.d/understandtech-rate-limit.conf` |
| `infrastructure/php-fpm/moodle.conf` | `/etc/php/8.3/fpm/pool.d/moodle.conf` |

Reload nginx only: `nginx -t && systemctl reload nginx`
PHP-FPM: **always** `systemctl restart php8.3-fpm` (never reload — stale workers cause DB errors)

---

## 3. PgBouncer — transaction pooling for PHP-FPM

### Why transaction mode

PHP-FPM workers are **short-lived connections**. Transaction pooling multiplexes hundreds of client sessions onto a small Azure Postgres backend pool without exhausting `max_connections`.

```ini
pool_mode = transaction
max_client_conn = 500
default_pool_size = 25
reserve_pool_size = 5
listen_addr = 127.0.0.1
listen_port = 6432
server_tls_sslmode = require
```

Moodle connects to `127.0.0.1:6432`, not Postgres directly.

### Auth and TLS

- `auth_type = scram-sha-256` with `userlist.txt` (generate hash via `scripts/generate-pgbouncer-hash.sh`)
- Azure Postgres requires TLS — **`server_tls_sslmode = require`** is mandatory
- Local auth may use plain userlist for `pgbouncer` admin stats only

### Pool sizing math

```
effective_backend_connections ≈ default_pool_size (+ reserve_pool_size under spike)
php_workers (pm.max_children) >> default_pool_size  ← intentional multiplexing
```

Ensure Azure Postgres `max_connections` > `default_pool_size` + admin overhead + direct connections.

### Upgrade / DDL exceptions

**Moodle `upgrade.php` and some DDL fail under transaction pooling** (prepared statements, temp tables, cursors). Options:

1. Temporarily switch to **session** mode (global **and** per-database `[databases]` line — both must match)
2. Run upgrade against Postgres directly: `scripts/moodle-upgrade-direct-pg.sh`
3. Clear stale lock: `scripts/moodle-clear-lock-sql.sh` if `upgraderunning` stuck
4. Sync version hash: `scripts/moodle-sync-version-hash.sh`

**Always restore transaction mode** after upgrades for normal PHP-FPM traffic.

Reload without drop: `pgbouncer -R -d /etc/pgbouncer/pgbouncer.ini`

---

## 4. Zero-inbound CI/CD — self-hosted runner

### Principle

The production VM maintains **zero inbound firewall holes for GitHub**. The runner polls `github.com` over outbound HTTPS (443). NSG allows only Cloudflare → origin HTTPS and admin SSH.

### Bootstrap (`infrastructure/runner/cloud-init.yaml`)

1. Create `gha-runner` system user (`/opt/actions-runner`)
2. Download runner release; register with short-lived token
3. Install as **systemd service**: `/opt/actions-runner/svc.sh install gha-runner`
4. Clone plugin monorepo to `/opt/understandtech-plugins`
5. Enable `nginx`, `php8.3-fpm`, `pgbouncer`, `actions.runner.*`

Render secrets before deploy:

```powershell
.\scripts\render-cloud-init.ps1
```

Substitutions: `{{POSTGRES_FQDN}}`, `{{REGISTRATION_TOKEN}}`, `{{STORAGE_ACCOUNT_NAME}}`, `{{SMB_PASSWORD}}`, `{{REPO_SSH_URL}}`

### Runner registration (token expires quickly)

```bash
gh api repos/nevaquit/understandtech/actions/runners/registration-token --jq .token

sudo -u gha-runner /opt/actions-runner/config.sh \
  --url https://github.com/nevaquit/understandtech \
  --token "$TOKEN" \
  --labels self-hosted,linux,production \
  --name understandtech-web-prod

sudo /opt/actions-runner/svc.sh install gha-runner
sudo /opt/actions-runner/svc.sh start
```

### Sudo allowlist

Deploy `infrastructure/runner/gha-runner-sudoers` → `/etc/sudoers.d/gha-runner` (mode 0440).

Allowed: Moodle CLI (`maintenance.php`, `upgrade.php`, `purge_caches.php`), targeted `chown` for plugin dirs, `systemctl reload nginx`, `systemctl restart php8.3-fpm` (never reload php-fpm), `systemctl reload pgbouncer`.

**Every workflow sudo command must match the allowlist exactly** — no broad `NOPASSWD: ALL`.

### Phase 5 pipeline pattern (`docs/playbook.md`)

| Stage | Runner | Purpose |
|-------|--------|---------|
| validate | `ubuntu-latest` (GitHub-hosted) | PHP lint, Bicep build, plugin checks |
| deploy | `[self-hosted, linux, production]` | rsync plugins, `upgrade.php`, purge OPcache/Redis |

Deploy workflow syncs `/opt/understandtech-plugins` → `/var/www/moodle/` plugin paths only — never overwrites Moodle core on VM.

### Verification

```bash
gh api repos/nevaquit/understandtech/actions/runners --jq '.runners[] | {name, status, busy}'
systemctl status actions.runner.*
ss -tlnp | grep -E ':443|:22'   # no unexpected listeners
```

---

## 5. Operational checklists

### New environment deploy

- [ ] Update `parameters.prod.bicepparam` (admin IP, SSH key)
- [ ] `az bicep build` + `az deployment sub validate`
- [ ] Deploy subscription stack
- [ ] Populate Key Vault secrets (`scripts/populate-keyvault-secrets.ps1`)
- [ ] Render and apply cloud-init / bootstrap
- [ ] Install Cloudflare origin certs + enable Authenticated Origin Pulls
- [ ] Configure PgBouncer userlist + SCRAM hash
- [ ] Register self-hosted runner
- [ ] Verify NSG: Cloudflare 443 + admin SSH only

### Config change (nginx / php-fpm / pgbouncer)

- [ ] Edit repo canonical copy under `infrastructure/`
- [ ] Copy to VM paths; `nginx -t` before reload
- [ ] For PgBouncer: prefer `pgbouncer -R` over full restart
- [ ] Smoke-test login rate limit and Moodle DB connectivity

### Incident: connection exhaustion

1. Check Azure Postgres active connections vs `max_connections`
2. Verify PgBouncer running on `127.0.0.1:6432` in **transaction** mode
3. Confirm Moodle `dbhost` points to PgBouncer, not Postgres FQDN
4. Review `pm.max_children` vs `default_pool_size` mismatch

---

## 6. Cross-skill references

- **Edge / AI offload:** `edge-serverless-orchestration` — Cloudflare Worker, Stream JWTs
- **Moodle plugins / CLI:** `moodle-core-php-engineering` — upgrade.php, plugin structure
- **AI Gateway:** `ai-intelligent-systems` — tutor routes, no direct LLM from PHP
- **Enterprise LMS + AI:** `lms-workflow`, `lms-enterprise-ai-master-skill` — SCORM/xAPI/LTI, multi-tenant, grading queues
