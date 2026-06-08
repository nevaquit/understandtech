#!/usr/bin/env bash
# Create or reset a Moodle manual-auth user on the VM.
#
# Usage:
#   MOODLE_USER_USERNAME=nevaquit MOODLE_USER_PASSWORD='...' \
#     ./scripts/setup-moodle-app-user-vm.sh
#
# Optional:
#   MOODLE_USER_EMAIL=user@example.com
#   MOODLE_USER_FIRSTNAME=Neva
#   MOODLE_USER_LASTNAME=Quit
#   MOODLE_ENROL_COURSES=SEC701,e2e101   (comma-separated shortnames; default SEC701)
set -euo pipefail

MOODLE_USER_USERNAME="${MOODLE_USER_USERNAME:?Set MOODLE_USER_USERNAME}"
MOODLE_USER_PASSWORD="${MOODLE_USER_PASSWORD:?Set MOODLE_USER_PASSWORD}"
export MOODLE_USER_USERNAME MOODLE_USER_PASSWORD
export MOODLE_USER_EMAIL="${MOODLE_USER_EMAIL:-${MOODLE_USER_USERNAME}@understandtech.app}"
export MOODLE_USER_FIRSTNAME="${MOODLE_USER_FIRSTNAME:-Neva}"
export MOODLE_USER_LASTNAME="${MOODLE_USER_LASTNAME:-Quit}"
export MOODLE_ENROL_COURSES="${MOODLE_ENROL_COURSES:-SEC701}"

sudo -u www-data -E php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/enrol/manual/lib.php');

$username = getenv('MOODLE_USER_USERNAME');
$password = getenv('MOODLE_USER_PASSWORD');
$email = getenv('MOODLE_USER_EMAIL');
$firstname = getenv('MOODLE_USER_FIRSTNAME');
$lastname = getenv('MOODLE_USER_LASTNAME');
$courseshortnames = array_filter(array_map('trim', explode(',', getenv('MOODLE_ENROL_COURSES') ?: '')));

if (!$username || !$password) {
    fwrite(STDERR, "MOODLE_USER_USERNAME and MOODLE_USER_PASSWORD are required\n");
    exit(1);
}

if ($existing = $DB->get_record('user', ['username' => $username, 'deleted' => 0])) {
    $existing->auth = 'manual';
    $existing->confirmed = 1;
    $existing->suspended = 0;
    $existing->mnethostid = $CFG->mnet_localhost_id;
    $existing->email = $email;
    $existing->firstname = $firstname;
    $existing->lastname = $lastname;
    user_update_user($existing, false, false);
    update_internal_user_password($existing, $password);
    $userid = (int) $existing->id;
    echo "user_reset id=$userid username=$username\n";
} else {
    $user = new stdClass();
    $user->username = $username;
    $user->password = $password;
    $user->firstname = $firstname;
    $user->lastname = $lastname;
    $user->email = $email;
    $user->auth = 'manual';
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $userid = user_create_user($user, false, false);
    echo "user_created id=$userid username=$username\n";
}

$enrol = enrol_get_plugin('manual');
foreach ($courseshortnames as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname]);
    if (!$course) {
        echo "course_missing shortname=$shortname\n";
        continue;
    }

    $courseid = (int) $course->id;
    $context = context_course::instance($courseid);
    $instances = enrol_get_instances($courseid, true);
    $manual = null;
    foreach ($instances as $instance) {
        if ($instance->enrol === 'manual') {
            $manual = $instance;
            break;
        }
    }
    if (!$manual) {
        $manualid = $enrol->add_instance((object) ['id' => $courseid]);
        $manual = $DB->get_record('enrol', ['id' => $manualid]);
    }
    if (!is_enrolled($context, $userid)) {
        $enrol->enrol_user($manual, $userid, 5);
        echo "user_enrolled course=$courseid shortname=$shortname\n";
    } else {
        echo "user_already_enrolled course=$courseid shortname=$shortname\n";
    }
}

echo "MOODLE_USER_EMAIL=$email\n";
echo "MOODLE_LOGIN_URL=https://understandtech.app/learn/login/index.php\n";
PHP
