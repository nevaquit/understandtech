#!/usr/bin/env bash
set -euo pipefail

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global $CFG, $DB;

$keys = ['version', 'release', 'branch', 'maturity', 'allversionshash'];
foreach ($keys as $k) {
    echo "$k=" . ($CFG->$k ?? 'unset') . "\n";
}

$plugins = ['local_certmaster', 'local_aitutor', 'block_examreadiness', 'qbehaviour_certmasterconfidence'];
foreach ($plugins as $p) {
    $version = get_config('core', 'plugins_' . str_replace('/', '_', $p) . '_version');
    $rec = $DB->get_record('config_plugins', ['plugin' => $p, 'name' => 'version'], IGNORE_MISSING);
    echo "$p version=" . ($rec->value ?? 'missing') . "\n";
}

$pending = $DB->get_records_select('config_plugins', "name LIKE '%_version'", null, 'plugin', 'plugin,name,value');
echo "config_plugins count=" . count($pending) . "\n";
PHP
