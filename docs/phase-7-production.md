# Phase 7 — Production Deployment and Validation

Per `docs/playbook.md` §7.1–7.4.

## Status summary

| Area | State |
|------|--------|
| Production URL live | ✅ `https://understandtech.app` (HTTP 200, `cf-ray` present) |
| AI Worker health | ✅ `https://ai.understandtech.app/health` → `{"status":"ok"}` |
| Self-hosted runner | ✅ `understandtech-web-prod` online |
| CI/CD | ✅ `deploy.yml` validate + deploy green on `main` |
| Post-deploy checklist | ✅ `docs/post-deployment-validation.md` |
| v1.0.0 tag + formal release deploy | ⏸ **Ready for core platform** — billing/email deferred (see below) |

## Deferred by user (2026-06-06)

Stripe Key Vault populate, Postmark server token, and Moodle payment account setup are **intentionally deferred** until understandtech Stripe/Postmark accounts exist. These are **not blockers** for tagging `v1.0.0` on the core learning platform.

| Integration | Gate impact | When to resume |
|-------------|-------------|----------------|
| Stripe (`stripe-*` KV, payment account) | Billing/checkout | `.\scripts\stripe-kv-setup-interactive.ps1` → `configure-stripe-remote.sh` |
| Postmark (`postmark-server-token`) | Transactional email | Verify sender → KV → `setup-postmark-smtp-remote.sh` |
| `payment-flow.spec.ts` | E2E billing | `STRIPE_TEST=1` + `E2E_PAID_COURSE_PATH` after Stripe live |

## v1.0.0 scope — required vs optional

### Required for `v1.0.0` tag (core platform)

| Gate | Status |
|------|--------|
| Staging Playwright core suite | ✅ 8/8 chromium (`--workers=1`) |
| Smoke test critical paths | ✅ SSL, HTTP 200, AI health/auth from workstation |
| Key Vault — core seven secrets | ✅ LLM + Stream signing + worker shared secret |
| DNS / Cloudflare proxy / Origin Pulls | ✅ |
| Redis sessions wired | ✅ |
| Self-hosted runner online | ✅ |
| Rollback plan documented | ✅ |
| Stream signing PHP helper | ✅ `local_certmaster\stream_helper` (RS256, 60s) |
| Stream test video + lesson embed | ❌ **User action** — upload in dashboard; see [stream-upload-checklist.md](stream-upload-checklist.md) |

### Optional / post-v1 (deferred integrations)

| Gate | Status | Notes |
|------|--------|-------|
| Stripe webhooks + checkout | ⏸ **Deferred** | `paygw_stripe` on VM; KV secrets absent |
| Postmark sender + SMTP | ⏸ **Deferred** | `smtphosts` empty on VM |
| `CF_AIG_AUTHORIZATION` Worker secret | ⚠️ **Optional** | Only if AI Gateway authenticated access is enabled |
| `auth.spec.ts` (chromium-auth) | ⚠️ Flaky | Not a §7.1 blocker |
| Full smoke `TEST_VIDEO_URL` | ⏸ After Stream upload | `generate-stream-signed-url.sh` |

## Pre-deploy gates (§7.1)

| Gate | Status | Evidence / notes |
|------|--------|------------------|
| Staging Playwright tests pass | ✅ **Done** (core) | **8/8 pass** chromium project (`--workers=1`, 2026-06-06): auth setup + AI tutor **4/4** + course nav **3/3**. `payment-flow.spec.ts` excluded unless `STRIPE_TEST=1`. |
| Smoke test passes (no failures) | ⚠️ **Partial** | Critical paths pass from workstation: SSL, HTTP 200, AI health/auth. Git Bash DNS check may fail on Windows (`dig`/`nslookup` PATH). `TEST_VIDEO_URL` not set — Stream check skipped. Full run: `ORIGIN_IP=52.252.59.54 PROD_URL=https://understandtech.app ./scripts/smoke-test-deployment.sh` |
| Azure Key Vault secrets populated | ✅ **Core** / ⏸ **Billing+email deferred** | Core seven OK (2026-06-06). **Stripe:** `stripe-secret-key`, `stripe-publishable-key`, `stripe-webhook-secret` — ⏸ deferred. **Postmark:** `postmark-server-token` — ⏸ deferred. |
| Production DNS → Cloudflare | ✅ **Done** | `understandtech.app` → Cloudflare anycast. |
| Cloudflare DNS records proxied | ✅ **Done** | `Server: cloudflare`, `CF-RAY` on responses. |
| Authenticated Origin Pulls enabled | ✅ **Done** | Nginx `ssl_client_certificate` present on VM. Direct `--resolve understandtech.app:443:52.252.59.54` from workstation: **TLS handshake fails** (curl 000/35) — origin not serving anonymous HTTPS; traffic must go through Cloudflare. |
| Cloudflare Stream test video | ⚠️ **Partial** | KV signing key ✅; PHP `stream_helper` ✅ in monorepo; upload + lesson embed still user action. See [stream-upload-checklist.md](stream-upload-checklist.md). |
| Stripe webhooks → production | ⏸ **Deferred** | `paygw_stripe` **1.31** on VM; webhook `POST` → **400** (not 404). No `STRIPE_*` in `/etc/moodle/env` until user populates KV. |
| Postmark sender verified | ⏸ **Deferred** | KV `postmark-server-token` absent; Moodle `smtphosts` empty on VM. |
| Self-hosted runner idle/online | ✅ **Done** | `{"name":"understandtech-web-prod","status":"online","busy":false}` |
| Rollback plan documented | ✅ **Done** | Playbook §7.4 + checklist rollback section |
| Redis sessions wired | ✅ **Done** | `\core\session\redis` in live `config.php`; Azure Redis `PONG` (TLS); `session_redis_encrypt` SSL context array; **`fetchbuffersize` 100000** for PgBouncer transaction mode. |
| `CF_AIG_AUTHORIZATION` Worker secret | ⚠️ **Optional** | Worker health/tutor 401 OK without it. Set only when Cloudflare AI Gateway **Authenticated Gateway** is enabled — see below. |

## Key Vault audit (2026-06-06)

```
OK   moodle-db-password (len=32)
OK   moodle-app-password (len=32)
OK   redis-password (len=44)
OK   anthropic-api-key (len=108)
OK   openai-api-key (len=164)
OK   cf-stream-signing-key (len=57)
OK   cf-worker-shared-secret (len=44)
DEF  stripe-secret-key          (deferred by user)
DEF  stripe-publishable-key     (deferred by user)
DEF  stripe-webhook-secret      (deferred by user)
DEF  postmark-server-token      (deferred by user)
```

Populate script dry-run: all four LLM/Stream/worker secrets already configured — `./scripts/populate-keyvault-secrets.sh` skips each.

When ready for billing/email: `.\scripts\stripe-kv-setup-interactive.ps1` → `./scripts/configure-stripe-remote.sh` and `./scripts/setup-postmark-smtp-remote.sh`.

## CF_AIG_AUTHORIZATION (optional)

Only required if **Cloudflare AI Gateway → Authenticated Gateway** is turned on for the `understandtech` gateway.

```bash
cd cloudflare-worker/ai-gateway
# Token from Cloudflare dashboard → AI → AI Gateway → understandtech → Authentication
npx wrangler secret put CF_AIG_AUTHORIZATION
npx wrangler secret list   # confirm present
```

Worker code sends `cf-aig-authorization: Bearer <token>` when the secret is set (`cloudflare-worker/ai-gateway/src/llm/aig.ts`). Current production: health and tutor work **without** this secret.

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

**Result:** **8 passed** (setup + AI tutor 4/4 + course navigation 3/3). `payment-flow.spec.ts` skipped (not in chromium project).

Optional auth-only project (runs after chromium; may flake on logout/session):

```bash
npx playwright test --project=chromium-auth --workers=1
```

Production test user (created via `scripts/setup-e2e-test-user-vm.sh` on VM):

- Username: `e2etest`
- Email: `e2e-test@understandtech.app`
- Course: `E2E Test Course` (`/course/view.php?id=2`)

## Deployment sequence (core v1.0.0)

When core gates above are ✅ (Stream upload optional but recommended before first lesson):

```bash
git tag -a v1.0.0 -m "Initial production release — core platform"
git push origin v1.0.0
gh workflow run deploy.yml --ref v1.0.0
gh run watch
PROD_URL=https://understandtech.app GITHUB_REPO=nevaquit/understandtech \
  ./scripts/smoke-test-deployment.sh
```

Then execute every row in [post-deployment-validation.md](post-deployment-validation.md) (skip deferred-integration rows until keys exist).

## Recommended next steps (no Stripe/Postmark keys required)

1. **Stream upload:** [stream-upload-checklist.md](stream-upload-checklist.md) — dashboard upload → `generate-stream-signed-url.sh` → `TEST_VIDEO_URL` smoke → Moodle Page embed via `stream_helper::sign_manifest_url()`.
2. **Moodle Stream settings:** Site administration → Plugins → Local plugins → CertMaster → set **kid** and **customer subdomain**; run `.\scripts\setup-moodle-env-vm.ps1` to deploy signing PEM to `/etc/moodle/cf-stream-signing-key.pem`.
3. **Nginx sync:** `./scripts/apply-nginx-config-remote.sh` (javascript.php PHP-FPM routing).
4. **Sudoers:** `./scripts/sync-sudoers-remote.sh`.
5. **Tag `v1.0.0`** when core gates are green.
6. **Later (keys):** Stripe KV → payment account; Postmark KV → SMTP.

## Related docs

- [post-deployment-validation.md](post-deployment-validation.md) — 30-minute checklist
- [stripe-integration.md](stripe-integration.md) — Stripe install, Key Vault, webhooks, test cards
- [v1-release-integrations.md](v1-release-integrations.md) — Stream / Stripe / Postmark
- [playbook.md §7](playbook.md#phase-7-production-deployment-and-validation)
- [scripts/README.md](../scripts/README.md) — Key Vault, Redis, E2E user, smoke script
