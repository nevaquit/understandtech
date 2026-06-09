<?php
// CLI probe: www-data chdir + dashboard DB queries.
define('CLI_SCRIPT', true);

chdir('/var/www/moodle');
require '/var/www/moodle/config.php';

global $DB;

$user = $DB->get_record('user', ['username' => 'e2etest'], 'id,username', MUST_EXIST);
$blocks = $DB->count_records('block_instances');

if (!$user || $blocks < 1) {
    fwrite(STDERR, "origin_health_cli_fail blocks={$blocks}\n");
    exit(1);
}

echo "origin_web_health_cli_ok user={$user->username} blocks={$blocks}\n";
