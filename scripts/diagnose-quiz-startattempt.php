<?php
/**
 * Diagnose quiz startattempt prerequisites for SEC701 knowledge checks.
 *
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/diagnose-quiz-startattempt.php [cmid]
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

$cmid = (int) ($argv[1] ?? 60);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/questionlib.php');

global $DB;

echo "=== diagnose quiz cmid={$cmid} ===\n";

try {
    $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
    $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
    echo "quiz_ok id={$quiz->id} name={$quiz->name}\n";
    echo "preferredbehaviour={$quiz->preferredbehaviour}\n";
} catch (Throwable $e) {
    echo 'quiz_load_error=' . $e->getMessage() . "\n";
    exit(1);
}

try {
    $type = question_engine::get_behaviour_type($quiz->preferredbehaviour);
    echo 'behaviour_archetypal=' . ($type->is_archetypal() ? 'yes' : 'no') . "\n";
} catch (Throwable $e) {
    echo 'behaviour_error=' . $e->getMessage() . "\n";
}

$slots = $DB->get_records('quiz_slots', ['quizid' => $quiz->id], 'slot ASC');
echo 'quiz_slots=' . count($slots) . "\n";

try {
    $structure = quiz_structure::create_for_quiz($quiz);
    echo 'quiz_structure_ok pages=' . count($structure->get_pages()) . "\n";
} catch (Throwable $e) {
    echo 'quiz_structure_error=' . $e->getMessage() . "\n";
    if (!empty($e->debuginfo)) {
        echo 'quiz_structure_debug=' . $e->debuginfo . "\n";
    }
}

try {
    $course = get_course($cm->course);
    $quizobj = quiz_settings::create($cm->instance, $cm->id);
    $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
    $quba->set_preferred_behaviour($quiz->preferredbehaviour);
    echo "quba_preferred_behaviour={$quiz->preferredbehaviour}\n";

    $structure = $quizobj->get_structure();
    $slot = 1;
    foreach ($structure->get_questions() as $questiondata) {
        $question = question_bank::load_question($questiondata->questionid, $questiondata->qversion);
        $quba->add_question($question, $slot);
        $slot++;
    }
    question_engine::save_questions_usage_by_activity($quba);
    echo "quba_create_ok questions=" . ($slot - 1) . " usageid={$quba->get_id()}\n";
    question_engine::delete_questions_usage_by_activity($quba->get_id());
    echo "quba_cleanup_ok\n";
} catch (Throwable $e) {
    echo 'quba_simulate_error=' . $e->getMessage() . "\n";
    if (!empty($e->debuginfo)) {
        echo 'quba_simulate_debug=' . $e->debuginfo . "\n";
    }
}

if (class_exists('\local_certmaster\api')) {
    echo "local_certmaster_api=yes\n";
} else {
    echo "local_certmaster_api=missing\n";
}

echo "=== done ===\n";
