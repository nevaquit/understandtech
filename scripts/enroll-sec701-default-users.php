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
require_once($CFG->libdir . '/accesslib.php');

global $DB;

/**
 * Ensure the manual enrolment plugin is enabled site-wide.
 *
 * @return void
 */
function sec701_ensure_manual_enrol_enabled(): void {
    $enabled = array_filter(explode(',', (string) get_config('moodle', 'enrol_plugins_enabled')));
    if (in_array('manual', $enabled, true)) {
        return;
    }
    $enabled[] = 'manual';
    set_config('enrol_plugins_enabled', implode(',', $enabled));
    echo "manual_enrol_plugin_enabled=1\n";
}

/**
 * Verify a user can view the course after enrolment.
 *
 * @param stdClass $user User record.
 * @param context_course $context Course context.
 * @return bool
 */
function sec701_user_can_view_course(stdClass $user, context_course $context): bool {
    return has_capability('moodle/course:view', $context, $user);
}

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

if ((int) $course->visible !== 1) {
    $DB->set_field('course', 'visible', 1, ['id' => $courseid]);
    echo "course_visible_enabled=1\n";
}

$studentroleid = (int) $DB->get_field('role', 'id', ['shortname' => 'student']);
if (!$studentroleid) {
    echo "role_missing shortname=student\n";
    exit(1);
}

sec701_ensure_manual_enrol_enabled();

$enrol = enrol_get_plugin('manual');
if (!$enrol) {
    echo "enrol_plugin_missing manual\n";
    exit(1);
}

$context = context_course::instance($courseid);
$instances = enrol_get_instances($courseid, false);
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
    echo "manual_enrol_instance_created=1\n";
}
if ((int) $manual->status !== ENROL_INSTANCE_ENABLED) {
    $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, ['id' => $manual->id]);
    $manual->status = ENROL_INSTANCE_ENABLED;
    echo "manual_enrol_instance_enabled=1\n";
}

$enrolled = 0;
$skipped = 0;
$missing = 0;
$verifiedusers = [];

foreach ($usernames as $username) {
    $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
    if (!$user) {
        echo "user_missing username={$username}\n";
        $missing++;
        continue;
    }

    $userid = (int) $user->id;
    $hasrole = $DB->record_exists('role_assignments', [
        'contextid' => $context->id,
        'userid' => $userid,
        'roleid' => $studentroleid,
    ]);
    $hasenrolment = $DB->record_exists_sql(
        "SELECT 1
           FROM {user_enrolments} ue
           JOIN {enrol} e ON e.id = ue.enrolid
          WHERE e.courseid = :courseid
            AND ue.userid = :userid
            AND ue.status = :active
            AND e.status = :enabled",
        [
            'courseid' => $courseid,
            'userid' => $userid,
            'active' => ENROL_USER_ACTIVE,
            'enabled' => ENROL_INSTANCE_ENABLED,
        ]
    );

    if (!$hasenrolment || !$hasrole) {
        $enrol->enrol_user($manual, $userid, $studentroleid, 0, 0, ENROL_USER_ACTIVE);
        echo "user_enrolled username={$username} id={$userid} role=student\n";
        $enrolled++;
    } else {
        echo "user_already_enrolled username={$username} id={$userid}\n";
        $skipped++;
    }

    $verifiedusers[] = $user;
}

purge_all_caches();

$failed = 0;
foreach ($verifiedusers as $user) {
    if (!sec701_user_can_view_course($user, $context)) {
        $rolecount = $DB->count_records('role_assignments', [
            'contextid' => $context->id,
            'userid' => (int) $user->id,
        ]);
        echo "capability_missing username={$user->username} id={$user->id} role_assignments={$rolecount}\n";
        $failed++;
    } else {
        echo "capability_ok username={$user->username} id={$user->id}\n";
    }
}

echo "enrol_summary enrolled={$enrolled} skipped={$skipped} missing={$missing} failed={$failed}\n";
echo "COURSE_PATH=/course/view.php?id={$courseid}\n";

if ($failed > 0) {
    echo "=== enroll failed ===\n";
    exit(1);
}

echo "=== enroll complete ===\n";
