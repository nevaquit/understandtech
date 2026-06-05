#!/usr/bin/env bash
set -euo pipefail

sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --non-interactive || true

echo "=== plugin status ==="
sudo -u www-data php /var/www/moodle/admin/cli/plugin_list.php 2>&1 | grep -i certmaster || true

echo "=== certmaster tables ==="
sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global $DB;
$tables = [
    'certmaster_certifications',
    'certmaster_domains',
    'certmaster_objectives',
    'certmaster_question_objective',
    'certmaster_attempt_confidence',
    'certmaster_mastery',
];
foreach ($tables as $t) {
    $exists = $DB->get_manager()->table_exists($t) ? 'yes' : 'no';
    echo "$t: $exists\n";
}
PHP

echo "=== pending upgrades ==="
sudo -u www-data php /var/www/moodle/admin/cli/check_database_schema.php 2>&1 | head -5
