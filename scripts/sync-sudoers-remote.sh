#!/usr/bin/env bash
# Remote wrapper: sync gha-runner sudoers on production VM via Azure Run Command.
# Requires: az login, repo already fetched on VM at /opt/understandtech-plugins.
#
# Usage:
#   ./scripts/sync-sudoers-remote.sh
#   AZURE_RG=... AZURE_VM=... ./scripts/sync-sudoers-remote.sh
set -euo pipefail

RG="${AZURE_RG:-understandtech-prod-rg}"
VM="${AZURE_VM:-understandtech-web-prod}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

az account show -o none >/dev/null 2>&1 || { echo 'Run: az login' >&2; exit 1; }

az vm run-command invoke \
  -g "$RG" \
  -n "$VM" \
  --command-id RunShellScript \
  --scripts \
    "set -euo pipefail
cd /opt/understandtech-plugins
git fetch origin main
git reset --hard origin/main
bash /opt/understandtech-plugins/scripts/sync-sudoers-vm.sh" \
  -o json \
  --query 'value[0].message' -o tsv

echo 'Done. Verify deploy workflow sudo steps still succeed.'
