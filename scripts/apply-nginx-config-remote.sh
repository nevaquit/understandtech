#!/usr/bin/env bash
# Remote wrapper: apply nginx vhost from monorepo on production VM via Azure Run Command.
# Syncs javascript.php / requirejs.php PHP-FPM routing from infrastructure/nginx/understandtech.conf.
#
# Prerequisites:
#   az login
#   Repo at /opt/understandtech-plugins on VM
#
# Usage:
#   ./scripts/apply-nginx-config-remote.sh
set -euo pipefail

RG="${AZURE_RG:-understandtech-prod-rg}"
VM="${AZURE_VM:-understandtech-web-prod}"

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
bash /opt/understandtech-plugins/scripts/apply-nginx-config.sh" \
  -o json \
  --query 'value[0].message' -o tsv

echo 'Done. Nginx vhost synced from repo.'
