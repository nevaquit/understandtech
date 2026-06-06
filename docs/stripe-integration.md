# Stripe integration — understandtech.app

Stripe handles subscription and one-time course payments. Card data never touches the Moodle VM (PCI scope stays with Stripe). API keys live in Azure Key Vault `utkvnhhwegpz3rem6` and are injected at runtime — never committed to this monorepo.

> **Repo note:** This monorepo has **no** references to Negotiatemedicalbill.ai, its Stripe account IDs, or its banking configuration. Negotiatemedicalbill billing is managed outside this repository. Use the Stripe Dashboard steps below to mirror payout settings manually from that account.

## Separate Stripe account for understandtech.app

understandtech.app and Negotiatemedicalbill.ai are separate products under **AI Tech Pros, Inc.** Each product should use its **own Stripe account** (isolated customers, payouts, webhooks, and API keys). Do **not** reuse Negotiatemedicalbill API keys in understandtech Key Vault — every Stripe account has unique `sk_…`, `pk_…`, and `whsec_…` values.

### Same legal entity (AI Tech Pros)

When both accounts belong to the same legal entity (AI Tech Pros, Inc.):

- Stripe **Organizations** (or multiple accounts under one login) let you manage both brands without a second email signup.
- **Tax ID (EIN)** and **business verification** can often be reused after the first account is verified — Stripe may pre-fill or skip duplicate KYC for the second account.
- **Payout bank account** is configured per account, but you enter the **same** routing and account numbers on each account (Stripe does not auto-copy bank details between accounts; you add them again or pick a saved external account when offered).
- Charges, refunds, disputes, and Connect transfers stay **scoped to the active account** — switching the dashboard account picker changes which keys and webhooks you see.

### Option A — New account under the same Stripe login (recommended)

Use this when you already log into Stripe for Negotiatemedicalbill.ai with an AI Tech Pros owner email.

1. Sign in at [dashboard.stripe.com](https://dashboard.stripe.com) with the **same** Stripe login used for Negotiatemedicalbill.
2. Open the **account picker** (top-left account name) → **Create account** (wording may be **Add account** under **Settings → Account** depending on dashboard version).
3. Choose **Create a new account** (standalone account for a new product — not a Connect connected account unless you deliberately use Connect).
4. Name the account clearly, e.g. `UnderstandTech` or `understandtech.app` (internal label only; students see business name from **Settings → Business details**).
5. Complete activation for the **new** account while it is selected in the account picker.

**Why Option A:** One login, shared org-level visibility, separate API keys and payout ledgers per product. Matches how a multi-product company typically operates.

### Option B — Brand-new Stripe signup

Use only if Negotiatemedicalbill uses a different owner email or you cannot add accounts under the existing login.

1. Sign up at [stripe.com](https://stripe.com) with an AI Tech Pros corporate email (e.g. `billing@…` or `ops@…`).
2. During activation, set legal entity to **AI Tech Pros, Inc.** (same as Negotiatemedicalbill if that is the existing entity).
3. Invite other team members via **Settings → Team** so both products remain accessible.

Option B creates a separate Stripe login; you will not see Negotiatemedicalbill in the same account picker unless you later link accounts through an Organization.

### Mirror payout bank account from Negotiatemedicalbill.ai

Perform these steps **while the understandtech.app Stripe account is selected** in the dashboard. Never commit bank account or routing numbers to git.

1. **Reference (read-only):** Switch account picker to **Negotiatemedicalbill** → **Settings → Payouts** → note payout schedule (daily/weekly/manual) and currency — do **not** copy account numbers into this repo.
2. Switch back to the **understandtech.app** account.
3. **Settings → Payouts → Add bank account** (or **Settings → Bank accounts and scheduling**).
4. Enter the **same** US bank routing and account numbers used on Negotiatemedicalbill (re-type manually; Stripe does not import them from the sibling account).
   - If Stripe shows **Use existing external account** or a saved payout method for the same legal entity, you may select it instead of re-entering.
5. Complete any micro-deposit verification if prompted (usually skipped when the bank was already verified for the same entity on another account).
6. Set **Payout schedule** to match Negotiatemedicalbill (e.g. daily automatic) if desired.

### Business profile (understandtech.app / AI Tech Pros)

With the understandtech account selected:

| Field | Suggested value |
|-------|-----------------|
| **Legal business name** | AI Tech Pros, Inc. |
| **Doing business as (DBA)** | understandtech.app (if Stripe asks for customer-facing name) |
| **Business website** | `https://understandtech.app` |
| **Product description** | Online certification training / LMS subscriptions and course payments |
| **Support email / phone** | understandtech support contacts (not Negotiatemedicalbill support) |
| **Statement descriptor** | Short name students see on card statements (e.g. `UNDERTECH` or `AI TECH PROS`) — must differ from Negotiatemedicalbill descriptor to avoid confusion |
| **Tax ID** | Same EIN as Negotiatemedicalbill if same legal entity |

Complete **Settings → Business → Public details** and account activation checklist before live mode.

### Test mode first

1. Confirm **Test mode** toggle (top-right) is **on** for the new understandtech account.
2. **Developers → API keys** — copy **test** publishable and secret keys (see Key Vault table below).
3. Configure Moodle payment account with test keys; run a checkout with card `4242 4242 4242 4242` (see [Test mode](#test-mode)).
4. Only after test checkout and webhooks succeed, repeat with **Live mode** keys and live webhook signing secret.

### Webhook URL (understandtech.app only)

Register webhooks on the **understandtech Stripe account**, not Negotiatemedicalbill:

```text
https://understandtech.app/payment/gateway/stripe/webhook.php
```

- **Preferred:** Moodle `paygw_stripe` creates the endpoint when you save the payment account (see [Webhook endpoint](#2-webhook-endpoint)).
- **Manual:** **Developers → Webhooks → Add endpoint** → URL above → copy signing secret to Key Vault.
- Test-mode and live-mode webhooks are **separate** endpoints and secrets.

### Key Vault secret names (understandtech account only)

Vault: **`utkvnhhwegpz3rem6`** (understandtech Azure subscription — not shared with Negotiatemedicalbill infrastructure).

| Key Vault name | Env var | Source (understandtech Stripe account) |
|----------------|---------|------------------------------------------|
| `stripe-secret-key` | `STRIPE_SECRET_KEY` | **Developers → API keys** → Secret (`sk_test_…` then `sk_live_…`) |
| `stripe-publishable-key` | `STRIPE_PUBLISHABLE_KEY` | Same page → Publishable (`pk_test_…` / `pk_live_…`) |
| `stripe-webhook-secret` | `STRIPE_WEBHOOK_SECRET` | **Developers → Webhooks** → signing secret for understandtech endpoint (`whsec_…`) |

These names are **distinct in practice** from Negotiatemedicalbill because they live in understandtech’s vault and hold **understandtech account keys only**. Do not paste Negotiatemedicalbill `sk_` / `pk_` / `whsec_` values here.

If both products ever shared one Key Vault (not recommended), use prefixed names such as `ut-stripe-secret-key` vs `nmb-stripe-secret-key` and update `scripts/populate-keyvault-secrets.*` accordingly — current scripts expect the unprefixed `stripe-*` names above.

Populate after obtaining test keys:

```powershell
$env:STRIPE_SECRET_KEY = 'sk_test_...'      # from understandtech account, test mode
$env:STRIPE_PUBLISHABLE_KEY = 'pk_test_...'
$env:STRIPE_WEBHOOK_SECRET = 'whsec_...'    # after webhook exists or payment account save
.\scripts\populate-keyvault-secrets.ps1
.\scripts\configure-stripe-vm.ps1
```

### Checklist — new understandtech Stripe account

| # | Step | Owner |
|---|------|--------|
| 1 | Create understandtech Stripe account (Option A or B) under AI Tech Pros | Stripe Dashboard |
| 2 | **Settings → Business** — understandtech.app profile, EIN, support URL | Stripe Dashboard |
| 3 | **Settings → Payouts** — add same bank as Negotiatemedicalbill (manual re-entry) | Stripe Dashboard |
| 4 | Stay in **Test mode** → copy test API keys | Stripe Dashboard |
| 5 | Install `paygw_stripe` on VM if not already ([Option A install](#recommended-install-path-option-a)) | VM / Moodle admin |
| 6 | Populate Key Vault `stripe-*` secrets with **understandtech test** keys | `populate-keyvault-secrets.ps1` |
| 7 | Run `configure-stripe-vm.ps1` → `/etc/moodle/env` | Workstation or VM |
| 8 | Moodle **Payment accounts** → Stripe gateway → save (registers webhook) | Moodle admin |
| 9 | Test checkout `4242…` on a paid course | Browser |
| 10 | Switch to **Live mode** → new live keys + live `whsec_` → update KV → re-save payment account | Stripe + KV + Moodle |
| 11 | Post-deploy: webhook POST returns not **404** ([verify](#2-webhook-endpoint)) | CLI / validation doc |

---

## Recommended install path (Option A)

| Approach | Verdict |
|----------|---------|
| **A — Install on VM from [Moodle plugins directory](https://moodle.org/plugins/)** | **Recommended.** Matches `.cursorrules`: upstream Moodle plugins are core-adjacent, not custom IP. Install under `/var/www/moodle/`, upgrade via Moodle admin/CLI. |
| B — Vendor into `moodle-plugins/` | **Not recommended.** Would track third-party GPL code in the monorepo, bloat CI, and fight the deploy workflow (which only rsyncs custom plugins). |

### Plugins

| Plugin | Frankstyle | VM path | Role |
|--------|------------|---------|------|
| **Stripe payment gateway** (Alex Morris) | `paygw_stripe` | `/var/www/moodle/payment/gateway/stripe/` | Primary — Moodle 4.x payment API, Checkout, subscriptions, coupons |
| **Enrolment on payment** (Moodle core) | `enrol_fee` | `/var/www/moodle/enrol/fee/` | Links courses to a payment account (ships with Moodle) |
| **Stripe Payment with SCA and Coupon** (DualCube, optional) | `enrol_stripepayment` | `/var/www/moodle/enrol/stripepayment/` | Legacy enrolment method; white-paper listed. Use only if DualCube-specific enrol UX is required — otherwise prefer `paygw_stripe` + `enrol_fee`. |

**Primary billing flow:** `paygw_stripe` + `enrol_fee` (Enrolment on payment).  
**Optional:** `enrol_stripepayment` — separate setup (REST web service token); does not use `webhook.php`.

### Install on production VM

1. Download ZIPs from Moodle.org (match **Moodle 4.5** build):
   - [paygw_stripe](https://moodle.org/plugins/paygw_stripe)
   - [enrol_stripepayment](https://moodle.org/plugins/enrol_stripepayment) — optional
2. **Site administration → Plugins → Install plugins** → upload each ZIP (or unzip to the paths above and open **Notifications**).
3. Run upgrade:
   ```bash
   sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --non-interactive
   sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php
   ```
4. Confirm directories exist:
   ```bash
   test -f /var/www/moodle/payment/gateway/stripe/webhook.php && echo paygw_stripe=ok
   ```

---

## Azure Key Vault secrets

Vault: **`utkvnhhwegpz3rem6`**

| Key Vault name | Env var (on VM `/etc/moodle/env`) | Source |
|----------------|-----------------------------------|--------|
| `stripe-secret-key` | `STRIPE_SECRET_KEY` | Stripe Dashboard → **Developers → API keys** → Secret key (`sk_test_…` / `sk_live_…`) |
| `stripe-publishable-key` | `STRIPE_PUBLISHABLE_KEY` | Same page → Publishable key (`pk_test_…` / `pk_live_…`) |
| `stripe-webhook-secret` | `STRIPE_WEBHOOK_SECRET` | Stripe Dashboard → **Developers → Webhooks** → signing secret (`whsec_…`) — see webhook section below |

Populate placeholders (never commit values):

```powershell
$env:STRIPE_SECRET_KEY = 'sk_test_...'
$env:STRIPE_PUBLISHABLE_KEY = 'pk_test_...'
$env:STRIPE_WEBHOOK_SECRET = 'whsec_...'
.\scripts\populate-keyvault-secrets.ps1
```

Bash: `./scripts/populate-keyvault-secrets.sh` with the same env vars.

Then push env to VM:

```powershell
.\scripts\configure-stripe-vm.ps1    # remote: KV → /etc/moodle/env + checks
# Or on VM directly:
sudo ./scripts/configure-stripe-vm.sh
```

---

## Stripe Dashboard setup

### 1. Account and API keys

1. Create the **understandtech.app** Stripe account (separate from Negotiatemedicalbill) — see [Separate Stripe account for understandtech.app](#separate-stripe-account-for-understandtechapp).
2. Start in **Test mode** (toggle top-right) on the understandtech account.
3. **Developers → API keys** — copy publishable and secret keys into Key Vault (above).

### 2. Webhook endpoint

**Production URL (paygw_stripe):**

```text
https://understandtech.app/payment/gateway/stripe/webhook.php
```

> **Not** `ipn.php` — that path belongs to older PayPal-style plugins. The official `paygw_stripe` webhook handler is `webhook.php`.

**How webhooks are registered**

- **Preferred:** When you save a **Payment account** in Moodle with Stripe API keys, `paygw_stripe` creates the Stripe webhook endpoint automatically and stores the signing secret in Moodle (`paygw_stripe` webhook table). You usually do **not** hand-create the endpoint in Stripe first.
- **Manual fallback** (if auto-creation fails, e.g. switching test → live keys):
  1. **Developers → Webhooks → Add endpoint**
  2. URL: `https://understandtech.app/payment/gateway/stripe/webhook.php`
  3. Events: let the plugin manage events, or select at minimum `checkout.session.completed`, `payment_intent.succeeded`, and subscription events if using recurring billing (see plugin docs).
  4. Copy **Signing secret** → Key Vault `stripe-webhook-secret`.
  5. If Moodle shows stale webhook IDs after key rotation, clear plugin webhook rows per [paygw_stripe issue #23](https://github.com/alexmorrisnz/moodle-paygw_stripe/issues/23) and re-save the payment account.

**Verify endpoint is reachable**

```bash
curl -s -o /dev/null -w '%{http_code}\n' \
  -X POST https://understandtech.app/payment/gateway/stripe/webhook.php
```

Expect **400** (invalid payload) or **202** — not **404**. A 404 means the plugin is not installed or nginx routing is wrong.

### 3. Business settings (production)

- Complete Stripe account activation (business details, bank account).
- Configure **Settings → Customer portal** if students manage subscriptions.
- Enable **Tax** if required for your jurisdictions.

---

## Moodle admin configuration

Run after plugins are installed and Key Vault secrets are populated.

### Payment gateway

1. **Site administration → Plugins → Payment gateways → Manage payment gateways**
2. Enable **Stripe** (eye icon).
3. **Site administration → Payments → Payment accounts → Create account**
   - Name: e.g. `UnderstandTech Stripe`
   - Enable account
   - Add gateway: **Stripe**
   - **Publishable key** / **Secret key**: paste from Key Vault (or from `/etc/moodle/env` if your process copies them manually)
   - Payment type: **One time** and/or **Subscription** per product strategy
   - Save — plugin should register the Stripe webhook for this account

### Course enrolment (primary path)

1. **Site administration → Plugins → Enrolments → Manage enrol plugins** — enable **Enrolment on payment** (`enrol_fee`).
2. In each paid course: **Course administration → Participants → Enrolment methods → Add method → Enrolment on payment**
   - Select the **Payment account** created above
   - Set **Enrolment fee** and **Currency** (e.g. USD)
3. Test as a learner not yet enrolled — Stripe Checkout should open.

### Optional: DualCube `enrol_stripepayment`

Only if you use this plugin instead of (or alongside) `enrol_fee`:

1. **Site administration → Plugins → Enrolments → Stripe payment** — enter API keys and web service token.
2. Enable **Web services** and **REST** protocol; create token for service `moodle_enrol_stripepayment`.
3. Add **Stripe** enrolment method per course.

---

## Test mode

| Item | Value |
|------|--------|
| Test card (success) | `4242 4242 4242 4242` |
| Expiry | Any future date (e.g. `12/34`) |
| CVC | Any 3 digits |
| ZIP | Any valid format |

Use Stripe **Test mode** keys (`sk_test_…`, `pk_test_…`) in Moodle payment account until checkout succeeds end-to-end.

---

## Production vs test checklist

| Step | Test mode | Live mode |
|------|-----------|-----------|
| Stripe dashboard toggle | Test | Live |
| API keys in Key Vault | `sk_test_…` / `pk_test_…` | `sk_live_…` / `pk_live_…` |
| Moodle payment account keys | Match test keys | Match live keys |
| Webhook endpoint | Test-mode endpoint (auto or manual) | **New** live-mode endpoint — test webhooks do not carry over |
| Webhook URL | `https://understandtech.app/payment/gateway/stripe/webhook.php` | Same URL |
| `stripe-webhook-secret` in Key Vault | Test `whsec_…` | Live `whsec_…` |
| Test payment | Card `4242…` | Real card or Stripe test in live (small amount) |
| E2E / smoke | Playbook `payment-flow.spec.ts` (future) | Post-deploy checklist Stripe row |
| Rollback | Disable enrol methods / maintenance mode | Same |

---

## Scripts in this repo

| Script | Purpose |
|--------|---------|
| `scripts/populate-keyvault-secrets.sh` / `.ps1` | Set `stripe-*` secrets when still `REPLACE-ME` |
| `scripts/configure-stripe-vm.sh` | On VM: read KV → append Stripe vars to `/etc/moodle/env`, verify plugin paths |
| `scripts/configure-stripe-vm.ps1` | From workstation: SCP script to VM and run via SSH |

Moodle **payment account** fields (gateway keys linked to courses) are still configured in the admin UI — there is no supported CLI for payment account records. The scripts handle secrets delivery and pre-flight checks only.

---

## Related docs

- [v1-release-integrations.md](v1-release-integrations.md) — gate status
- [phase-7-production.md](phase-7-production.md) — §7.1 pre-deploy gates
- [post-deployment-validation.md](post-deployment-validation.md) — post-launch Stripe row
- White paper §2 — payments (Stripe, PCI offload)
- Playbook §6.1 — future `payment-flow.spec.ts`
