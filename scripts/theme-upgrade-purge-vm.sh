#!/usr/bin/env bash
# Sync theme_understandtech, run Moodle upgrade, purge caches, recycle PHP-FPM.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"

echo "=== theme sync + Moodle upgrade ==="
bash "${REPO}/scripts/sync-theme-understandtech-vm.sh"

echo "=== purge Moodle caches ==="
sudo -u www-data php "${MOODLE_DIR}/admin/cli/purge_caches.php"

echo "=== recycle PHP-FPM ==="
bash "${REPO}/scripts/restart-php-fpm-vm.sh"

echo "theme_upgrade_purge_complete=1"
