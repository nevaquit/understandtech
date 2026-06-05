#!/usr/bin/env bash
set -euo pipefail

INI=/etc/pgbouncer/pgbouncer.ini
BACKUP=/tmp/pgbouncer.ini.transaction.bak

echo "=== Switch PgBouncer to session pooling ==="
sudo cp "$INI" "$BACKUP"
sudo sed -i 's/^pool_mode = transaction$/pool_mode = session/' "$INI"
sudo grep '^pool_mode' "$INI"
sudo systemctl restart pgbouncer
systemctl is-active pgbouncer

echo "=== Run Moodle upgrade via PgBouncer (session mode) ==="
if ! sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --non-interactive; then
  echo "Upgrade failed in session mode; reverting PgBouncer"
  sudo cp "$BACKUP" "$INI"
  sudo systemctl restart pgbouncer
  exit 1
fi

echo "=== Revert PgBouncer to transaction pooling ==="
sudo cp "$BACKUP" "$INI"
sudo grep '^pool_mode' "$INI"
sudo systemctl restart pgbouncer
systemctl is-active pgbouncer

sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php
echo "Session-mode upgrade complete; PgBouncer restored."
