#!/usr/bin/env bash
# Remote wrapper: configure Postmark SMTP on production VM via Azure Run Command.
#
# Usage:
#   POSTMARK_SERVER_TOKEN='...' ./scripts/setup-postmark-smtp-remote.sh
#   # or after storing token in Key Vault as postmark-server-token:
#   ./scripts/setup-postmark-smtp-remote.sh
set -euo pipefail

RG="${AZURE_RG:-understandtech-prod-rg}"
VM="${AZURE_VM:-understandtech-web-prod}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

az account show -o none >/dev/null 2>&1 || { echo 'Run: az login' >&2; exit 1; }

params=()
if [[ -n "${POSTMARK_SERVER_TOKEN:-}" ]]; then
  params+=(--parameters "POSTMARK_SERVER_TOKEN=$POSTMARK_SERVER_TOKEN")
fi
if [[ -n "${SMTP_FROM:-}" ]]; then
  params+=(--parameters "SMTP_FROM=$SMTP_FROM")
fi

az vm run-command invoke \
  -g "$RG" \
  -n "$VM" \
  --command-id RunShellScript \
  --scripts @"$SCRIPT_DIR/setup-postmark-smtp-vm.sh" \
  "${params[@]}" \
  -o json \
  --query 'value[0].message' -o tsv

echo 'Done. Verify with Moodle test email or password reset.'
