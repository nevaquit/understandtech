#!/usr/bin/env bash
# Remote wrapper: create E2E test user + course on production VM via Azure Run Command.
# Usage: E2E_PASSWORD='...' ./scripts/setup-e2e-test-user-remote.sh
set -euo pipefail

E2E_PASSWORD="${E2E_PASSWORD:?Set E2E_PASSWORD before running}"
RG="${AZURE_RG:-understandtech-prod-rg}"
VM="${AZURE_VM:-understandtech-web-prod}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

az account show -o none >/dev/null 2>&1 || { echo 'Run: az login' >&2; exit 1; }

az vm run-command invoke \
  -g "$RG" \
  -n "$VM" \
  --command-id RunShellScript \
  --scripts @"$SCRIPT_DIR/setup-e2e-test-user-vm.sh" \
  --parameters "E2E_PASSWORD=$E2E_PASSWORD" \
  -o json \
  --query 'value[0].message' -o tsv

echo 'Copy STAGING_TEST_USER_* and E2E_COURSE_PATH from output into tests/e2e/.env'
