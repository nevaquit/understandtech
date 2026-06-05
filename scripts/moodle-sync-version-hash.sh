#!/usr/bin/env bash
set -euo pipefail

cd /var/www/moodle

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require __DIR__ . '/config.php';
require_once($CFG->libdir . '/upgradelib.php');

$old = $CFG->allversionshash ?? '';
$new = core_component::get_all_versions_hash();
echo "old hash=$old\n";
echo "new hash=$new\n";

set_config('allversionshash', $new);
purge_all_caches();

echo 'needs_upgrading=' . (moodle_needs_upgrading() ? 'yes' : 'no') . "\n";
PHP

sudo -u www-data php admin/cli/upgrade.php --is-pending && echo pending=no || echo pending=yes
sudo -u www-data php admin/cli/purge_caches.php

# Restore PgBouncer transaction pooling.
sudo cp /tmp/pb.full.bak /etc/pgbouncer/pgbouncer.ini 2>/dev/null || sudo cp /tmp/pb.bak /etc/pgbouncer/pgbouncer.ini
sudo sed -i 's/pool_mode=session/pool_mode=transaction/g' /etc/pgbouncer/pgbouncer.ini
sudo sed -i 's/^pool_mode = session$/pool_mode = transaction/' /etc/pgbouncer/pgbouncer.ini
sudo systemctl restart pgbouncer
echo "PgBouncer transaction mode restored"

bash /tmp/verify-certmaster-tables.sh 2>/dev/null | tail -8
bash /tmp/moodle-query-custom-plugins.sh 2>/dev/null | head -12
