#!/usr/bin/env bash
set -euo pipefail

echo "=== upgrade lock files ==="
sudo find /var/www/moodledata -maxdepth 3 \( -name '*upgrade*' -o -name 'climaintenance*' \) 2>/dev/null || true

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
foreach (['upgraderunning', 'maintenance_enabled', 'maintenance_message'] as $k) {
    $v = get_config('core', $k);
    echo "$k=" . var_export($v, true) . "\n";
}
PHP

echo "=== clearing stale upgrade lock ==="
sudo -u www-data php /var/www/moodle/admin/cli/maintenance.php --disable 2>/dev/null || true
sudo -u www-data php /var/www/moodle/admin/cli/cfg.php --name=upgraderunning --unset 2>/dev/null || true
sudo rm -f /var/www/moodledata/climaintenance.html /var/www/moodledata/.upgrading 2>/dev/null || true

echo "=== switch to direct postgres ==="
CONFIG=/var/www/moodle/config.php
BACKUP=/tmp/config.pgbouncer.bak
sudo cp "$CONFIG" "$BACKUP"
sudo sed -i "s|127.0.0.1|understandtech-pg-prod.postgres.database.azure.com|g" "$CONFIG"
sudo sed -i "s|'dbport' => 6432|'dbport' => 5432, 'sslmode' => 'require'|g" "$CONFIG"

echo "=== run upgrade ==="
sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --non-interactive --allow-unstable > /var/www/moodledata/upgrade.log 2>&1
RC=$?
echo "upgrade exit=$RC"
cat /var/www/moodledata/upgrade.log

sudo cp "$BACKUP" "$CONFIG"
sudo chown root:www-data "$CONFIG"
sudo chmod 640 "$CONFIG"

sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php

bash /tmp/check-moodle-plugin-versions.sh 2>/dev/null || true
