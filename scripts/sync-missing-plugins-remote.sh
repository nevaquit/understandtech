#!/usr/bin/env bash
# Azure wrapper for sync-missing-plugins-vm.sh (run after pushing script to origin/main).
set -euo pipefail

RG="${AZURE_RG:-understandtech-prod-rg}"
VM="${AZURE_VM:-understandtech-web-prod}"
REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

az account show -o none >/dev/null 2>&1 || { echo 'Run: az login' >&2; exit 1; }

az vm run-command invoke \
  -g "$RG" \
  -n "$VM" \
  --command-id RunShellScript \
  --scripts \
    "set -euo pipefail
cd ${REPO}
git fetch origin main
git reset --hard origin/main
bash ${REPO}/scripts/sync-missing-plugins-vm.sh" \
  -o json \
  --query 'value[0].message' -o tsv
