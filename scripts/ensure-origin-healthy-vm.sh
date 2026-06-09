#!/usr/bin/env bash
# Full idempotent origin recovery: DB, PHP-FPM pool, permissions, enrolment, theme, strict health.
# Disables maintenance mode ONLY when verify-moodle-web-health.sh passes.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

echo "=== sync gha-runner sudoers (restart-only policy) ==="
bash "${REPO}/scripts/sync-sudoers-vm.sh"

echo "=== apply PHP-FPM pool ==="
bash "${REPO}/scripts/apply-php-fpm-pool-vm.sh"

echo "=== recover DB connectivity ==="
bash "${REPO}/scripts/recover-origin-db.sh" || true

echo "=== migrate / fix Redis sessions ==="
bash "${REPO}/scripts/migrate-moodle-sessions-to-redis-vm.sh" \
  || bash "${REPO}/scripts/fix-redis-session-env-vm.sh" || true

echo "=== restart stack ==="
systemctl restart pgbouncer
systemctl restart php8.3-fpm
systemctl reload nginx

echo "=== post-deploy stabilize (enrol, theme, permissions) ==="
bash "${REPO}/scripts/post-deploy-stabilize-vm.sh"

echo "=== strict web health ==="
bash "${REPO}/scripts/verify-moodle-web-health.sh"

sudo -u www-data php /var/www/moodle/admin/cli/maintenance.php --disable
echo 'ensure_origin_healthy_complete=1'
