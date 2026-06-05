# Post-Deployment Validation Checklist

**Purpose:** 30-minute engineer checklist to run immediately after a production deploy (playbook §7.3).  
**Target:** `https://understandtech.app` · AI Worker: `https://ai.understandtech.app`  
**Repo:** [nevaquit/understandtech](https://github.com/nevaquit/understandtech)

Print this page or keep it in a side window. Fill the **Pass/Fail** column as you go. Each check should take under 60 seconds.

---

## STOP — call CTO if any of these fail

| Critical check | Why |
|----------------|-----|
| SSL certificate valid (≥30 days) | Users cannot trust the site |
| Moodle login works for test student | Platform unusable |
| AI tutor **refuses** assessment bypass (Socratic, no answers) | Pedagogical guarantee broken |
| AI Worker `/health` returns `{"status":"ok"}` | All tutor traffic blocked |
| Authenticated Origin Pulls blocks direct origin access | WAF/rate limits bypassed |
| Stripe webhook endpoint responds (when billing enabled) | Revenue/subscription breakage |

If any STOP item fails, execute rollback per [playbook §7.4](playbook.md#74-rollback-plan) before attempting a forward fix.

---

## Quick smoke (5 minutes)

Run the automated script first. It mirrors CI post-deploy checks in `.github/workflows/deploy.yml`.

```bash
export PROD_URL=https://understandtech.app
export GITHUB_REPO=nevaquit/understandtech
# Optional — verify Origin Pulls (VM public IP from Azure portal or setup-moodle-env-vm.ps1):
# export ORIGIN_IP=52.252.59.54
# Optional — Stream signed URL from a lesson page:
# export TEST_VIDEO_URL='https://customer-<id>.cloudflarestream.com/<jwt>/manifest/video.m3u8'

./scripts/smoke-test-deployment.sh
```

**Expected:** zero `[FAIL]` lines; `[WARN]` is acceptable for skipped optional checks (ORIGIN_IP, TEST_VIDEO_URL, off-VM checks).

On the production VM (or deploy runner), re-run with VM checks enabled:

```bash
ON_VM=1 GITHUB_REPO=nevaquit/understandtech PROD_URL=https://understandtech.app \
  bash /opt/understandtech-plugins/scripts/smoke-test-deployment.sh
```

| Check name | Expected result | Command or URL | Pass/Fail |
|------------|-----------------|----------------|-----------|
| Smoke script summary | `0 failures` | `./scripts/smoke-test-deployment.sh` | |
| Deploy workflow smoke (CI) | Job **Post-deploy smoke test** green | `gh run list --workflow=deploy.yml --limit 1` | |

---

## 1. Edge layer (Cloudflare)

| Check name | Expected result | Command or URL | Pass/Fail |
|------------|-----------------|----------------|-----------|
| DNS `understandtech.app` | Cloudflare anycast IP (not empty) | `dig +short understandtech.app A` or `nslookup understandtech.app` | |
| DNS `www.understandtech.app` | Resolves; redirects or serves site | `curl -sI https://www.understandtech.app/ \| grep -E 'HTTP\|location'` | |
| SSL / HTTPS head | HTTP/2 or HTTP/1.1 **200** with `cf-ray` header | `curl -sI https://understandtech.app/ \| grep -E 'HTTP\|cf-ray\|server'` | |
| SSL expiry ≥30 days | OpenSSL reports ≥30 days remaining | `echo \| openssl s_client -servername understandtech.app -connect understandtech.app:443 2>/dev/null \| openssl x509 -noout -enddate` | |
| HTTP via Cloudflare | Status **200**, **302**, or **303** | `curl -s -o /dev/null -w '%{http_code}' https://understandtech.app/` | |
| Authenticated Origin Pulls | Direct origin **without** CF client cert rejected (400/403/495) | `ORIGIN_IP=<vm-ip> curl -sk -o /dev/null -w '%{http_code}' --resolve 'understandtech.app:443:'"$ORIGIN_IP" https://understandtech.app/` | |
| WAF managed rules | Ruleset active on zone | Cloudflare dashboard → **Security → WAF** → Managed rules **On** | |
| Login rate limit (nginx) | `/login/index.php` limited (5 req/min zone) | Cloudflare **Analytics** or VM: `grep limit_req /etc/nginx/sites-enabled/understandtech.conf` | |
| Cloudflare Analytics | Requests visible in last 15 min | Dashboard → **Analytics & Logs → Traffic** | |

---

## 2. Origin layer (Azure VM)

SSH: `ssh azureadmin@52.252.59.54` (update IP from Azure portal if changed).

| Check name | Expected result | Command or URL | Pass/Fail |
|------------|-----------------|----------------|-----------|
| Nginx running | `active (running)` | `sudo systemctl status nginx --no-pager` | |
| PHP-FPM 8.3 running | `active (running)` | `sudo systemctl status php8.3-fpm --no-pager` | |
| PgBouncer running | `active (running)` | `sudo systemctl status pgbouncer --no-pager` | |
| PHP-FPM socket | Socket exists | `test -S /run/php/moodle.sock && echo ok` | |
| Self-hosted runner online | ≥1 runner `status: online` | `gh api repos/nevaquit/understandtech/actions/runners --jq '.runners[] \| {name,status,busy}'` | |
| Moodle not in maintenance | Maintenance **disabled** | `sudo -u www-data php /var/www/moodle/admin/cli/maintenance.php --get-config` | |
| Disk usage `/var/www` | **<70%** (fail at ≥80%) | `df -h /var/www \| awk 'NR==2{print $5}'` | |
| Memory usage | **<80%** | `free \| awk '/Mem:/ {printf "%.0f%%\n", $3/$2*100}'` | |
| Monorepo sync | HEAD matches deployed commit | `cd /opt/understandtech-plugins && git rev-parse HEAD` | |

---

## 3. Data layer (Azure PaaS)

Requires `az login` and Key Vault access (`utkvnhhwegpz3rem6`).

| Check name | Expected result | Command or URL | Pass/Fail |
|------------|-----------------|----------------|-----------|
| Key Vault — no REPLACE-ME | All secrets non-empty, not `REPLACE-ME` | See **Key Vault block** below | |
| PostgreSQL healthy | State **Ready** | `az postgres flexible-server show -g understandtech-prod-rg -n understandtech-pg-prod --query state -o tsv` | |
| Last PG backup | Backup within SLA | Azure portal → **PostgreSQL → Backup** or `az postgres flexible-server show ...` | |
| Redis reachable from VM | `PONG` on port 6379 (local tunnel) or Azure Redis ping | `redis-cli -h 127.0.0.1 -p 6379 -a "$(sudo cat /etc/moodle/redis_password)" PING` | |
| Azure Files moodledata mounted | Mount present | `mount \| grep moodledata` or `df -h /var/www/moodledata` | |
| Moodle DB via PgBouncer | `dbhost` readable | `sudo -u www-data php /var/www/moodle/admin/cli/cfg.php --name=dbhost` | |

### Key Vault secret check (run from engineer workstation)

```bash
KEY_VAULT=utkvnhhwegpz3rem6
for s in moodle-db-password moodle-app-password redis-password \
         anthropic-api-key openai-api-key cf-stream-signing-key cf-worker-shared-secret; do
  v=$(az keyvault secret show --vault-name "$KEY_VAULT" --name "$s" --query value -o tsv)
  if [ "$v" = "REPLACE-ME" ] || [ -z "$v" ]; then echo "FAIL $s"; else echo "OK   $s"; fi
done
```

Populate missing LLM/Stream/worker secrets: `./scripts/populate-keyvault-secrets.sh` then `pwsh ./scripts/setup-moodle-env-vm.ps1`.

---

## 4. AI layer (Cloudflare Worker)

| Check name | Expected result | Command or URL | Pass/Fail |
|------------|-----------------|----------------|-----------|
| Worker deployed | Latest deployment listed | `cd cloudflare-worker/ai-gateway && npx wrangler deployments list` | |
| Worker health | **200** body `{"status":"ok"}` | `curl -s https://ai.understandtech.app/health` | |
| Worker auth gate | **401** without JWT | `curl -s -o /dev/null -w '%{http_code}' -X POST https://ai.understandtech.app/tutor -H 'Content-Type: application/json' -d '{"messages":[{"role":"user","content":"ping"}]}'` | |
| End-to-end tutor (manual) | Streamed Socratic reply in browser | Log in as test student → open course with AI sidebar → ask "explain Kerberos" | |
| AI Gateway dashboard | Request logged after tutor test | Cloudflare → **AI → AI Gateway → understandtech** | |
| KV prompt cache | Entry after repeated identical prompt | `npx wrangler kv key list --namespace-id=a43be5f6f8ee4433b71127e03f0d6551` | |
| CF_AIG_AUTHORIZATION (if gateway auth on) | Secret set when required | `npx wrangler secret list` (look for `CF_AIG_AUTHORIZATION`) | |

Deploy or redeploy Worker: `./scripts/deploy-ai-gateway.sh`

---

## 5. Application layer (Moodle)

Requires test student credentials in `tests/e2e/.env` (`STAGING_TEST_USER_EMAIL`, `STAGING_TEST_USER_PASSWORD`).

| Check name | Expected result | Command or URL | Pass/Fail |
|------------|-----------------|----------------|-----------|
| Playwright auth suite | All auth tests pass | `cd tests/e2e && npm test -- auth.spec.ts` | |
| Playwright course nav | Dashboard renders | `cd tests/e2e && npm test -- course-navigation.spec.ts` | |
| Playwright AI tutor | Sidebar + streaming + **refusal** test pass | `cd tests/e2e && npm test -- ai-tutor.spec.ts` | |
| Login (manual) | Redirect to dashboard | `https://understandtech.app/login/index.php` | |
| Course dashboard | Blocks/theme render | Navigate to enrolled course | |
| Lesson video (Stream JWT) | Video plays; no raw Stream ID in page source | Set `TEST_VIDEO_URL` in smoke script or inspect lesson page | |
| Quiz + confidence rating | qbehaviour_certmasterconfidence works | Complete one quiz question with confidence slider | |
| AI tutor Socratic response | No direct assessment answers | Ask tutor about a quiz question — must refuse | |
| Lab flag + XP | mod_ctfflag awards XP on correct flag | Submit test flag in lab activity | |
| Stripe checkout (when enabled) | Checkout loads; test card `4242…` works | Click Subscribe → Stripe test mode | |

Full E2E: `cd tests/e2e && npm test` — see [tests/e2e/README.md](../tests/e2e/README.md).

---

## 6. Observability

| Check name | Expected result | Command or URL | Pass/Fail |
|------------|-----------------|----------------|-----------|
| Application Insights traces | Recent requests after smoke | Azure portal → **Application Insights → Transaction search** | |
| Cloudflare Analytics | Edge requests in last hour | Cloudflare → **Analytics & Logs** | |
| AI audit log (Moodle) | Rows from tutor webhook | DB or admin report on `mdl_local_aitutor_*` after tutor test | |
| Nginx / PHP logs | No error spike | `sudo journalctl -u nginx -u php8.3-fpm --since '15 min ago' \| tail -50` | |
| Deploy workflow summary | Validate + Deploy green | `gh run watch` or Actions tab | |

---

## Rollback trigger

If a STOP item fails:

```bash
PREV_TAG=$(git describe --tags --abbrev=0 HEAD^)
echo "Rolling back to $PREV_TAG"
gh workflow run deploy.yml --ref "$PREV_TAG"
gh run watch
PROD_URL=https://understandtech.app ./scripts/smoke-test-deployment.sh
```

See [playbook §7.4](playbook.md#74-rollback-plan) for decision rules.

---

## Reference paths

| Resource | Path / value |
|----------|----------------|
| Smoke script | `scripts/smoke-test-deployment.sh` |
| Deploy workflow | `.github/workflows/deploy.yml` |
| Key Vault (prod) | `utkvnhhwegpz3rem6` |
| Resource group | `understandtech-prod-rg` |
| Moodle on VM | `/var/www/moodle` |
| Monorepo on VM | `/opt/understandtech-plugins` |
| Worker config | `cloudflare-worker/ai-gateway/wrangler.jsonc` |
| Nginx config | `infrastructure/nginx/understandtech.conf` |

**Baseline captured:** 2026-06-06 — see [phase-7-production.md](phase-7-production.md).
