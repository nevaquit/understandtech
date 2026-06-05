#!/usr/bin/env bash
set -uo pipefail

echo "=== restore pgbouncer config ==="
sudo cp /var/www/moodle/config.php.preinstall /var/www/moodle/config.php
sudo chown root:www-data /var/www/moodle/config.php
sudo chmod 640 /var/www/moodle/config.php
sudo grep -E 'dbhost|dbport' /var/www/moodle/config.php | grep -v dbpass

echo "=== config_plugins rows ==="
sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global $DB;
$rows = $DB->get_records('config_plugins', null, 'plugin,name');
foreach ($rows as $r) {
    echo "{$r->plugin}:{$r->name}={$r->value}\n";
}
PHP

echo "=== certmaster table row counts ==="
sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global $DB;
$tables = ['certmaster_certifications','certmaster_domains','certmaster_objectives','certmaster_question_objective','certmaster_attempt_confidence','certmaster_mastery'];
foreach ($tables as $t) {
    try {
        $c = $DB->count_records($t);
        echo "$t rows=$c\n";
    } catch (Throwable $e) {
        echo "$t error=" . $e->getMessage() . "\n";
    }
}
PHP
