#!/usr/bin/env bash
set -uo pipefail

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global $DB;

$plugins = ['local_certmaster', 'local_aitutor', 'block_examreadiness', 'qbehaviour_certmasterconfidence'];
foreach ($plugins as $p) {
    $rec = $DB->get_record('config_plugins', ['plugin' => $p, 'name' => 'version'], IGNORE_MISSING);
    echo "$p version=" . ($rec->value ?? 'missing') . "\n";
}

$tables = ['certmaster_certifications','certmaster_domains','certmaster_objectives'];
foreach ($tables as $t) {
    echo "$t exists=" . ($DB->get_manager()->table_exists($t) ? 'yes' : 'no') . " rows=" . $DB->count_records($t) . "\n";
}
PHP

sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --is-pending >/dev/null 2>&1
echo "is-pending exit=$?"
