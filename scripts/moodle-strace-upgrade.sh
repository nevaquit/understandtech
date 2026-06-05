#!/usr/bin/env bash
set -uo pipefail

INI=/etc/pgbouncer/pgbouncer.ini
PB_BACKUP=/tmp/pgbouncer.ini.transaction.bak

sudo cp "$INI" "$PB_BACKUP"
sudo sed -i 's/^pool_mode = transaction$/pool_mode = session/' "$INI"
sudo systemctl restart pgbouncer
echo "pool_mode=$(grep '^pool_mode' "$INI")"

sudo -u www-data strace -f -o /var/www/moodledata/strace-upgrade.log php /var/www/moodle/admin/cli/upgrade.php --non-interactive --allow-unstable 2>/var/www/moodledata/upgrade-stderr.log || true
RC=$?
echo "upgrade exit=$RC"
echo "stderr:"
sudo cat /var/www/moodledata/upgrade-stderr.log 2>/dev/null || true
echo "strace tail:"
sudo tail -30 /var/www/moodledata/strace-upgrade.log 2>/dev/null || true

sudo cp "$PB_BACKUP" "$INI"
sudo systemctl restart pgbouncer

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global $DB;
foreach (['local_certmaster','local_aitutor','block_examreadiness','qbehaviour_certmasterconfidence'] as $p) {
    $rec = $DB->get_record('config_plugins', ['plugin' => $p, 'name' => 'version'], IGNORE_MISSING);
    echo "$p=" . ($rec->value ?? 'missing') . "\n";
}
PHP
