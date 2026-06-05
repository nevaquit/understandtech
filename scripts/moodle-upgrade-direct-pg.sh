#!/usr/bin/env bash
set -uo pipefail

CONFIG=/var/www/moodle/config.php
BACKUP=/tmp/config.pgbouncer.bak
LOG=/tmp/moodle-upgrade.log

exec > >(tee "$LOG") 2>&1

echo "=== Moodle direct-Postgres upgrade $(date -Is) ==="

sudo cp "$CONFIG" "$BACKUP"
sudo sed -i "s|127.0.0.1|understandtech-pg-prod.postgres.database.azure.com|g" "$CONFIG"
sudo sed -i "s|'dbport' => 6432|'dbport' => 5432, 'sslmode' => 'require'|g" "$CONFIG"

echo "--- config dboptions ---"
sudo grep -A6 dboptions "$CONFIG" || true

if ! sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --non-interactive; then
  echo "Upgrade failed; restoring config"
  sudo cp "$BACKUP" "$CONFIG"
  sudo chown root:www-data "$CONFIG"
  sudo chmod 640 "$CONFIG"
  exit 1
fi

sudo cp "$BACKUP" "$CONFIG"
sudo chown root:www-data "$CONFIG"
sudo chmod 640 "$CONFIG"
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php

echo "Upgrade complete via direct Postgres."
