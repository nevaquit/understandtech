#!/usr/bin/env bash
set -uo pipefail

INI=/etc/pgbouncer/pgbouncer.ini
PB_BACKUP=/tmp/pgbouncer.ini.transaction.bak

run_upgrade() {
  cd /var/www/moodle
  sudo -u www-data php admin/cli/upgrade.php --non-interactive --allow-unstable
}

echo "=== before ==="
bash /tmp/moodle-query-custom-plugins.sh 2>/dev/null | head -10
sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --is-pending && echo pending=no || echo pending=yes

sudo cp "$INI" "$PB_BACKUP"
sudo sed -i 's/^pool_mode = transaction$/pool_mode = session/' "$INI"
sudo systemctl restart pgbouncer
echo "PgBouncer session mode active"

echo "=== session upgrade ==="
set +e
run_upgrade > /tmp/upgrade-out.txt 2>&1
RC=$?
set -e
cat /tmp/upgrade-out.txt
echo "upgrade exit=$RC"

sudo cp "$PB_BACKUP" "$INI"
sudo systemctl restart pgbouncer
echo "PgBouncer transaction mode restored"

sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php

echo "=== after ==="
bash /tmp/moodle-query-custom-plugins.sh 2>/dev/null | head -15
sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --is-pending && echo pending=no || echo pending=yes

bash /tmp/verify-certmaster-tables.sh 2>/dev/null || true

sudo -u www-data php /var/www/moodle/admin/cli/cfg.php --name=theme
