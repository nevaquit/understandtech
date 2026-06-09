#!/usr/bin/env bash
# Full origin recovery: permissions, PgBouncer, PHP-FPM pool, Redis, authenticated smoke.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

bash "${REPO}/scripts/apply-php-fpm-pool-vm.sh"
chmod 755 /var/www
chmod 755 /var/www/moodle
bash "${REPO}/scripts/recover-origin-db.sh" || true
bash "${REPO}/scripts/fix-moodle-chdir-quick-vm.sh"
bash "${REPO}/scripts/migrate-moodle-sessions-to-redis-vm.sh" || bash "${REPO}/scripts/fix-redis-session-env-vm.sh" || true
systemctl restart pgbouncer
systemctl restart php8.3-fpm
systemctl reload nginx
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php
sudo -u www-data php /var/www/moodle/admin/cli/maintenance.php --disable || true

bash "${REPO}/scripts/origin-web-health-vm.sh" || bash "${REPO}/scripts/origin-web-health-cli.sh"
echo 'ensure_origin_healthy_complete=1'
