# v1.0.0 Integration Gaps — Stream, Stripe, Postmark

Honest status for playbook §7.1 gates that require **external dashboards** or **plugins not yet in this monorepo**.

## Cloudflare Stream (test video)

| Item | Status |
|------|--------|
| Key Vault `cf-stream-signing-key` | ✅ Populated (len 57, not `REPLACE-ME`) |
| `local_certmaster` Stream signing | ✅ **`stream_helper`** — RS256 JWT, 60s expiry; admin kid + subdomain; PEM from `/etc/moodle/cf-stream-signing-key.pem` |
| Test video uploaded | ❌ **User action** — no video in Stream yet |
| Course lesson embed | ❌ **User action** — embed signed player in a lesson after upload |
| Smoke `TEST_VIDEO_URL` | ⏸ Skipped until signed manifest URL exists |

**Checklist:** [stream-upload-checklist.md](stream-upload-checklist.md)

### Steps (Cloudflare dashboard)

1. **Stream → Upload** — upload a short test clip (e.g. 30s MP4). Note the **video ID** and **customer subdomain** from the embed snippet.
2. **Stream → Settings → Signing keys** — confirm signing key PEM matches Key Vault `cf-stream-signing-key`; note the **Key ID** (`kid`).
3. In Moodle, add a **Page** or **Lesson** activity with the Stream iframe URL pattern from [stream-jwt-signing.md](.cursor/skills/edge-serverless-orchestration/stream-jwt-signing.md) (JWT generated server-side, 60s expiry).
4. Generate a smoke URL (after upload):
   ```bash
   export STREAM_VIDEO_ID='<uid>'
   export STREAM_SIGNING_KID='<kid>'
   export STREAM_CUSTOMER_SUBDOMAIN='customer-<id>'
   # CF_STREAM_SIGNING_KEY from Key Vault or env
   TEST_VIDEO_URL="$(./scripts/generate-stream-signed-url.sh)" \
     PROD_URL=https://understandtech.app ./scripts/smoke-test-deployment.sh
   ```

**Automated in repo:** `scripts/generate-stream-signed-url.sh` (needs `python3` + `cryptography`). No Stream upload API token in repo — upload via dashboard.

---

## Stripe (billing)

| Item | Status |
|------|--------|
| Install approach | ✅ **Option A** — install on VM from Moodle.org (not monorepo). See [stripe-integration.md](stripe-integration.md). |
| Moodle plugins on VM | ✅ **`paygw_stripe` installed** — release **1.31** (`2026020800`), `webhook.php` present (Azure run-command 2026-06-06); optional `enrol_stripepayment` not installed |
| Key Vault Stripe secrets | ⏸ **Deferred by user** — `stripe-secret-key`, `stripe-publishable-key`, `stripe-webhook-secret` not in vault `utkvnhhwegpz3rem6`. Helper: `.\scripts\stripe-kv-setup-interactive.ps1` |
| Stripe env on VM (`/etc/moodle/env`) | ⏸ **Blocked** — no `STRIPE_*` vars until KV populated; `configure-stripe-remote.sh` skipped |
| Webhook route reachable | ✅ **HTTP 400** on `POST …/webhook.php` (plugin installed; not 404) |
| Stripe account / webhooks | ❌ **User action** — create understandtech Stripe account; Moodle payment account + test keys |
| E2E `payment-flow.spec.ts` | ⏸ **Deferred** — excluded from chromium project; `chromium-stripe` project when `STRIPE_TEST=1` |

### Expected plugins (white-paper / playbook)

- `paygw_stripe` — payment gateway (primary; use with core `enrol_fee`)
- `enrol_stripepayment` — optional legacy subscription enrolment (DualCube)

### Steps when ready

Full runbook: **[stripe-integration.md](stripe-integration.md)**

1. Install **`paygw_stripe`** on VM from [Moodle plugins directory](https://moodle.org/plugins/paygw_stripe) (not committed to monorepo).
2. Populate Key Vault: `stripe-secret-key`, `stripe-publishable-key`, `stripe-webhook-secret` via `./scripts/populate-keyvault-secrets.sh`.
3. Run `./scripts/configure-stripe-vm.sh` (or `configure-stripe-vm.ps1` from workstation) — merges secrets into `/etc/moodle/env`.
4. **Moodle admin:** Payment accounts → Stripe gateway → link **Enrolment on payment** on paid courses.
5. **Stripe Dashboard → Developers → Webhooks** — endpoint **`https://understandtech.app/payment/gateway/stripe/webhook.php`** (usually auto-created when payment account is saved).
6. Test mode: card **`4242 4242 4242 4242`**.

**Cannot fake:** payment without Stripe account, VM plugin install, and payment account configuration.

---

## Postmark (transactional email)

| Item | Status |
|------|--------|
| Moodle SMTP (`smtphosts`) | ❌ Empty on production VM (verified 2026-06-06) |
| Key Vault `postmark-server-token` | ⏸ **Deferred by user** — secret not in vault (re-audit 2026-06-06) |
| Postmark sender signature | ❌ **User action** |
| Password-reset test email | ❌ Blocked until SMTP configured |

### Steps

1. **Postmark → Sender Signatures** — verify domain `understandtech.app` (DKIM + Return-Path).
2. Create **Server API token**; store in Key Vault:
   ```bash
   az keyvault secret set --vault-name utkvnhhwegpz3rem6 \
     --name postmark-server-token --value '<server-api-token>'
   ```
3. **Automated SMTP wiring** (engineer workstation with `az login`):
   ```bash
   ./scripts/setup-postmark-smtp-remote.sh
   # or on VM: POSTMARK_SERVER_TOKEN='...' ./scripts/setup-postmark-smtp-vm.sh
   ```
   Sets: `smtphosts=smtp.postmarkapp.com:587`, TLS, token auth, `noreply@understandtech.app`.
4. Send test: **Site administration → Server → Email → Outgoing mail configuration → Test outgoing mail**, or trigger password reset for `e2etest`.

**Manual alternative:** Site administration → Server → Email → Outgoing mail (same values as script).

---

## Related scripts

| Script | Purpose |
|--------|---------|
| `./scripts/populate-keyvault-secrets.sh` | LLM + Stream + worker + **Stripe** secrets |
| `./scripts/install-paygw-stripe-vm.sh` | Download + install `paygw_stripe` on VM (Azure Run Command or SSH) |
| `./scripts/stripe-kv-setup-interactive.ps1` | Secure prompts → Stripe KV secrets (no echo) |
| `./scripts/configure-stripe-remote.sh` | KV → `/etc/moodle/env` on VM via Azure Run Command |
| `./scripts/configure-stripe-vm.sh` / `.ps1` | KV → `/etc/moodle/env` Stripe vars; plugin pre-flight (SSH or on-VM) |
| `./scripts/setup-moodle-env-vm.ps1` | KV → `/etc/moodle/env` |
| `./scripts/generate-stream-signed-url.sh` | Build `TEST_VIDEO_URL` after Stream upload |
| `./scripts/setup-postmark-smtp-remote.sh` | KV/env → Moodle Postmark SMTP (Azure Run Command) |
| `./scripts/sync-sudoers-remote.sh` | Sync `gha-runner-sudoers` on VM from repo |
| `./scripts/smoke-test-deployment.sh` | Optional `TEST_VIDEO_URL` check |
