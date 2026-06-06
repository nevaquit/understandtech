#!/usr/bin/env bash
# Run Moodle upgrade.php against Azure PostgreSQL directly (bypass PgBouncer transaction pool).
# PgBouncer transaction mode breaks DDL in upgrade_noncore(); restore config.php after success.
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
CONFIG="${MOODLE_DIR}/config.php"
BACKUP=/tmp/config.pgbouncer.bak
LOG="${MOODLE_UPGRADE_LOG:-/tmp/moodle-upgrade.log}"
REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

exec > >(tee -a "$LOG") 2>&1

echo "=== Moodle direct-Postgres upgrade $(date -Is) ==="

if [ -x "${REPO}/scripts/recover-origin-db.sh" ]; then
  bash "${REPO}/scripts/recover-origin-db.sh" || true
fi

if ! sudo -u www-data php -r "define('CLI_SCRIPT',true); require '${CONFIG}'; global \$DB; \$DB->get_record('config',['name'=>'version'],'value'); echo 'ok';" 2>/dev/null | grep -q ok; then
  echo "ERROR: DB still unreachable after recovery" >&2
  exit 1
fi

ensure_pgbouncer_backup() {
  if grep -q '127.0.0.1' "$CONFIG" && grep -q "'dbport' => 6432" "$CONFIG"; then
    cp "$CONFIG" "$BACKUP"
    echo "PgBouncer config backed up to ${BACKUP}"
    return 0
  fi
  if [ -f "$BACKUP" ] && grep -q '127.0.0.1' "$BACKUP" && grep -q "'dbport' => 6432" "$BACKUP"; then
    echo "Using existing PgBouncer backup at ${BACKUP}"
    return 0
  fi
  cp "$CONFIG" "$BACKUP"
  sed -i 's|understandtech-pg-prod.postgres.database.azure.com|127.0.0.1|g' "$BACKUP"
  sed -i "s|'dbport' => 5432, 'sslmode' => 'require'|'dbport' => 6432|g" "$BACKUP"
  sed -i "s|'dbport' => 5432|'dbport' => 6432|g" "$BACKUP"
  sed -i "/'sslmode'/d" "$BACKUP"
  echo "Built PgBouncer backup from direct Postgres config"
}

restore_pgbouncer_config() {
  if [ ! -f "$BACKUP" ]; then
    echo "ERROR: missing PgBouncer backup at ${BACKUP}" >&2
    return 1
  fi
  for bak in /tmp/pgbouncer.ini.transaction.bak /tmp/pb.full.bak /tmp/pb.bak; do
    if [ -f "$bak" ]; then
      cp "$bak" /etc/pgbouncer/pgbouncer.ini
      sed -i 's/pool_mode=session/pool_mode=transaction/g' /etc/pgbouncer/pgbouncer.ini
      sed -i 's/^pool_mode = session$/pool_mode = transaction/' /etc/pgbouncer/pgbouncer.ini
      systemctl restart pgbouncer
      echo "PgBouncer transaction mode restored from ${bak}"
      break
    fi
  done
  cp "$BACKUP" "$CONFIG"
  chown root:www-data "$CONFIG"
  chmod 640 "$CONFIG"
  if ! sudo -u www-data php -r "define('CLI_SCRIPT',true); require '${CONFIG}'; global \$DB; \$DB->get_record('config',['name'=>'version'],'value'); echo 'ok';" 2>/dev/null | grep -q ok; then
    echo "ERROR: PgBouncer config restore failed DB ping" >&2
    return 1
  fi
  echo "PgBouncer config restored and verified"
}

ensure_pgbouncer_backup

sudo sed -i "s|127.0.0.1|understandtech-pg-prod.postgres.database.azure.com|g" "$CONFIG"
sudo sed -i "s|'dbport' => 6432|'dbport' => 5432, 'sslmode' => 'require'|g" "$CONFIG"

echo "--- config dboptions ---"
sudo grep -A6 dboptions "$CONFIG" || true

cd "$MOODLE_DIR"
if ! sudo -u www-data /usr/bin/php admin/cli/upgrade.php --non-interactive --allow-unstable; then
  echo "Upgrade failed; restoring PgBouncer config"
  restore_pgbouncer_config || cp "$BACKUP" "$CONFIG"
  chown root:www-data "$CONFIG"
  chmod 640 "$CONFIG"
  exit 1
fi

if ! restore_pgbouncer_config; then
  echo "ERROR: could not restore PgBouncer after upgrade" >&2
  exit 1
fi

if [ -f "${REPO}/scripts/moodle-sync-version-hash.sh" ]; then
  bash "${REPO}/scripts/moodle-sync-version-hash.sh" || echo "WARN: version hash sync failed"
fi

sudo -u www-data /usr/bin/php admin/cli/purge_caches.php
systemctl reload php8.3-fpm

NGINX_SRC="${REPO}/infrastructure/nginx/understandtech.conf"
NGINX_DST="/etc/nginx/sites-available/understandtech.conf"
if [ -f "$NGINX_SRC" ] && { [ ! -f "$NGINX_DST" ] || ! cmp -s "$NGINX_SRC" "$NGINX_DST"; }; then
  echo "Applying nginx vhost from ${NGINX_SRC}"
  cp "$NGINX_SRC" "$NGINX_DST"
  ln -sf "$NGINX_DST" /etc/nginx/sites-enabled/understandtech.conf
  if [ -f "${REPO}/infrastructure/nginx/understandtech-rate-limit.conf" ]; then
    cp "${REPO}/infrastructure/nginx/understandtech-rate-limit.conf" /etc/nginx/conf.d/understandtech-rate-limit.conf
  fi
  nginx -t
  systemctl reload nginx
  echo "nginx reloaded"
fi

if [ -f "${REPO}/scripts/test-tutor-jwt.php" ]; then
  echo "--- tutor JWT smoke ---"
  sudo -u www-data /usr/bin/php "${REPO}/scripts/test-tutor-jwt.php" --curl || echo "WARN: tutor JWT/worker check failed"
fi



echo "--- SCSS compilation diagnostic ---"
sudo -u www-data /usr/bin/php << 'PHPEOF'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
require_once($CFG->libdir . '/outputlib.php');
try {
  $theme = theme_config::load('understandtech');
  $css = $theme->get_css_content();
  echo 'SCSS OK: ' . strlen($css) . ' bytes' . PHP_EOL;
  echo 'First 100: ' . substr($css, 0, 100) . PHP_EOL;
} catch (\Throwable $e) {
  echo 'SCSS ERROR: ' . $e->getMessage() . PHP_EOL;
  echo 'At: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
PHPEOF
echo "Upgrade complete via direct Postgres."
