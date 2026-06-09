#!/usr/bin/env bash
# Exit 0 when Moodle web bootstrap + dashboard DB paths are healthy.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
sudo -u www-data php "${REPO}/scripts/origin-web-health-cli.php"
