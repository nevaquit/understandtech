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
| Staging Playwright tests pass | ✅ **Done** (core) | **8/8 pass** chromium project (`--workers=1`, 2026-06-06): auth setup + AI tutor **4/4** + course nav **3/3**. Optional `auth.spec.ts` (chromium-auth) still flaky when run after session tests — not a §7.1 blocker. |
| Smoke test passes (no failures) | ⚠️ **Partial** | Critical paths pass from workstation: SSL, HTTP 200, AI health/auth. Git Bash DNS check may fail on Windows (`dig`/`nslookup` PATH). `TEST_VIDEO_URL` not set — Stream check skipped. Full run: `ORIGIN_IP=52.252.59.54 PROD_URL=https://understandtech.app ./scripts/smoke-test-deployment.sh` |
| Azure Key Vault secrets populated | ⚠️ **Partial** | Core seven OK (2026-06-06). **Stripe:** `stripe-secret-key`, `stripe-publishable-key`, `stripe-webhook-secret` — ❌ **absent** from vault (2026-06-06 re-audit). **Postmark:** `postmark-server-token` — ❌ absent. Helper: `.\scripts\stripe-kv-setup-interactive.ps1` ([stripe-integration.md](stripe-integration.md)). |
| Production DNS → Cloudflare | ✅ **Done** | `understandtech.app` → Cloudflare anycast. |
| Cloudflare DNS records proxied | ✅ **Done** | `Server: cloudflare`, `CF-RAY` on responses. |
| Authenticated Origin Pulls enabled | ✅ **Done** | Nginx `ssl_client_certificate` present on VM. Direct `--resolve understandtech.app:443:52.252.59.54` from workstation: **TLS handshake fails** (curl 000/35) — origin not serving anonymous HTTPS; traffic must go through Cloudflare. |
| Cloudflare Stream test video | ❌ **User action** | KV signing key ✅ (len 57); no upload/lesson embed yet. See [stream-upload-checklist.md](stream-upload-checklist.md) and [v1-release-integrations.md](v1-release-integrations.md). |
| Stripe webhooks → production | ⚠️ **Partial** | `paygw_stripe` **1.31** (2026020800) on VM; webhook `POST` → **400** (not 404). No `STRIPE_*` in `/etc/moodle/env` until KV populated. Payment account + KV secrets: user action. See [stripe-integration.md](stripe-integration.md). |
| Postmark sender verified | ❌ **User action** | KV `postmark-server-token` absent; Moodle `smtphosts` empty on VM. See [v1-release-integrations.md](v1-release-integrations.md). |
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
ABS  stripe-secret-key
ABS  stripe-publishable-key
ABS  stripe-webhook-secret
ABS  postmark-server-token
```

Populate script dry-run: all four LLM/Stream/worker secrets already configured — `./scripts/populate-keyvault-secrets.sh` skips each.

Stripe/Postmark: run `.\scripts\stripe-kv-setup-interactive.ps1` when understandtech Stripe test keys exist; then `./scripts/configure-stripe-remote.sh` and `./scripts/setup-postmark-smtp-remote.sh`.

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

**Result:** **8 passed** (setup + AI tutor 4/4 + course navigation 3/3).

Optional auth-only project (runs after chromium; may flake on logout/session):

```bash
npx playwright test --project=chromium-auth --workers=1
```

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

1. **Stream:** Follow [stream-upload-checklist.md](stream-upload-checklist.md) — upload in dashboard, `generate-stream-signed-url.sh` → `TEST_VIDEO_URL`, re-run smoke.
2. **Stripe:** `paygw_stripe` installed on VM — run `.\scripts\stripe-kv-setup-interactive.ps1`, then `./scripts/configure-stripe-remote.sh`, then Moodle payment account ([stripe-integration.md](stripe-integration.md)).
3. **Postmark:** Verify sender; `az keyvault secret set --name postmark-server-token …`; run `./scripts/setup-postmark-smtp-remote.sh`.
4. **Sudoers:** From machine with `az login`: `./scripts/sync-sudoers-remote.sh` (or `sync-sudoers-vm.sh` on VM after `git pull`).
5. **Tag `v1.0.0`** when all §7.1 rows are ✅.

## Related docs

- [post-deployment-validation.md](post-deployment-validation.md) — 30-minute checklist
- [stripe-integration.md](stripe-integration.md) — Stripe install, Key Vault, webhooks, test cards
- [v1-release-integrations.md](v1-release-integrations.md) — Stream / Stripe / Postmark
- [playbook.md §7](playbook.md#phase-7-production-deployment-and-validation)
- [scripts/README.md](../scripts/README.md) — Key Vault, Redis, E2E user, smoke script
