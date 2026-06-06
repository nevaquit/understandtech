#!/usr/bin/env bash
# Sync local_aitutor from monorepo checkout on VM, upgrade, purge caches.
# Usage: ./scripts/deploy-local-aitutor-remote.sh
set -euo pipefail

RG="${AZURE_RG:-understandtech-prod-rg}"
VM="${AZURE_VM:-understandtech-web-prod}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

az account show -o none >/dev/null 2>&1 || { echo 'Run: az login' >&2; exit 1; }

az vm run-command invoke \
  -g "$RG" \
  -n "$VM" \
  --command-id RunShellScript \
  --scripts @"$SCRIPT_DIR/deploy-local-aitutor-vm.sh" \
  -o json \
  --query 'value[0].message' -o tsv

echo 'Done. local_aitutor deployed and caches purged on VM.'
