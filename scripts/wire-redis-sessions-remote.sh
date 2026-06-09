#!/usr/bin/env bash
# Remote wrapper: run wire-redis-sessions-vm.sh on production VM via Azure Run Command.
set -euo pipefail

RG="${AZURE_RG:-understandtech-prod-rg}"
VM="${AZURE_VM:-understandtech-web-prod}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

az account show -o none >/dev/null 2>&1 || { echo 'Run: az login' >&2; exit 1; }

az vm run-command invoke \
  -g "$RG" \
  -n "$VM" \
  --command-id RunShellScript \
  --scripts @"$SCRIPT_DIR/wire-redis-sessions-vm.sh" \
  -o json \
  --query 'value[0].message' -o tsv

echo 'Done. Verify: Moodle login survives sudo systemctl restart php8.3-fpm on VM.'
