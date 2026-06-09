#!/usr/bin/env bash
# Post-deploy / post-recovery stabilization: enrolment, theme sync, permissions, PHP-FPM recycle.
# Idempotent — safe after deploy, seed, DB recovery, or rollback.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

echo "=== SEC701 default enrolment ==="
sudo -u www-data php "${REPO}/scripts/enroll-sec701-default-users.php"

echo "=== theme sync ==="
bash "${REPO}/scripts/sync-theme-understandtech-vm.sh"

echo "=== directory permissions + chdir verify ==="
bash "${REPO}/scripts/fix-moodle-dir-permissions-vm.sh"

sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php
# Full restart (never reload) — recycles all PHP-FPM workers after cache/theme changes.
bash "${REPO}/scripts/restart-php-fpm-vm.sh"

echo 'post_deploy_stabilize_complete=1'
