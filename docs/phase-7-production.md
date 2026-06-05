# Phase 7 — Production Deployment and Validation

Per `docs/playbook.md` §7.1–7.4. **Do not tag `v1.0.0` until all pre-deploy gates below are green.**

## Status summary

| Area | State |
|------|--------|
| Production URL live | ✅ `https://understandtech.app` (HTTP 200, `cf-ray` present) |
| AI Worker health | ✅ `https://ai.understandtech.app/health` → `{"status":"ok"}` |
| Self-hosted runner | ✅ `understandtech-web-prod` online (`gh api …/runners`) |
| CI/CD | ✅ `deploy.yml` validate + deploy green on `main` |
| Post-deploy checklist | ✅ `docs/post-deployment-validation.md` |
| v1.0.0 tag + formal release deploy | ⏸ **Blocked** — gates below |

## Pre-deploy gates (§7.1)

| Gate | Status | Evidence / notes |
|------|--------|------------------|
| Staging Playwright tests pass | ⚠️ **Partial** | Suite exists (`tests/e2e/`); auth/tutor specs need `STAGING_TEST_USER_*` in `.env`. Invalid-login test runs without creds. |
| Smoke test passes (no failures) | ⚠️ **Partial** | Automated script: **6 pass / 6 warn / 0 fail** when `gh` + DNS tools available; **5 pass / 6 warn / 1 fail** from Git Bash on Windows (DNS check — `dig`/`nslookup` not in PATH). Critical paths (SSL, HTTP, AI health/auth, Moodle login) pass. |
| Azure Key Vault secrets populated | ❓ **Verify on VM** | Vault `utkvnhhwegpz3rem6`. Run populate validation: `./scripts/populate-keyvault-secrets.sh` (checks 4 LLM/Stream/worker secrets). Also confirm `moodle-app-password`, `redis-password` not `REPLACE-ME`. Requires `az login`. |
| Production DNS → Cloudflare | ✅ **Done** | `understandtech.app` → Cloudflare anycast (104.21.x / 172.67.x). |
| Cloudflare DNS records proxied | ✅ **Done** | Responses include `Server: cloudflare`, `CF-RAY` header. |
| Authenticated Origin Pulls enabled | ⚠️ **Unverified** | Nginx enforces client cert (`deploy.yml` health check notes 400 without CF cert). Set `ORIGIN_IP=52.252.59.54` in smoke script to confirm block. |
| Cloudflare Stream test video | ❌ **Pending** | No `TEST_VIDEO_URL` in smoke runs. Upload video in Stream dashboard; wire signed URL in course content. |
| Stripe webhooks → production | ❌ **Pending** | `paygw_stripe` / `enrol_stripepayment` not in `moodle-plugins/`; no webhook endpoint configured in repo. |
| Postmark sender verified | ❌ **Pending** | Not configured in codebase; Moodle SMTP settings required. |
| Self-hosted runner idle/online | ✅ **Done** | `{"name":"understandtech-web-prod","status":"online","busy":false}` |
| Rollback plan documented | ✅ **Done** | Playbook §7.4 + checklist rollback section |
| Redis sessions wired | ⚠️ **Partial** | `config.php.template` sets `\core\session\redis` → Azure Redis; smoke script still warns Redis may be unreachable until VM tunnel/password verified. |
| `CF_AIG_AUTHORIZATION` Worker secret | ⚠️ **Optional** | Only required when Cloudflare AI Gateway auth is enabled without the Workers AI binding. Worker health/tutor 401 work without it today. Set via `npx wrangler secret put CF_AIG_AUTHORIZATION` if gateway dashboard shows auth errors. |

## Smoke test baseline (2026-06-06)

From engineer workstation (Git Bash, `GITHUB_REPO=nevaquit/understandtech`):

```
=== Summary: 5 passed, 6 warnings, 1 failures ===
[PASS] SSL valid (~89 days remaining)
[PASS] HTTP via Cloudflare: 200
[PASS] AI Worker /health OK
[PASS] AI Worker /tutor rejects unauthenticated requests (401)
[PASS] Moodle login page reachable
[FAIL] DNS resolution (dig/nslookup unavailable in Git Bash on Windows)
[WARN] ORIGIN_IP not set — Origin Pulls test skipped
[WARN] VM DB / Redis / disk checks skipped (not on VM)
[WARN] gh CLI not in Git Bash PATH (runner check skipped; verified separately via PowerShell)
[WARN] TEST_VIDEO_URL not set — Stream JWT skipped
```

Separate verification (PowerShell): `gh api repos/nevaquit/understandtech/actions/runners` → runner **online**; `nslookup understandtech.app` → Cloudflare IPs.

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

## Recommended actions before v1.0.0

1. **Credentials:** Create production test student; add to `tests/e2e/.env`; run `npm test` — all specs green.
2. **Key Vault:** `az login` → run secret loop in post-deployment doc → `./scripts/populate-keyvault-secrets.sh` if any `REPLACE-ME` → `pwsh ./scripts/setup-moodle-env-vm.ps1`.
3. **Origin Pulls:** `ORIGIN_IP=52.252.59.54 ./scripts/smoke-test-deployment.sh` — expect direct-origin block.
4. **Stream:** Upload test video; embed in lesson; export signed URL to `TEST_VIDEO_URL`; re-run smoke.
5. **Redis sessions:** SSH to VM → `redis-cli … PING` → confirm Moodle sessions persist across PHP-FPM reload.
6. **Stripe:** Install/configure Moodle Stripe plugins; point webhook to production; run test card flow (playbook §6.1).
7. **Postmark:** Verify sender domain; configure Moodle SMTP; send test password-reset email.
8. **CF_AIG_AUTHORIZATION:** If AI Gateway returns 401 on tutor traffic, set Worker secret and redeploy via `./scripts/deploy-ai-gateway.sh`.
9. **Tag release:** Only after steps 1–8 pass → tag `v1.0.0` and trigger deploy.

## Related docs

- [post-deployment-validation.md](post-deployment-validation.md) — 30-minute checklist
- [playbook.md §7](playbook.md#phase-7-production-deployment-and-validation)
- [scripts/README.md](../scripts/README.md) — Key Vault, Worker deploy, smoke script
