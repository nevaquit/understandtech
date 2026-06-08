#!/usr/bin/env bash
set -euo pipefail
REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "$REPO" fetch origin main
  sudo -u gha-runner git -C "$REPO" reset --hard origin/main
fi
sudo -u www-data php "${REPO}/scripts/fix-sec701-page-filters.php"
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php
sudo -u www-data php -r "define('CLI_SCRIPT', true); require('/var/www/moodle/config.php'); require_once(\$CFG->libdir . '/filterlib.php'); filter_manager::reset_caches(); echo \"filter_cache_reset=1\n\";"
sudo -u www-data php "${REPO}/scripts/diagnose-page-web.php" 4 admin
echo 'fix_sec701_page_filters_complete=1'
