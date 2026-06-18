<?php
/**
 * Seed Study Coach (block_studyplan) on My Moodle and cert course dashboards.
 *
 * Idempotent — safe to re-run after deploy or seed.
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/seed-study-plan-block.php
 *
 * Optional env:
 *   STUDYPLAN_USERNAMES=admin,e2etest,nevaquit
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/my/lib.php');

global $DB;

/**
 * @param int $parentcontextid
 * @param string $pagetype
 * @param string $region
 * @param int $weight
 * @param int $certid
 * @param string $title
 * @return int Block instance id
 */
function ut_ensure_studyplan_block(
    int $parentcontextid,
    string $pagetype,
    string $region,
    int $weight,
    int $certid,
    string $title
): int {
    global $DB;

    $config = (object) [
        'certificationid' => $certid,
        'title' => $title,
    ];
    $configdata = base64_encode(serialize($config));

    $existing = $DB->get_records('block_instances', [
        'blockname' => 'studyplan',
        'parentcontextid' => $parentcontextid,
        'pagetypepattern' => $pagetype,
    ], 'id ASC');

    if ($existing) {
        $instance = reset($existing);
        if ($instance->configdata !== $configdata) {
            $instance->configdata = $configdata;
            $instance->timemodified = time();
            $DB->update_record('block_instances', $instance);
            echo "studyplan_block_updated id={$instance->id} context={$parentcontextid} pagetype={$pagetype}\n";
        } else {
            echo "studyplan_block_exists id={$instance->id} context={$parentcontextid} pagetype={$pagetype}\n";
        }
        return (int) $instance->id;
    }

    $instance = (object) [
        'blockname' => 'studyplan',
        'parentcontextid' => $parentcontextid,
        'showinsubcontexts' => 0,
        'requiredbytheme' => 0,
        'pagetypepattern' => $pagetype,
        'subpagepattern' => null,
        'defaultregion' => $region,
        'defaultweight' => $weight,
        'configdata' => $configdata,
        'timecreated' => time(),
        'timemodified' => time(),
    ];
    $instance->id = (int) $DB->insert_record('block_instances', $instance);
    echo "studyplan_block_created id={$instance->id} context={$parentcontextid} pagetype={$pagetype}\n";
    return $instance->id;
}

/**
 * @param string $certshortname
 * @return int|null
 */
function ut_certification_id(string $certshortname): ?int {
    global $DB;

    if (!$DB->get_manager()->table_exists('certmaster_certifications')) {
        return null;
    }

    $id = $DB->get_field('certmaster_certifications', 'id', ['shortname' => $certshortname], IGNORE_MISSING);
    return $id ? (int) $id : null;
}

/**
 * @param int $userid
 * @param int $certid
 * @return void
 */
function ut_warm_study_plan(int $userid, int $certid): void {
    if (!class_exists('\local_certmaster\api')) {
        return;
    }
    \local_certmaster\api::get_user_study_plan($userid, $certid);
}

/** @var array<string, array{course: string, cert: string, title: string}> */
$tracks = [
    'NET009' => [
        'course' => 'NET009',
        'cert' => 'network_plus_n10_009',
        'title' => 'Network+ Study Coach',
    ],
    'SEC701' => [
        'course' => 'SEC701',
        'cert' => 'security_plus_sy0_701',
        'title' => 'Security+ Study Coach',
    ],
    'APLUS' => [
        'course' => 'APLUS',
        'cert' => 'comptia_a_plus',
        'title' => 'A+ Study Coach',
    ],
];

$defaultnames = 'admin,e2etest,nevaquit';
$usernamesraw = getenv('STUDYPLAN_USERNAMES') ?: $defaultnames;
$usernames = array_values(array_unique(array_filter(array_map('trim', explode(',', $usernamesraw)))));

$admin = get_admin();
if ($admin && !in_array($admin->username, $usernames, true)) {
    $usernames[] = $admin->username;
}

echo "=== seed block_studyplan ===\n";

$netcertid = ut_certification_id('network_plus_n10_009');
if ($netcertid === null) {
    echo "cert_missing shortname=network_plus_n10_009\n";
    exit(1);
}

foreach ($usernames as $username) {
    $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
    if (!$user) {
        echo "user_skip missing username={$username}\n";
        continue;
    }

    $context = context_user::instance((int) $user->id);
    ut_ensure_studyplan_block(
        $context->id,
        'my-index',
        'content',
        -1,
        $netcertid,
        'Study Coach'
    );
    ut_warm_study_plan((int) $user->id, $netcertid);
    echo "studyplan_my_dashboard user={$username} id={$user->id}\n";
}

foreach ($tracks as $label => $track) {
    $course = $DB->get_record('course', ['shortname' => $track['course']]);
    if (!$course) {
        echo "course_skip missing shortname={$track['course']}\n";
        continue;
    }

    $certid = ut_certification_id($track['cert']);
    if ($certid === null) {
        echo "cert_skip missing shortname={$track['cert']}\n";
        continue;
    }

    $coursecontext = context_course::instance((int) $course->id);
    ut_ensure_studyplan_block(
        $coursecontext->id,
        'course-view-*',
        'side-pre',
        -1,
        $certid,
        $track['title']
    );
    echo "studyplan_course course={$track['course']} id={$course->id} cert={$certid}\n";
}

purge_all_caches();
echo "studyplan_seed_complete=1\n";
