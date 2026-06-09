#!/usr/bin/env bash
# Exit 0 when production Moodle is healthy (delegates to verify-moodle-web-health.sh).
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
SCRIPT="${REPO}/scripts/verify-moodle-web-health.sh"
if [ ! -f "$SCRIPT" ]; then
  SCRIPT="$(cd "$(dirname "$0")" && pwd)/verify-moodle-web-health.sh"
fi

bash "$SCRIPT"
echo 'origin_web_health_ok=1'
