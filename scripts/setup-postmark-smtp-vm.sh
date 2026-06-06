#!/usr/bin/env bash
# Configure Moodle outgoing SMTP for Postmark on the production VM.
# Reads token from env or Azure Key Vault secret postmark-server-token.
#
# Usage (on VM):
#   POSTMARK_SERVER_TOKEN='...' SMTP_FROM='noreply@understandtech.app' \
#     ./scripts/setup-postmark-smtp-vm.sh
#
# Dry-run (print cfg names only):
#   DRY_RUN=1 POSTMARK_SERVER_TOKEN='x' ./scripts/setup-postmark-smtp-vm.sh
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
KEY_VAULT="${KEY_VAULT:-utkvnhhwegpz3rem6}"
KV_SECRET="${KV_SECRET:-postmark-server-token}"
SMTP_HOST="${SMTP_HOST:-smtp.postmarkapp.com:587}"
SMTP_FROM="${SMTP_FROM:-noreply@understandtech.app}"
SMTP_FROM_NAME="${SMTP_FROM_NAME:-Understand Tech}"

token="${POSTMARK_SERVER_TOKEN:-}"
if [[ -z "$token" && -n "${KEY_VAULT:-}" ]]; then
  if command -v az >/dev/null 2>&1; then
    token="$(az keyvault secret show --vault-name "$KEY_VAULT" --name "$KV_SECRET" --query value -o tsv 2>/dev/null || true)"
  fi
fi

if [[ -z "$token" || "$token" == "REPLACE-ME" ]]; then
  echo "ERROR: Postmark token missing. Set POSTMARK_SERVER_TOKEN or create Key Vault secret: $KV_SECRET" >&2
  exit 1
fi

cfg_set() {
  local name="$1" value="$2"
  if [[ "${DRY_RUN:-0}" == "1" ]]; then
    echo "[dry-run] would set $name"
    return 0
  fi
  sudo -u www-data /usr/bin/php "$MOODLE_DIR/admin/cli/cfg.php" --name="$name" --set="$value" >/dev/null
}

echo "Configuring Postmark SMTP for Moodle (from: $SMTP_FROM)"

cfg_set smtphosts "$SMTP_HOST"
cfg_set smtpsecure tls
cfg_set smtpauthtype LOGIN
cfg_set smtpuser "$token"
cfg_set smtppass "$token"
cfg_set noreplyaddress "$SMTP_FROM"
cfg_set supportemail "$SMTP_FROM"
cfg_set supportname "$SMTP_FROM_NAME"

if [[ "${DRY_RUN:-0}" == "1" ]]; then
  echo "Dry run complete."
  exit 0
fi

current="$(sudo -u www-data /usr/bin/php "$MOODLE_DIR/admin/cli/cfg.php" --name=smtphosts 2>/dev/null || true)"
if [[ "$current" == "$SMTP_HOST" ]]; then
  echo "OK: smtphosts=$current"
else
  echo "WARN: smtphosts read back as: ${current:-empty}" >&2
  exit 1
fi

echo "Next: Site admin → Server → Email → Test outgoing mail, or trigger password reset for e2etest."
