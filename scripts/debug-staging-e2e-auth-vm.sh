#!/usr/bin/env bash
# Verify e2etest credentials and session config on staging VM.
set -euo pipefail
sudo -u www-data php <<'PHP'
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
require_once($CFG->libdir . '/authlib.php');
$user = authenticate_user_login('e2etest', 'UtE2eTest2026Secure');
if ($user) {
    echo 'auth_ok id=' . $user->id . ' username=' . $user->username . PHP_EOL;
} else {
    echo 'auth_failed' . PHP_EOL;
}
echo 'wwwroot=' . $CFG->wwwroot . PHP_EOL;
echo 'session_handler=' . $CFG->session_handler_class . PHP_EOL;
echo 'dbsessions=' . (int)!empty($CFG->dbsessions) . PHP_EOL;
PHP
