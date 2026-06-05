#!/usr/bin/env bash
set -uo pipefail

CONFIG=/var/www/moodle/config.php
BACKUP=/tmp/config.pgbouncer.bak

sudo cp "$CONFIG" "$BACKUP"
sudo sed -i "s|127.0.0.1|understandtech-pg-prod.postgres.database.azure.com|g" "$CONFIG"
sudo sed -i "s|'dbport' => 6432|'dbport' => 5432, 'sslmode' => 'require'|g" "$CONFIG"

echo "=== direct postgres upgrade ==="
set +e
OUT=$(sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --non-interactive 2>&1)
CODE=$?
set -e
echo "$OUT"
echo "EXIT=$CODE"
if [ -z "$OUT" ]; then
  echo "(no stdout/stderr)"
fi

sudo cp "$BACKUP" "$CONFIG"
sudo chown root:www-data "$CONFIG"
sudo chmod 640 "$CONFIG"

echo "=== pgbouncer upgrade ==="
set +e
OUT2=$(sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --non-interactive 2>&1)
CODE2=$?
set -e
echo "$OUT2"
echo "EXIT=$CODE2"

bash /tmp/check-moodle-plugin-versions.sh 2>/dev/null || true
