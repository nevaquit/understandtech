#!/usr/bin/env bash
set -uo pipefail

echo "=== clear upgrade locks ==="
sudo -u www-data php /var/www/moodle/admin/cli/maintenance.php --disable 2>/dev/null || true
sudo -u www-data php /var/www/moodle/admin/cli/cfg.php --name=upgraderunning --unset 2>/dev/null || true
sudo rm -f /var/www/moodledata/climaintenance.html /var/www/moodledata/.upgrading 2>/dev/null || true
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php

INI=/etc/pgbouncer/pgbouncer.ini
PB_BACKUP=/tmp/pgbouncer.ini.transaction.bak
sudo cp "$INI" "$PB_BACKUP"
sudo sed -i 's/^pool_mode = transaction$/pool_mode = session/' "$INI"
sudo systemctl restart pgbouncer
echo "session pooling enabled"

cd /var/www/moodle
echo "=== upgrade ==="
set +e
OUT=$(sudo -u www-data php admin/cli/upgrade.php --non-interactive --allow-unstable --maintenance=false 2>&1)
RC=$?
set -e
echo "$OUT"
echo "exit=$RC"

sudo cp "$PB_BACKUP" "$INI"
sudo systemctl restart pgbouncer
echo "transaction pooling restored"

sudo -u www-data php admin/cli/purge_caches.php

echo "=== status ==="
bash /tmp/moodle-query-custom-plugins.sh 2>/dev/null
sudo -u www-data php admin/cli/upgrade.php --is-pending && echo pending=no || echo pending=yes
bash /tmp/verify-certmaster-tables.sh 2>/dev/null | tail -10
