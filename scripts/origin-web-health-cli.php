<?php
// Simulate web bootstrap (no CLI_SCRIPT) and probe dashboard DB paths.

chdir('/var/www/moodle');
require '/var/www/moodle/config.php';

global $DB;

$user = $DB->get_record('user', ['username' => 'e2etest'], 'id,username', MUST_EXIST);
$blocks = $DB->count_records('block_instances');
$course = $DB->get_record('course', ['shortname' => 'SEC701'], 'id,shortname', IGNORE_MISSING);

if (!$user || $blocks < 1) {
    fwrite(STDERR, "origin_health_cli_fail user=" . ($user ? 'ok' : 'missing') . " blocks={$blocks}\n");
    exit(1);
}

echo "origin_web_health_cli_ok user={$user->username} blocks={$blocks}";
if ($course) {
    echo " course={$course->shortname}";
}
echo "\n";
