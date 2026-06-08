<?php
/**
 * Simulate SEC701 course view data paths and surface DB/filter failures.
 *
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/diagnose-course-web.php [courseid] [username]
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

$courseid = (int) ($argv[1] ?? 3);
$username = $argv[2] ?? 'admin';

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/filterlib.php');

global $DB, $USER;

echo "=== diagnose course={$courseid} user={$username} ===\n";

$user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
if (!$user) {
    $user = $DB->get_record('user', ['id' => 2]);
}
if (!$user) {
    echo "user_missing\n";
    exit(1);
}
$USER = $user;
echo "user_ok id={$USER->id} username={$USER->username}\n";

try {
    $course = get_course($courseid);
    echo 'course_ok name=' . $course->fullname . "\n";
} catch (Throwable $e) {
    echo 'course_error=' . $e->getMessage() . "\n";
    exit(1);
}

try {
    $context = context_course::instance($courseid);
    echo 'course_context=' . $context->id . "\n";
} catch (Throwable $e) {
    echo 'course_context_error=' . $e->getMessage() . "\n";
}

try {
    $modinfo = get_fast_modinfo($course);
    echo 'modinfo_ok modules=' . count($modinfo->get_cms()) . "\n";
} catch (Throwable $e) {
    echo 'modinfo_error=' . $e->getMessage() . "\n";
    exit(1);
}

if (!empty($course->summary)) {
    try {
        $options = new stdClass();
        $options->context = context_course::instance($courseid);
        $summary = format_text($course->summary, $course->summaryformat, $options);
        echo 'course_summary_ok len=' . strlen($summary) . "\n";
    } catch (Throwable $e) {
        echo 'course_summary_error=' . $e->getMessage() . "\n";
        if (!empty($e->debuginfo)) {
            echo 'course_summary_debug=' . $e->debuginfo . "\n";
        }
    }
} else {
    echo "course_summary_empty\n";
}

$types = [];
foreach ($modinfo->get_cms() as $cm) {
    if ($cm->deletioninprogress) {
        continue;
    }
    $types[$cm->modname] = ($types[$cm->modname] ?? 0) + 1;
    if ($cm->modname === 'quiz') {
        try {
            $quiz = $DB->get_record('quiz', ['id' => $cm->instance], 'id,intro,introformat', MUST_EXIST);
            if (!empty($quiz->intro)) {
                $options = new stdClass();
                $options->context = context_module::instance($cm->id);
                format_text($quiz->intro, $quiz->introformat, $options);
            }
            echo "quiz_intro_ok cmid={$cm->id} name={$cm->name}\n";
        } catch (Throwable $e) {
            echo "quiz_intro_fail cmid={$cm->id} err=" . $e->getMessage() . "\n";
        }
    }
}
echo 'module_counts=' . json_encode($types) . "\n";

try {
    $blocks = $DB->get_records('block_instances', ['parentcontextid' => context_course::instance($courseid)->id]);
    echo 'course_blocks=' . count($blocks) . "\n";
} catch (Throwable $e) {
    echo 'course_blocks_error=' . $e->getMessage() . "\n";
}

echo "=== done ===\n";
