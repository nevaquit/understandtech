#!/usr/bin/env bash
set -uo pipefail

cd /var/www/moodle

sudo -u www-data php <<'PHP'
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "step1\n";
define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
require __DIR__ . '/config.php';
echo "step2 config ok\n";
require_once($CFG->libdir.'/adminlib.php');
echo "step3 adminlib ok\n";
require_once($CFG->libdir.'/upgradelib.php');
echo "step4 upgradelib ok\n";
require_once($CFG->libdir.'/clilib.php');
echo "step5 clilib ok\n";
require_once($CFG->libdir.'/environmentlib.php');
echo "step6 environmentlib ok\n";

$result = upgrade_get_env_check();
echo "step7 env check ok\n";

$plugins = core_plugin_manager::instance()->get_plugins();
echo "step8 plugins count=" . count($plugins) . "\n";
PHP
