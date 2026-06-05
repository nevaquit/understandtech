#!/usr/bin/env bash
set -euo pipefail

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global $DB;

$records = $DB->get_records_select('config_plugins', $DB->sql_like('plugin', ':p', false), ['p' => '%certmaster%'], 'plugin,name');
foreach ($records as $r) {
    echo "{$r->plugin}:{$r->name}={$r->value}\n";
}
echo "--- aitutor ---\n";
$records = $DB->get_records_select('config_plugins', $DB->sql_like('plugin', ':p', false), ['p' => '%aitutor%'], 'plugin,name');
foreach ($records as $r) {
    echo "{$r->plugin}:{$r->name}={$r->value}\n";
}
echo "--- exam ---\n";
$records = $DB->get_records_select('config_plugins', $DB->sql_like('plugin', ':p', false), ['p' => '%exam%'], 'plugin,name');
foreach ($records as $r) {
    echo "{$r->plugin}:{$r->name}={$r->value}\n";
}
PHP
