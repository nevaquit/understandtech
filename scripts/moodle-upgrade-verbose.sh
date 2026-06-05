#!/usr/bin/env bash
set -uo pipefail

CONFIG=/var/www/moodle/config.php
BACKUP=/tmp/config.pgbouncer.bak

sudo cp "$CONFIG" "$BACKUP"
sudo sed -i "s|127.0.0.1|understandtech-pg-prod.postgres.database.azure.com|g" "$CONFIG"
sudo sed -i "s|'dbport' => 6432|'dbport' => 5432, 'sslmode' => 'require'|g" "$CONFIG"

echo "=== upgrade (direct postgres, verbose) ==="
sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --non-interactive --verbose 2>&1
echo "EXIT=$?"

sudo cp "$BACKUP" "$CONFIG"
sudo chown root:www-data "$CONFIG"
sudo chmod 640 "$CONFIG"

echo "=== post-restore upgrade via pgbouncer ==="
sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --non-interactive --verbose 2>&1 || true
echo "PG_EXIT=$?"

bash /tmp/check-moodle-plugin-versions.sh 2>/dev/null || true
