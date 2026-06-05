#!/usr/bin/env bash
set -uo pipefail

CONFIG=/var/www/moodle/config.php
BACKUP=/tmp/config.pgbouncer.bak
INI=/etc/pgbouncer/pgbouncer.ini
PB_BACKUP=/tmp/pgbouncer.ini.transaction.bak

run_upgrade() {
  local label="$1"
  echo "=== $label ==="
  set +e
  OUT=$(sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --non-interactive --maintenance 2>&1)
  CODE=$?
  set -e
  echo "$OUT"
  echo "EXIT=$CODE"
  return "$CODE"
}

# Direct Postgres path (bypasses PgBouncer transaction pooling).
sudo cp "$CONFIG" "$BACKUP"
sudo sed -i "s|127.0.0.1|understandtech-pg-prod.postgres.database.azure.com|g" "$CONFIG"
sudo sed -i "s|'dbport' => 6432|'dbport' => 5432, 'sslmode' => 'require'|g" "$CONFIG"

if run_upgrade "direct postgres"; then
  echo "Direct postgres upgrade succeeded."
else
  echo "Direct postgres failed; trying session pooling."
  sudo cp "$BACKUP" "$CONFIG"
  sudo chown root:www-data "$CONFIG"
  sudo chmod 640 "$CONFIG"

  sudo cp "$INI" "$PB_BACKUP"
  sudo sed -i 's/^pool_mode = transaction$/pool_mode = session/' "$INI"
  sudo systemctl restart pgbouncer

  if run_upgrade "pgbouncer session mode"; then
    echo "Session-mode upgrade succeeded."
  else
    echo "Session-mode upgrade failed."
    sudo cp "$PB_BACKUP" "$INI"
    sudo systemctl restart pgbouncer
    exit 1
  fi

  sudo cp "$PB_BACKUP" "$INI"
  sudo systemctl restart pgbouncer
fi

sudo cp "$BACKUP" "$CONFIG"
sudo chown root:www-data "$CONFIG"
sudo chmod 640 "$CONFIG"

sudo -u www-data php /var/www/moodle/admin/cli/maintenance.php --disable 2>/dev/null || true
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php

bash /tmp/check-moodle-plugin-versions.sh 2>/dev/null || true
bash /tmp/verify-certmaster-tables.sh 2>/dev/null || true

echo "Done."
