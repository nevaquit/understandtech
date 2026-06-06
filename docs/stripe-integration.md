# Stripe integration — understandtech.app

Stripe handles subscription and one-time course payments. Card data never touches the Moodle VM (PCI scope stays with Stripe). API keys live in Azure Key Vault `utkvnhhwegpz3rem6` and are injected at runtime — never committed to this monorepo.

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

1. Create or open your Stripe account at [dashboard.stripe.com](https://dashboard.stripe.com).
2. Start in **Test mode** (toggle top-right).
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
