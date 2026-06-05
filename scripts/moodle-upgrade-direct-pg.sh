#!/usr/bin/env bash
# Run Moodle upgrade.php against Azure PostgreSQL directly (bypass PgBouncer transaction pool).
# PgBouncer transaction mode breaks DDL in upgrade_noncore(); restore config.php after success.
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
CONFIG="${MOODLE_DIR}/config.php"
BACKUP=/tmp/config.pgbouncer.bak
LOG="${MOODLE_UPGRADE_LOG:-/var/log/moodle-upgrade.log}"

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

echo "Upgrade complete via direct Postgres."
