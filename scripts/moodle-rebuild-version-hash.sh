#!/usr/bin/env bash
set -euo pipefail

cd /var/www/moodle

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require __DIR__ . '/config.php';
require_once($CFG->libdir . '/upgradelib.php');
require_once($CFG->libdir . '/adminlib.php');

echo 'before needs_upgrading=' . (moodle_needs_upgrading() ? 'yes' : 'no') . "\n";
echo 'before hash=' . ($CFG->allversionshash ?? 'unset') . "\n";

if (function_exists('update_all_versions_hash')) {
    update_all_versions_hash();
    echo "called update_all_versions_hash()\n";
}

// Moodle 4.x helper on plugin manager.
$pluginman = core_plugin_manager::instance();
if (method_exists($pluginman, 'reset_all_version_hashes')) {
    $pluginman->reset_all_version_hashes();
    echo "called reset_all_version_hashes()\n";
}

// Recompute via upgrade savepoint helper when available.
if (function_exists('upgrade_core_savepoint')) {
    include(__DIR__ . '/version.php');
    upgrade_core_savepoint(true, $version, false);
    echo "called upgrade_core_savepoint(true, $version)\n";
}

purge_all_caches();
echo 'after needs_upgrading=' . (moodle_needs_upgrading() ? 'yes' : 'no') . "\n";
echo 'after hash=' . (get_config('core', 'allversionshash') ?: 'unset') . "\n";
PHP

sudo -u www-data php admin/cli/upgrade.php --is-pending && echo pending=no || echo pending=yes
