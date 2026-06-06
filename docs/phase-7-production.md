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
| v1.0.0 core tag | ✅ **Gates green** — Stream upload + billing/email deferred (see below) |

## Deferred integrations (post–v1.0.0 core)

These are **not blockers** for tagging `v1.0.0` on the core learning platform.

| Integration | Gate impact | When to resume |
|-------------|-------------|----------------|
| Cloudflare Stream upload + lesson embed | Video lessons | [stream-upload-checklist.md](stream-upload-checklist.md) |
| Stripe (`stripe-*` KV, payment account) | Billing/checkout | `.\scripts\stripe-kv-setup-interactive.ps1` → `configure-stripe-remote.sh` |
| Postmark (`postmark-server-token`) | Transactional email | Verify sender → KV → `setup-postmark-smtp-remote.sh` |
| `payment-flow.spec.ts` | E2E billing | `STRIPE_TEST=1` + `E2E_PAID_COURSE_PATH` after Stripe live |
| pgvector RAG | Course-grounded tutor | [rag-phase2.md](rag-phase2.md) |

## v1.0.0 scope — core vs optional

### Required for `v1.0.0` tag (core platform)

| Gate | Status |
|------|--------|
| Staging Playwright core suite | ✅ 8/8 chromium (`--workers=1`) |
| Smoke test critical paths | ✅ SSL, HTTP 200, AI health/auth from workstation |
| Key Vault — six core secrets | ✅ DB, Redis, Anthropic, OpenAI, worker shared secret |
| DNS / Cloudflare proxy / Origin Pulls | ✅ |
| Redis sessions wired | ✅ |
| Self-hosted runner online | ✅ |
| Rollback plan documented | ✅ |
| v1.0.0 core release doc | ✅ [v1.0.0-core-release.md](v1.0.0-core-release.md) |
| v1.0.0 tag checklist | ✅ [v1.0.0-tag-checklist.md](v1.0.0-tag-checklist.md) |

### Optional / post-v1 (deferred integrations)

| Gate | Status | Notes |
|------|--------|-------|
| Cloudflare Stream test video + lesson embed | ⏸ **Post-v1** | Signing code may exist; upload is user action — [stream-upload-checklist.md](stream-upload-checklist.md) |
| Stripe webhooks + checkout | ⏸ **Deferred** | `paygw_stripe` on VM; KV secrets absent |
| Postmark sender + SMTP | ⏸ **Deferred** | `smtphosts` empty on VM |
| `CF_AIG_AUTHORIZATION` Worker secret | ⚠️ **Optional** | Only if AI Gateway authenticated access enabled — [ai-gateway README](../cloudflare-worker/ai-gateway/README.md) |
| `auth.spec.ts` (chromium-auth) | ⚠️ Flaky | Not a §7.1 blocker |
| pgvector RAG | ⏸ **Phase 2** | Stub in `local_aitutor\rag_context` — [rag-phase2.md](rag-phase2.md) |
| Full smoke `TEST_VIDEO_URL` | ⏸ After Stream upload | `generate-stream-signed-url.sh` |

## Pre-deploy gates (§7.1)

| Gate | Status | Evidence / notes |
|------|--------|------------------|
| Staging Playwright tests pass | ✅ **Done** (core) | **8/8 pass** chromium project (`--workers=1`, 2026-06-06): auth setup + AI tutor **4/4** + course nav **3/3**. `payment-flow.spec.ts` excluded unless `STRIPE_TEST=1`. |
| Smoke test passes (no failures) | ✅ **Core paths** | SSL, HTTP 200, AI health/auth pass from workstation. Git Bash DNS check may fail on Windows (`dig`/`nslookup` PATH). `TEST_VIDEO_URL` skipped (Stream post-v1). Full run: `ORIGIN_IP=52.252.59.54 PROD_URL=https://understandtech.app ./scripts/smoke-test-deployment.sh` |
| Azure Key Vault secrets populated | ✅ **Core** / ⏸ **Billing+email deferred** | Six core OK (2026-06-06). **Stripe:** `stripe-*` — ⏸ deferred. **Postmark:** `postmark-server-token` — ⏸ deferred. Stream signing key present but upload not required for core tag. |
| Production DNS → Cloudflare | ✅ **Done** | `understandtech.app` → Cloudflare anycast. |
| Cloudflare DNS records proxied | ✅ **Done** | `Server: cloudflare`, `CF-RAY` on responses. |
| Authenticated Origin Pulls enabled | ✅ **Done** | Nginx `ssl_client_certificate` present on VM. Direct `--resolve understandtech.app:443:52.252.59.54` from workstation: **TLS handshake fails** (curl 000/35) — origin not serving anonymous HTTPS; traffic must go through Cloudflare. |
| Self-hosted runner idle/online | ✅ **Done** | `{"name":"understandtech-web-prod","status":"online","busy":false}` |
| Rollback plan documented | ✅ **Done** | Playbook §7.4 + checklist rollback section |
| Redis sessions wired | ✅ **Done** | `\core\session\redis` in live `config.php`; Azure Redis `PONG` (TLS); `session_redis_encrypt` SSL context array; **`fetchbuffersize` 100000** for PgBouncer transaction mode. |
| Nginx vhost synced | ✅ | `apply-nginx-config-remote.sh` — javascript.php PHP-FPM routing |
| Origin DB recovery script | ✅ | `recover-origin-db.sh` + `recover-origin.yml` workflow |

## Key Vault audit (2026-06-06)

```
OK   moodle-db-password (len=32)
OK   moodle-app-password (len=32)
OK   redis-password (len=44)
OK   anthropic-api-key (len=108)
OK   openai-api-key (len=164)
OK   cf-worker-shared-secret (len=44)
OK   cf-stream-signing-key (len=57)   # present; Stream upload post-v1
DEF  stripe-secret-key          (deferred)
DEF  stripe-publishable-key     (deferred)
DEF  stripe-webhook-secret      (deferred)
DEF  postmark-server-token      (deferred)
```

When ready for billing/email: `.\scripts\stripe-kv-setup-interactive.ps1` → `./scripts/configure-stripe-remote.sh` and `./scripts/setup-postmark-smtp-remote.sh`.

## Smoke / Origin Pulls (2026-06-06)

From workstation (PowerShell + curl):

| Check | Result |
|-------|--------|
| HTTPS via Cloudflare | 200, `cf-ray` present |
| AI `/health` | `{"status":"ok"}` |
| AI `/tutor` no JWT | 401 |
| Direct origin `52.252.59.54:443` | TLS handshake failure (blocked) |
| `TEST_VIDEO_URL` | Not set — skipped (post-v1) |

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

See [v1.0.0-core-release.md](v1.0.0-core-release.md) for exact tag + deploy + smoke commands.

```bash
git tag -a v1.0.0 -m "Core production release — LMS, AI tutor, CI/CD (billing/Stream deferred)"
git push origin v1.0.0
gh workflow run deploy.yml --ref v1.0.0
gh run watch
PROD_URL=https://understandtech.app GITHUB_REPO=nevaquit/understandtech \
  ./scripts/smoke-test-deployment.sh
```

Then execute every row in [post-deployment-validation.md](post-deployment-validation.md) (skip deferred-integration rows).

## Recommended next steps (core platform shipped)

1. **Tag `v1.0.0`** — [v1.0.0-core-release.md](v1.0.0-core-release.md)
2. **RAG Phase 2** — [rag-phase2.md](rag-phase2.md) (pgvector + Worker context injection)
3. **Nginx / sudoers sync** — `./scripts/apply-nginx-config-remote.sh`, `./scripts/sync-sudoers-remote.sh`
4. **Later:** Stream upload, Stripe KV, Postmark KV

## Related docs

- [v1.0.0-core-release.md](v1.0.0-core-release.md) — tag, deploy, smoke commands
- [post-deployment-validation.md](post-deployment-validation.md) — 30-minute checklist
- [rag-phase2.md](rag-phase2.md) — post-v1 RAG plan
- [stripe-integration.md](stripe-integration.md) — Stripe (deferred)
- [v1-release-integrations.md](v1-release-integrations.md) — Stream / Stripe / Postmark
- [playbook.md §7](playbook.md#phase-7-production-deployment-and-validation)
- [scripts/README.md](../scripts/README.md) — Key Vault, Redis, E2E user, smoke script
