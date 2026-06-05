#!/usr/bin/env bash
set -uo pipefail

cd /var/www/moodle
sudo bash /tmp/moodle-clear-lock-sql.sh >/dev/null

sudo -u www-data php admin/cli/upgrade.php --non-interactive --allow-unstable --maintenance=false > /tmp/up-final.log 2>&1
RC=$?
echo "upgrade exit=$RC"
sudo cat /tmp/up-final.log
sudo -u www-data php admin/cli/upgrade.php --is-pending && echo pending=no || echo pending=yes

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require __DIR__ . '/config.php';
require_once($CFG->libdir . '/upgradelib.php');
echo 'needs_upgrading=' . (moodle_needs_upgrading() ? 'yes' : 'no') . "\n";
PHP

bash /tmp/moodle-query-custom-plugins.sh 2>/dev/null | head -12
