#!/usr/bin/env bash
# Create or reset E2E test student + course on production Moodle (run on VM).
# Usage: E2E_PASSWORD='...' ./scripts/setup-e2e-test-user-vm.sh
set -euo pipefail

E2E_PASSWORD="${E2E_PASSWORD:?Set E2E_PASSWORD before running}"
export E2E_PASSWORD

sudo -u www-data -E php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/enrol/manual/lib.php');

$password = getenv('E2E_PASSWORD');
if ($password === false || $password === '') {
    fwrite(STDERR, "E2E_PASSWORD not set in environment\n");
    exit(1);
}

$username = 'e2etest';
$email = 'e2e-test@understandtech.app';

if ($existing = $DB->get_record('user', ['username' => $username, 'deleted' => 0])) {
    $existing->auth = 'manual';
    $existing->confirmed = 1;
    $existing->suspended = 0;
    $existing->mnethostid = $CFG->mnet_localhost_id;
    user_update_user($existing, false, false);
    update_internal_user_password($existing, $password);
    $userid = (int) $existing->id;
    echo "user_reset id=$userid\n";
} else {
    $user = new stdClass();
    $user->username = $username;
    $user->password = $password;
    $user->firstname = 'E2E';
    $user->lastname = 'Student';
    $user->email = $email;
    $user->auth = 'manual';
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $userid = user_create_user($user, false, false);
    echo "user_created id=$userid\n";
}

$shortname = 'e2e101';
$course = $DB->get_record('course', ['shortname' => $shortname]);
if (!$course) {
    $newcourse = new stdClass();
    $newcourse->fullname = 'E2E Test Course';
    $newcourse->shortname = $shortname;
    $newcourse->category = 1;
    $newcourse->format = 'topics';
    $course = create_course($newcourse);
    echo "course_created id={$course->id}\n";
} else {
    echo "course_exists id={$course->id}\n";
}

$courseid = (int) $course->id;
$context = context_course::instance($courseid);
$enrol = enrol_get_plugin('manual');
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
    echo "user_enrolled course=$courseid\n";
} else {
    echo "user_already_enrolled course=$courseid\n";
}

$sec701 = $DB->get_record('course', ['shortname' => 'SEC701']);
if ($sec701) {
    $seccontext = context_course::instance((int) $sec701->id);
    $secinstances = enrol_get_instances((int) $sec701->id, true);
    $secmanual = null;
    foreach ($secinstances as $instance) {
        if ($instance->enrol === 'manual') {
            $secmanual = $instance;
            break;
        }
    }
    if (!$secmanual) {
        $secmanualid = $enrol->add_instance((object) ['id' => (int) $sec701->id]);
        $secmanual = $DB->get_record('enrol', ['id' => $secmanualid]);
    }
    if (!is_enrolled($seccontext, $userid)) {
        $enrol->enrol_user($secmanual, $userid, 5);
        echo "user_enrolled sec701={$sec701->id}\n";
    } else {
        echo "user_already_enrolled sec701={$sec701->id}\n";
    }
    echo "E2E_COURSE_PATH=/course/view.php?id={$sec701->id}\n";
} else {
    echo "E2E_COURSE_PATH=/course/view.php?id=$courseid\n";
}

echo "STAGING_TEST_USER_EMAIL=$email\n";
PHP
