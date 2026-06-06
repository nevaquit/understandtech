#!/usr/bin/env bash
# Stripe pre-flight on the Moodle VM: read Key Vault secrets, merge into /etc/moodle/env,
# verify paygw_stripe is installed. Does NOT store secrets in this script or in git.
#
# Prerequisites: az login, Key Vault Secrets User on utkvnhhwegpz3rem6, run as user with sudo.
#
# Usage (on VM):
#   ./scripts/configure-stripe-vm.sh
#   ./scripts/configure-stripe-vm.sh --dry-run
#
# Payment account + course enrolment still require Moodle admin UI (see docs/stripe-integration.md).

set -euo pipefail

KEY_VAULT="${KEY_VAULT:-utkvnhhwegpz3rem6}"
MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
ENV_FILE="${ENV_FILE:-/etc/moodle/env}"
DRY_RUN="${DRY_RUN:-0}"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --vault) KEY_VAULT="$2"; shift 2 ;;
    --dry-run) DRY_RUN=1; shift ;;
    -h|--help)
      sed -n '2,12p' "$0"
      exit 0
      ;;
    *) echo "Unknown option: $1" >&2; exit 1 ;;
  esac
done

az account show -o none >/dev/null 2>&1 || { echo 'Run: az login' >&2; exit 1; }

read_kv() {
  local name="$1"
  az keyvault secret show --vault-name "$KEY_VAULT" --name "$name" --query value -o tsv
}

validate_secret() {
  local label="$1" value="$2"
  if [[ -z "$value" || "$value" == "REPLACE-ME" ]]; then
    echo "[FAIL] Key Vault secret '$label' is empty or REPLACE-ME" >&2
    echo "       Populate via ./scripts/populate-keyvault-secrets.sh (see docs/stripe-integration.md)" >&2
    exit 1
  fi
  echo "[ OK ] $label present (len=${#value})"
}

echo "Key Vault: $KEY_VAULT"
echo "Moodle:    $MOODLE_DIR"

stripe_secret="$(read_kv stripe-secret-key)"
stripe_publishable="$(read_kv stripe-publishable-key)"
stripe_webhook="$(read_kv stripe-webhook-secret)"

validate_secret stripe-secret-key "$stripe_secret"
validate_secret stripe-publishable-key "$stripe_publishable"
validate_secret stripe-webhook-secret "$stripe_webhook"

webhook_path="$MOODLE_DIR/payment/gateway/stripe/webhook.php"
if [[ -f "$webhook_path" ]]; then
  echo "[ OK ] paygw_stripe installed ($webhook_path)"
else
  echo "[WARN] paygw_stripe not found — install from Moodle plugins directory first" >&2
  echo "       See docs/stripe-integration.md (Option A)" >&2
fi

if [[ -d "$MOODLE_DIR/enrol/stripepayment" ]]; then
  echo "[ OK ] enrol_stripepayment present (optional plugin)"
else
  echo "[info] enrol_stripepayment not installed (optional; primary path uses enrol_fee + paygw_stripe)"
fi

MARKER='# understandtech: stripe keys from Key Vault'
stripe_block="${MARKER}
STRIPE_SECRET_KEY=${stripe_secret}
STRIPE_PUBLISHABLE_KEY=${stripe_publishable}
STRIPE_WEBHOOK_SECRET=${stripe_webhook}
"

if [[ "$DRY_RUN" -eq 1 ]]; then
  echo '[dry-run] Would merge Stripe vars into' "$ENV_FILE"
  exit 0
fi

if [[ ! -f "$ENV_FILE" ]]; then
  echo "[FAIL] $ENV_FILE missing — run setup-moodle-env-vm.ps1 first" >&2
  exit 1
fi

tmp="$(mktemp)"
sudo grep -vF "$MARKER" "$ENV_FILE" | sudo grep -vE '^STRIPE_' >"$tmp" || true
printf '\n%s\n' "$stripe_block" >>"$tmp"
sudo cp "$tmp" "$ENV_FILE"
rm -f "$tmp"
sudo chown root:www-data "$ENV_FILE"
sudo chmod 640 "$ENV_FILE"
echo "[set]  Stripe env vars merged into $ENV_FILE"

if [[ -f "$MOODLE_DIR/admin/cli/cfg.php" ]]; then
  enabled="$(sudo -u www-data /usr/bin/php "$MOODLE_DIR/admin/cli/cfg.php" --name=enabled --component=enrol_fee 2>/dev/null || echo '')"
  if [[ "$enabled" != "1" ]]; then
    echo "[next] Enable enrol_fee: Site administration → Plugins → Enrolments → Enrolment on payment"
  else
    echo "[ OK ] enrol_fee enabled"
  fi
fi

echo ''
echo 'Manual Moodle admin steps (required):'
echo '  1. Site administration → Plugins → Payment gateways → enable Stripe'
echo '  2. Site administration → Payments → Payment accounts → create account + add Stripe gateway'
echo '     Use keys from Key Vault (or copy from /etc/moodle/env)'
echo '  3. Per course: Enrolment methods → Enrolment on payment → link payment account + fee'
echo '  4. Webhook URL: https://understandtech.app/payment/gateway/stripe/webhook.php'
echo '     (paygw_stripe usually registers this automatically when the payment account is saved)'
echo ''
echo 'Test card: 4242 4242 4242 4242 — see docs/stripe-integration.md'
