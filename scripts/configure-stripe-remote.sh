#!/usr/bin/env bash
# Remote wrapper: configure Stripe env on production VM via Azure Run Command.
# Reads Stripe secrets from Key Vault on the VM (managed identity / az login on VM).
#
# Prerequisites:
#   az login (workstation)
#   Key Vault secrets stripe-* populated (not REPLACE-ME)
#   VM has Key Vault Secrets User + az CLI
#
# Usage:
#   ./scripts/configure-stripe-remote.sh
#   ./scripts/configure-stripe-remote.sh --dry-run
set -euo pipefail

RG="${AZURE_RG:-understandtech-prod-rg}"
VM="${AZURE_VM:-understandtech-web-prod}"
KEY_VAULT="${KEY_VAULT:-utkvnhhwegpz3rem6}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DRY_RUN=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dry-run) DRY_RUN=1; shift ;;
    -h|--help)
      sed -n '2,14p' "$0"
      exit 0
      ;;
    *) echo "Unknown option: $1" >&2; exit 1 ;;
  esac
done

az account show -o none >/dev/null 2>&1 || { echo 'Run: az login' >&2; exit 1; }

for name in stripe-secret-key stripe-publishable-key stripe-webhook-secret; do
  val="$(az keyvault secret show --vault-name "$KEY_VAULT" --name "$name" --query value -o tsv 2>/dev/null || true)"
  if [[ -z "$val" || "$val" == "REPLACE-ME" ]]; then
    echo "[FAIL] Key Vault secret '$name' missing or REPLACE-ME" >&2
    echo "       Run: .\\scripts\\stripe-kv-setup-interactive.ps1" >&2
    exit 1
  fi
  echo "[ OK ] $name present (len=${#val})"
done

invoke_args=(
  -g "$RG"
  -n "$VM"
  --command-id RunShellScript
  -o json
  --query 'value[0].message' -o tsv
)

if [[ "$DRY_RUN" -eq 1 ]]; then
  az vm run-command invoke "${invoke_args[@]}" \
    --scripts "export KEY_VAULT=$KEY_VAULT DRY_RUN=1; $(cat "$SCRIPT_DIR/configure-stripe-vm.sh")"
else
  az vm run-command invoke "${invoke_args[@]}" \
    --scripts "export KEY_VAULT=$KEY_VAULT; $(cat "$SCRIPT_DIR/configure-stripe-vm.sh")"
fi

echo 'Done. Complete Moodle payment account setup — docs/stripe-integration.md'
