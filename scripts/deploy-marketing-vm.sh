#!/usr/bin/env bash
# Sync marketing/ from monorepo checkout to /var/www/marketing on the production VM.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
SRC="${REPO}/marketing/"
DST="/var/www/marketing/"

if [[ ! -d "$SRC" ]]; then
  echo "ERROR: marketing source not found: $SRC" >&2
  exit 1
fi

echo "=== deploy marketing site ==="
mkdir -p "$DST"
/usr/bin/rsync -av --delete \
  --exclude='.git' --exclude='.github' \
  "$SRC" "$DST"
/usr/bin/chown -R gha-runner:gha-runner "$DST"
echo "deploy_marketing_complete=1"
