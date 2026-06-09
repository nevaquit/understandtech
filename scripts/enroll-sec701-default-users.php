<?php
/**
 * Enrol default users into SEC701 (manual enrolment, student role).
 *
 * Idempotent — safe to re-run after seed, DB recovery, or deploy.
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/enroll-sec701-default-users.php
 *
 * Optional env:
 *   SEC701_ENROL_USERNAMES=admin,e2etest,nevaquit  (comma-separated; default admin,e2etest)
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/enrol/manual/lib.php');

global $DB;

$defaultnames = 'admin,e2etest';
$usernamesraw = getenv('SEC701_ENROL_USERNAMES') ?: $defaultnames;
$usernames = array_values(array_unique(array_filter(array_map('trim', explode(',', $usernamesraw)))));

$admin = get_admin();
if ($admin && !in_array($admin->username, $usernames, true)) {
    $usernames[] = $admin->username;
}

$course = $DB->get_record('course', ['shortname' => 'SEC701']);
if (!$course) {
    echo "course_missing shortname=SEC701\n";
    exit(1);
}

$courseid = (int) $course->id;
echo "=== enroll SEC701 course={$courseid} ===\n";

$studentroleid = (int) $DB->get_field('role', 'id', ['shortname' => 'student']);
if (!$studentroleid) {
    echo "role_missing shortname=student\n";
    exit(1);
}

$enrol = enrol_get_plugin('manual');
if (!$enrol) {
    echo "enrol_plugin_missing manual\n";
    exit(1);
}

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
    $manual = $DB->get_record('enrol', ['id' => $manualid], '*', MUST_EXIST);
    echo "manual_enrol_enabled=1\n";
}

$enrolled = 0;
$skipped = 0;
$missing = 0;

foreach ($usernames as $username) {
    $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
    if (!$user) {
        echo "user_missing username={$username}\n";
        $missing++;
        continue;
    }
    if (is_enrolled($context, (int) $user->id)) {
        echo "user_already_enrolled username={$username} id={$user->id}\n";
        $skipped++;
        continue;
    }
    $enrol->enrol_user($manual, (int) $user->id, $studentroleid);
    echo "user_enrolled username={$username} id={$user->id} role=student\n";
    $enrolled++;
}

echo "enrol_summary enrolled={$enrolled} skipped={$skipped} missing={$missing}\n";
echo "COURSE_PATH=/course/view.php?id={$courseid}\n";
echo "=== enroll complete ===\n";
