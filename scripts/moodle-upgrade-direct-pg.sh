#!/usr/bin/env bash
# Run Moodle upgrade.php against Azure PostgreSQL directly (bypass PgBouncer transaction pool).
# PgBouncer transaction mode breaks DDL in upgrade_noncore(); restore config.php after success.
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
CONFIG="${MOODLE_DIR}/config.php"
BACKUP=/tmp/config.pgbouncer.bak
LOG="${MOODLE_UPGRADE_LOG:-/tmp/moodle-upgrade.log}"

exec > >(tee -a "$LOG") 2>&1

echo "=== Moodle direct-Postgres upgrade $(date -Is) ==="

sudo cp "$CONFIG" "$BACKUP"
sudo sed -i "s|127.0.0.1|understandtech-pg-prod.postgres.database.azure.com|g" "$CONFIG"
sudo sed -i "s|'dbport' => 6432|'dbport' => 5432, 'sslmode' => 'require'|g" "$CONFIG"

echo "--- config dboptions ---"
sudo grep -A6 dboptions "$CONFIG" || true

cd "$MOODLE_DIR"
if ! sudo -u www-data /usr/bin/php admin/cli/upgrade.php --non-interactive --allow-unstable; then
  echo "Upgrade failed; restoring config"
  sudo cp "$BACKUP" "$CONFIG"
  sudo chown root:www-data "$CONFIG"
  sudo chmod 640 "$CONFIG"
  exit 1
fi

sudo cp "$BACKUP" "$CONFIG"
sudo chown root:www-data "$CONFIG"
sudo chmod 640 "$CONFIG"
sudo -u www-data /usr/bin/php admin/cli/purge_caches.php

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
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

echo "Upgrade complete via direct Postgres."
