#!/usr/bin/env bash
# One-shot: sync theme, stabilize, set default theme, disable maintenance, verify health.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
cd "$REPO"
git fetch origin main
git reset --hard origin/main

bash "${REPO}/scripts/sync-theme-understandtech-vm.sh"
bash "${REPO}/scripts/post-deploy-stabilize-vm.sh"

sudo -u www-data php -r '
define("CLI_SCRIPT", true);
require "/var/www/moodle/config.php";
set_config("theme", "understandtech");
echo "theme=" . get_config("core", "theme") . PHP_EOL;
'

sudo -u www-data php /var/www/moodle/admin/cli/maintenance.php --disable

export STAGING_URL="${STAGING_URL:-https://staging.understandtech.app/learn}"
bash "${REPO}/scripts/verify-moodle-web-health.sh"
