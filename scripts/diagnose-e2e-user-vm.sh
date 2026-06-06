#!/usr/bin/env bash
# Diagnose and fix E2E test user login on production VM.
set -euo pipefail

E2E_PASSWORD="${E2E_PASSWORD:?Set E2E_PASSWORD}"
export E2E_PASSWORD

sudo -u www-data -E php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/user/lib.php');

$username = 'e2etest';
$u = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
if (!$u) {
    echo "user_not_found\n";
    exit(1);
}
echo "before id={$u->id} auth={$u->auth} confirmed={$u->confirmed} suspended={$u->suspended}\n";

$u->auth = 'manual';
$u->confirmed = 1;
$u->suspended = 0;
$u->mnethostid = $CFG->mnet_localhost_id;
user_update_user($u, false, false);
echo "user_normalized\n";
PHP

sudo -u www-data php /var/www/moodle/admin/cli/reset_password.php \
    --username=e2etest \
    --password="${E2E_PASSWORD}" \
    --ignore-password-policy

sudo -u www-data -E php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/authlib.php');

$username = 'e2etest';
$password = getenv('E2E_PASSWORD');
if ($password === false || $password === '') {
    echo "env_missing\n";
    exit(1);
}
$user = authenticate_user_login($username, $password);
if ($user) {
    echo "auth_test_ok id={$user->id}\n";
} else {
    echo "auth_test_failed\n";
    exit(2);
}
PHP
