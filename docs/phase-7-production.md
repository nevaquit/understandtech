# Phase 7 — Production Deployment and Validation

Per `docs/playbook.md` §7.1–7.4. **Do not tag `v1.0.0` until all pre-deploy gates below are green.**

## Status summary

| Area | State |
|------|--------|
| Production URL live | ✅ `https://understandtech.app` (HTTP 200, `cf-ray` present) |
| AI Worker health | ✅ `https://ai.understandtech.app/health` → `{"status":"ok"}` |
| Self-hosted runner | ✅ `understandtech-web-prod` online |
| CI/CD | ✅ `deploy.yml` validate + deploy green on `main` |
| Post-deploy checklist | ✅ `docs/post-deployment-validation.md` |
| v1.0.0 tag + formal release deploy | ⏸ **Blocked** — gates below |

## Pre-deploy gates (§7.1)

| Gate | Status | Evidence / notes |
|------|--------|------------------|
| Staging Playwright tests pass | ⚠️ **Partial** | **5 pass / 6 fail / 0 skip** (chromium, `--workers=1`, 2026-06-06). Auth login + session persist ✅; course navigation 3/3 ✅; AI tutor 0/4 ❌ (sidebar HTML absent on course — plugin deployed but hook output not in page; purge/deploy follow-up). Invalid-login + logout flaky under load. Credentials: user `e2etest` on VM; `tests/e2e/.env` populated locally (gitignored). |
| Smoke test passes (no failures) | ⚠️ **Partial** | Critical paths pass from workstation: SSL, HTTP 200, AI health/auth, Moodle login (after `fetchbuffersize` + PgBouncer restart). Git Bash DNS check may fail on Windows (`dig`/`nslookup` PATH). Run from VM or PowerShell for full DNS. |
| Azure Key Vault secrets populated | ✅ **Done** | All 7 secrets OK (length only, 2026-06-06): `moodle-db-password`, `moodle-app-password`, `redis-password`, `anthropic-api-key`, `openai-api-key`, `cf-stream-signing-key`, `cf-worker-shared-secret` — none `REPLACE-ME`. |
| Production DNS → Cloudflare | ✅ **Done** | `understandtech.app` → Cloudflare anycast. |
| Cloudflare DNS records proxied | ✅ **Done** | `Server: cloudflare`, `CF-RAY` on responses. |
| Authenticated Origin Pulls enabled | ✅ **Done** | Nginx `ssl_client_certificate` present on VM. Direct `--resolve understandtech.app:443:52.252.59.54` from workstation: **TLS handshake fails** (curl 000/35) — origin not serving anonymous HTTPS; traffic must go through Cloudflare. |
| Cloudflare Stream test video | ❌ **User action** | KV signing key ✅; no upload/lesson embed yet. See [v1-release-integrations.md](v1-release-integrations.md). |
| Stripe webhooks → production | ❌ **Blocked** | Plugins not in repo; no webhook. See [v1-release-integrations.md](v1-release-integrations.md). |
| Postmark sender verified | ❌ **User action** | Moodle `smtphosts` empty on VM. See [v1-release-integrations.md](v1-release-integrations.md). |
| Self-hosted runner idle/online | ✅ **Done** | `{"name":"understandtech-web-prod","status":"online","busy":false}` |
| Rollback plan documented | ✅ **Done** | Playbook §7.4 + checklist rollback section |
| Redis sessions wired | ✅ **Done** | `\core\session\redis` in live `config.php`; Azure Redis `PONG` (TLS); `session_redis_encrypt` SSL context array; **`fetchbuffersize` 100000** for PgBouncer transaction mode. Restart PgBouncer after config changes. |
| `CF_AIG_AUTHORIZATION` Worker secret | ⚠️ **Optional** | Worker health/tutor 401 OK without it today. |

## Key Vault audit (2026-06-06)

```
OK   moodle-db-password (len=32)
OK   moodle-app-password (len=32)
OK   redis-password (len=44)
OK   anthropic-api-key (len=108)
OK   openai-api-key (len=164)
OK   cf-stream-signing-key (len=57)
OK   cf-worker-shared-secret (len=44)
```

Populate script dry-run: all four LLM/Stream/worker secrets already configured — `./scripts/populate-keyvault-secrets.sh` skips each.

## Smoke / Origin Pulls (2026-06-06)

From workstation (PowerShell + curl):

| Check | Result |
|-------|--------|
| HTTPS via Cloudflare | 200, `cf-ray` present |
| AI `/health` | `{"status":"ok"}` |
| AI `/tutor` no JWT | 401 |
| Direct origin `52.252.59.54:443` | TLS handshake failure (blocked) |
| `TEST_VIDEO_URL` | Not set — skipped |

Full script: `ORIGIN_IP=52.252.59.54 PROD_URL=https://understandtech.app ./scripts/smoke-test-deployment.sh` (use Git Bash/WSL or run individual curl checks on Windows).

## Playwright (2026-06-06)

```bash
cd tests/e2e
cp .env.example .env   # set STAGING_TEST_USER_EMAIL=e2etest, password, E2E_COURSE_PATH=/course/view.php?id=2
npx playwright install chromium
npx playwright test --project=chromium --workers=1
```

**Result:** 5 passed, 6 failed (AI tutor sidebar + flaky auth logout/invalid-login).

Production test user (created via `scripts/setup-e2e-test-user-vm.sh` on VM):

- Username: `e2etest`
- Email: `e2e-test@understandtech.app`
- Course: `E2E Test Course` (`/course/view.php?id=2`)

## Deployment sequence (when gates are green)

Do **not** run until all §7.1 gates are ✅.

```bash
git tag -a v1.0.0 -m "Initial production release"
git push origin v1.0.0
gh workflow run deploy.yml --ref v1.0.0
gh run watch
PROD_URL=https://understandtech.app GITHUB_REPO=nevaquit/understandtech \
  ./scripts/smoke-test-deployment.sh
```

Then execute every row in [post-deployment-validation.md](post-deployment-validation.md).

## Recommended next steps

1. **AI tutor E2E:** Sync latest `local_aitutor` from monorepo to VM (`deploy-plugins-vm.sh` / CI deploy), purge caches, confirm `#local-aitutor-sidebar` on course 2; re-run tutor specs.
2. **Stream:** Upload test video; set `TEST_VIDEO_URL`; re-run smoke.
3. **Stripe / Postmark:** Follow [v1-release-integrations.md](v1-release-integrations.md).
4. **Playwright:** Store `STAGING_TEST_USER_PASSWORD` in engineer `.env` only; optional GitHub Actions secrets for CI.
5. **Tag `v1.0.0`** when all §7.1 rows are ✅.

## Related docs

- [post-deployment-validation.md](post-deployment-validation.md) — 30-minute checklist
- [v1-release-integrations.md](v1-release-integrations.md) — Stream / Stripe / Postmark
- [playbook.md §7](playbook.md#phase-7-production-deployment-and-validation)
- [scripts/README.md](../scripts/README.md) — Key Vault, Redis, E2E user, smoke script
