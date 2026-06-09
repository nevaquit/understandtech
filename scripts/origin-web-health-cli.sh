#!/usr/bin/env bash
set -euo pipefail
REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
sudo -u www-data php "${REPO}/scripts/origin-web-health-cli.php"
