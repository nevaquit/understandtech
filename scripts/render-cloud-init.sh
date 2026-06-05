#!/usr/bin/env bash
# Render cloud-init template before Azure deployment.
# Usage: ./scripts/render-cloud-init.sh > /tmp/cloud-init-rendered.yaml
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TEMPLATE="$ROOT/infrastructure/runner/cloud-init.yaml"

: "${POSTGRES_FQDN:?Set POSTGRES_FQDN}"
: "${REGISTRATION_TOKEN:?Set REGISTRATION_TOKEN}"
: "${STORAGE_ACCOUNT_NAME:?Set STORAGE_ACCOUNT_NAME}"
: "${SMB_PASSWORD:?Set SMB_PASSWORD}"
REPO_SSH_URL="${REPO_SSH_URL:-git@github.com:nevaquit/understandtech.git}"

sed \
  -e "s|{{POSTGRES_FQDN}}|${POSTGRES_FQDN}|g" \
  -e "s|{{REGISTRATION_TOKEN}}|${REGISTRATION_TOKEN}|g" \
  -e "s|{{STORAGE_ACCOUNT_NAME}}|${STORAGE_ACCOUNT_NAME}|g" \
  -e "s|{{SMB_PASSWORD}}|${SMB_PASSWORD}|g" \
  -e "s|{{REPO_SSH_URL}}|${REPO_SSH_URL}|g" \
  "$TEMPLATE"
