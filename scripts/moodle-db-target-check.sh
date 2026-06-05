#!/usr/bin/env bash
set -euo pipefail

echo "=== /etc/moodle/env keys ==="
sudo grep -E '^MOODLE_DB_' /etc/moodle/env | cut -d= -f1

echo "=== active config.php connection ==="
sudo grep -E 'dbhost|dbport|dbname|dbuser' /var/www/moodle/config.php | grep -v dbpass

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global $CFG, $DB;
echo "CFG dbhost={$CFG->dbhost} dbport={$CFG->dboptions['dbport']} dbname={$CFG->dbname}\n";
echo "getenv MOODLE_DB_HOST=" . getenv('MOODLE_DB_HOST') . "\n";
$rec = $DB->get_record('config_plugins', ['plugin' => 'local_certmaster', 'name' => 'version'], IGNORE_MISSING);
echo "local_certmaster version=" . ($rec->value ?? 'missing') . "\n";
$cnt = $DB->count_records('config_plugins');
echo "config_plugins total rows=$cnt\n";
PHP

echo "=== preinstall config test ==="
sudo cp /var/www/moodle/config.php /tmp/cfg.active.bak
sudo cp /var/www/moodle/config.php.preinstall /var/www/moodle/config.php
sudo chown root:www-data /var/www/moodle/config.php
sudo chmod 640 /var/www/moodle/config.php

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global $CFG, $DB;
echo "CFG dbhost={$CFG->dbhost} dbport={$CFG->dboptions['dbport']}\n";
$rec = $DB->get_record('config_plugins', ['plugin' => 'local_certmaster', 'name' => 'version'], IGNORE_MISSING);
echo "local_certmaster version=" . ($rec->value ?? 'missing') . "\n";
$cnt = $DB->count_records('config_plugins');
echo "config_plugins total rows=$cnt\n";
PHP

sudo cp /tmp/cfg.active.bak /var/www/moodle/config.php
sudo chown root:www-data /var/www/moodle/config.php
sudo chmod 640 /var/www/moodle/config.php
