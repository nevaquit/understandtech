# Moodle Safe Deploy

Prevents production breakage when pushing `moodle-plugins/` changes to understandtech.app.

## Why deploys broke the site

| Failure | Cause | Permanent fix |
|---------|-------|---------------|
| `Call to undefined method moodle_page::add_body_attributes()` | API from Moodle >4.5 used on 4.5.12 | `validate-moodle-plugin-api.php` blocklist |
| Empty course index / skeleton nav | `Y.NodeList` crash in `core/templates` + AMD-only prerender | `templates_dom_patch.js` (all layouts) + **server prerender in `layout/drawers.php`** + AMD fallbacks |
| Site-wide DB errors after deploy | Stale PHP-FPM workers after `reload` | **Always `systemctl restart php8.3-fpm`** via `restart-php-fpm-vm.sh` — **reload is forbidden** |
| DB errors after deploy | www-data cannot chdir into `/var/www/moodle` | `fix-moodle-dir-permissions-vm.sh` + `fix-moodle-chdir-quick-vm.sh` |
| Dashboard Timeline skeleton loaders | AMD race after cache purge | `timeline_fallback.js` + `myoverview_fallback.js` + health retries on `/my/` |
| Course 3 permission error | Users not enrolled after seed/DB recovery | `enroll-sec701-default-users.php` in every deploy/recover |
| Theme drift after partial recovery | Theme not re-synced from monorepo | `sync-theme-understandtech-vm.sh` in stabilize path |
| ~35 min broken window | 5-min cron only | 3-min cron + post-deploy `workflow_run` trigger |
| Rollback left site worse | Maintenance disabled before health passed | Rollback → `ensure-origin-healthy-vm.sh` → health gate |
| Manual deploy bypasses stabilize | Scripts rsync plugins without enrol/theme/FPM recycle | `post-deploy-stabilize-vm.sh` wired into all bypass scripts |

## CI gates (run before deploy)

```bash
php scripts/validate-moodle-plugin-api.php   # Moodle 4.5 API + AMD build files
php -l moodle-plugins/**/*.php               # syntax (deploy.yml validate job)
```

**Rules when editing theme/plugins:**

1. Never call `$page` / `$PAGE` methods not in `scripts/moodle-45-page-api-allowlist.txt`
2. Never add methods in `scripts/moodle-45-page-api-blocklist.txt`
3. Every `amd/src/*.js` needs a matching `amd/build/*.min.js`
4. On course/incourse pages: use `js_amd_inline`, not `js_call_amd` in `lib.php` / hooks
5. Bump `version.php` on every plugin change

## PHP-FPM rule (CRITICAL — forbidden operation)

**Never use `systemctl reload php8.3-fpm` anywhere in deploy, recovery, or operator docs.**

`reload` keeps old workers alive; they retain stale DB/bootstrap state and cause
`Error reading from database` site-wide after plugin sync or config changes.

**Always use:**

```bash
sudo bash /opt/understandtech-plugins/scripts/restart-php-fpm-vm.sh
# or: sudo systemctl restart php8.3-fpm
```

**Enforcement:**

- `infrastructure/runner/gha-runner-sudoers` — allows `restart` only; **no reload line for php8.3-fpm**
- All stabilize/recover scripts call `restart-php-fpm-vm.sh` or `systemctl restart`
- Nginx/PgBouncer may still use `reload` (safe — not PHP worker state)

Scripts that enforce restart: `restart-php-fpm-vm.sh`, `fix-moodle-chdir-quick-vm.sh`,
`fix-moodle-dir-permissions-vm.sh`, `apply-php-fpm-pool-vm.sh`, `moodle-upgrade-direct-pg.sh`,
`post-deploy-stabilize-vm.sh`.

## Course index permanence (three layers)

1. **Server HTML** — `layout/drawers.php` embeds `course_index_prerender` sections into drawer HTML before response (works without JS).
2. **DOM patch** — `templates_dom_patch.js` loads first on **every pagelayout** via `page_init` (fixes core/templates Y.NodeList crash).
3. **AMD fallbacks** — `courseindex_prerender.js`, `courseindex_fallback.js`, `timeline_fallback.js`, `myoverview_fallback.js`.

Health gate (`verify-moodle-web-health.sh`) requires `courseindex-section` count ≥ 1, rejects skeleton-only state, and runs CLI modinfo check when on VM.

## Staging-first deploy rule (CRITICAL)

**Never promote plugin changes to production without passing staging deploy + Playwright E2E.**

Pipeline order (`deploy.yml`):

1. **validate** — API lint, Bicep, changed-plugin detection (GitHub-hosted)
2. **deploy-staging** — full sync on `[self-hosted, linux, staging]`; health against `STAGING_URL`
3. **staging-e2e** — Playwright chromium on staging (blocks prod)
4. **deploy production** — only after steps 2–3 succeed

Emergency bypass: `workflow_dispatch` with `skip_staging_gate=true` (manual only).

Staging VM bootstrap: `parameters.staging.bicepparam` → Cloudflare `staging` A record →
`RUNNER_NAME=understandtech-web-staging RUNNER_LABELS=self-hosted,linux,staging … bootstrap-gha-runner-vm.sh` →
`gh workflow run seed-sec701.yml -f target=staging`.

## Deploy pipeline (`.github/workflows/deploy.yml`)

Per-environment steps (staging then production):

1. **Record pre-deploy SHA** on VM (`/tmp/understandtech-pre-deploy-sha`)
2. **Enable maintenance** during sync
3. Rsync plugins → purge caches → **`restart` PHP-FPM** → upgrade
4. Fix permissions → chdir verify → **`restart` PHP-FPM** again
5. **`post-deploy-stabilize-vm.sh`** — SEC701 enrolment, theme sync, permissions
6. **`verify-moodle-web-health.sh`** — strict, with retries (`PROD_URL` or `STAGING_URL`)
7. **`smoke-test-deployment.sh`**
8. **Disable maintenance** only if steps 6–7 pass
9. **On failure:** rollback → `ensure-origin-healthy-vm.sh` → maintenance stays ON if health fails
10. **Origin health check** auto-runs via `workflow_run` after **production** deploy completes

## Bypass scripts (must call post-deploy-stabilize)

These paths do **not** run full `deploy.yml`; each ends with `post-deploy-stabilize-vm.sh`:

- `deploy-local-aitutor-vm.sh`
- `deploy-plugins-vm.sh`
- `deploy-lesson-layout-vm.sh`
- `stabilize-origin-vm.sh`
- `rollback-deploy-vm.sh`

## Recovery scripts (idempotent)

| Script | When |
|--------|------|
| `ensure-origin-healthy-vm.sh` | Full recovery: DB, PHP-FPM, enrol, theme, strict health |
| `post-deploy-stabilize-vm.sh` | After deploy, seed, rollback, or partial fix |
| `restart-php-fpm-vm.sh` | Safe PHP-FPM recycle (only supported path) |
| `sync-theme-understandtech-vm.sh` | Theme drift / partial recovery |
| `enroll-sec701-default-users.php` | After seed, DB recovery, or deploy |
| `rollback-deploy-vm.sh` | Plugin rollback — then ensure-origin-healthy |
| `verify-moodle-web-health.sh` | Deploy gate, origin-health, recover, seed |

## Monitoring (`.github/workflows/origin-health.yml`)

- Cron every **3 minutes**
- Auto-triggers after **Deploy Moodle Plugins** completes
- On failure: runs `ensure-origin-healthy-vm.sh` (DB + PHP-FPM + enrol + theme + health)
- Maintenance disabled **only on success**

## What is permanent vs monitored/recoverable

| Guaranteed permanent | Monitored / auto-recovered |
|---------------------|---------------------------|
| Server-rendered course index in drawer HTML | Real Azure Postgres outage (not FPM-related) |
| PHP-FPM restart-only policy (sudoers + scripts) | Up to ~3 min blind window before origin-health cron |
| templates_dom_patch on all layouts | JS fully disabled in browser (server nav still works in drawer) |
| Strict health gate before maintenance off | Edge/Cloudflare issues outside origin scripts |

## Operator checklist before pushing plugin changes

```
- [ ] php scripts/validate-moodle-plugin-api.php
- [ ] AMD build/*.min.js updated for every amd/src change
- [ ] version.php bumped for every changed plugin
- [ ] No new $PAGE->method() without allowlist entry
- [ ] PR validate job green; merge to main
- [ ] Wait for Deploy workflow: staging deploy → Playwright E2E → prod deploy
- [ ] Confirm Origin health check passes within 3 min (auto after prod deploy)
- [ ] Spot-check staging then prod: login, /my/ timeline, course SEC701 (id=3), course index drawer
```

## Operator checklist after manual VM intervention

```
- [ ] sudo bash /opt/understandtech-plugins/scripts/ensure-origin-healthy-vm.sh
- [ ] Do NOT disable maintenance until verify-moodle-web-health.sh exits 0
- [ ] Never reload PHP-FPM — always: sudo bash .../restart-php-fpm-vm.sh
- [ ] Re-run enroll-sec701-default-users.php if users report course access errors
```

## Manual recovery

```bash
# Full idempotent recovery (preferred)
sudo bash /opt/understandtech-plugins/scripts/ensure-origin-healthy-vm.sh

# Plugin-only rollback (then run ensure-origin-healthy)
sudo bash /opt/understandtech-plugins/scripts/rollback-deploy-vm.sh
sudo bash /opt/understandtech-plugins/scripts/ensure-origin-healthy-vm.sh

# PHP-FPM recycle only (never reload)
sudo bash /opt/understandtech-plugins/scripts/restart-php-fpm-vm.sh

# Or via GitHub Actions: Recover origin DB / Origin health check (workflow_dispatch)
```
