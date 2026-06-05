#!/usr/bin/env bash
set -euo pipefail

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global $DB;

foreach (['upgraderunning', 'maintenance_enabled', 'maintenance_message'] as $name) {
    $val = get_config('core', $name);
    echo "core.$name=" . var_export($val, true) . "\n";
}

// Force-clear stale upgrade lock.
unset_config('upgraderunning');
set_config('maintenance_enabled', '0');
set_config('maintenance_message', '');

foreach (['upgraderunning', 'maintenance_enabled'] as $name) {
    $val = get_config('core', $name);
    echo "after core.$name=" . var_export($val, true) . "\n";
}
PHP

sudo rm -f /var/www/moodledata/climaintenance.html 2>/dev/null || true
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php

cd /var/www/moodle
sudo -u www-data php admin/cli/upgrade.php --is-pending && echo pending=no || echo pending=yes
