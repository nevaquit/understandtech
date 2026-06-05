#!/usr/bin/env bash
set -uo pipefail

INI=/etc/pgbouncer/pgbouncer.ini
PB_BACKUP=/tmp/pgbouncer.ini.transaction.bak

sudo cp "$INI" "$PB_BACKUP"
sudo sed -i 's/^pool_mode = transaction$/pool_mode = session/' "$INI"
sudo systemctl restart pgbouncer

cd /var/www/moodle
sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require __DIR__ . '/config.php';
require_once($CFG->libdir . '/upgradelib.php');
require_once($CFG->libdir . '/adminlib.php');

echo "Running upgrade_plugins for block...\n";
try {
    upgrade_plugins('block', false, false);
    echo "block upgrade_plugins done\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
PHP

sudo cp "$PB_BACKUP" "$INI"
sudo systemctl restart pgbouncer

bash /tmp/moodle-query-custom-plugins.sh 2>/dev/null
sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --is-pending && echo pending=no || echo pending=yes
