#!/usr/bin/env bash
set -uo pipefail

CONFIG=/var/www/moodle/config.php
BACKUP=/tmp/config.pgbouncer.bak

if [ -f "$BACKUP" ]; then
  sudo cp "$BACKUP" "$CONFIG"
  sudo chown root:www-data "$CONFIG"
  sudo chmod 640 "$CONFIG"
  echo "Restored config from backup."
fi

sudo grep -E "dbhost|dbport|sslmode" "$CONFIG" | grep -v dbpass

echo "=== test DB via Moodle ==="
sudo -u www-data php /var/www/moodle/admin/cli/check_database_schema.php 2>&1 | head -10
echo "schema exit=$?"

echo "=== test direct postgres config ==="
sudo cp "$CONFIG" /tmp/cfg.test.bak
sudo sed -i "s|127.0.0.1|understandtech-pg-prod.postgres.database.azure.com|g" "$CONFIG"
sudo sed -i "s|'dbport' => 6432|'dbport' => 5432, 'sslmode' => 'require'|g" "$CONFIG"
sudo -u www-data php /var/www/moodle/admin/cli/check_database_schema.php 2>&1 | head -10
echo "direct schema exit=$?"

sudo cp /tmp/cfg.test.bak "$CONFIG"
sudo chown root:www-data "$CONFIG"
sudo chmod 640 "$CONFIG"

echo "=== upgrade with direct postgres (no set -e) ==="
sudo cp "$CONFIG" /tmp/cfg.up.bak
sudo sed -i "s|127.0.0.1|understandtech-pg-prod.postgres.database.azure.com|g" "$CONFIG"
sudo sed -i "s|'dbport' => 6432|'dbport' => 5432, 'sslmode' => 'require'|g" "$CONFIG"

sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --non-interactive --allow-unstable > /var/www/moodledata/upgrade2.log 2>&1
RC=$?
echo "upgrade exit=$RC"
cat /var/www/moodledata/upgrade2.log

sudo cp /tmp/cfg.up.bak "$CONFIG"
sudo chown root:www-data "$CONFIG"
sudo chmod 640 "$CONFIG"

bash /tmp/check-moodle-plugin-versions.sh 2>/dev/null || true
