#!/usr/bin/env bash
set -uo pipefail

cd /var/www/moodle

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require __DIR__ . '/config.php';
require_once($CFG->libdir . '/upgradelib.php');
require_once($CFG->libdir . '/adminlib.php');

$component = 'block_examreadiness';
$version = 2026060500;

$installed = $DB->get_record('config_plugins', ['plugin' => $component, 'name' => 'version'], IGNORE_MISSING);
echo "before: " . ($installed->value ?? 'missing') . "\n";

if (!$installed) {
    upgrade_plugin_savepoint(true, $version, 'block', 'examreadiness');
    echo "registered via upgrade_plugin_savepoint\n";
} else {
    echo "already registered\n";
}

$after = $DB->get_record('config_plugins', ['plugin' => $component, 'name' => 'version'], IGNORE_MISSING);
echo "after: " . ($after->value ?? 'missing') . "\n";
PHP

sudo -u www-data php admin/cli/upgrade.php --is-pending && echo pending=no || echo pending=yes
sudo -u www-data php admin/cli/purge_caches.php
