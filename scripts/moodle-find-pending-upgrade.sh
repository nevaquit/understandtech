#!/usr/bin/env bash
set -euo pipefail

cd /var/www/moodle

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require __DIR__ . '/config.php';
require_once($CFG->libdir . '/upgradelib.php');
require_once($CFG->libdir . '/adminlib.php');

$pluginman = core_plugin_manager::instance();
foreach ($pluginman->get_plugins() as $type => $plugins) {
    foreach ($plugins as $name => $plugin) {
        if ($plugin->versiondb != $plugin->versiondisk) {
            echo "MISMATCH $type/$name db={$plugin->versiondb} disk={$plugin->versiondisk} status={$plugin->get_status()}\n";
        }
    }
}

echo "core dbversion=" . $CFG->version . " disk=" . $pluginman->get_plugin_info('mod', 'upgrade') . "\n";
echo "core version from code: ";
include(__DIR__ . '/version.php');
echo "$version release=$release branch=$branch\n";
echo "cfg version=$CFG->version release=$CFG->release branch=$CFG->branch\n";
PHP
