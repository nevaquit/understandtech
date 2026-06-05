#!/usr/bin/env bash
set -euo pipefail

cd /var/www/moodle

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require __DIR__ . '/config.php';
require_once($CFG->libdir . '/upgradelib.php');

echo 'needs_upgrading=' . (moodle_needs_upgrading() ? 'yes' : 'no') . "\n";
echo 'allversionshash=' . ($CFG->allversionshash ?? 'unset') . "\n";

$pluginman = core_plugin_manager::instance();
$pluginman->reset_caches();
$updates = $pluginman->get_plugins_with_pending_upgrades();
echo 'pending_plugins=' . count($updates) . "\n";
foreach ($updates as $component => $info) {
    echo "  $component\n";
}
PHP
