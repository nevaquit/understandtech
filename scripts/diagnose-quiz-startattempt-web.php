<?php
/**
 * Simulate quiz preview/start attempt and capture exceptions.
 *
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/diagnose-quiz-startattempt-web.php [cmid] [username]
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

$cmid = (int) ($argv[1] ?? 60);
$username = $argv[2] ?? 'admin';

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/quiz/classes/quiz_settings.php');

global $DB, $USER;

echo "=== web simulate quiz start cmid={$cmid} user={$username} ===\n";

$user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
if (!$user) {
    echo "user_missing\n";
    exit(1);
}
$USER = $user;

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
echo "quiz={$quiz->name} behaviour={$quiz->preferredbehaviour}\n";

require_capability('mod/quiz:preview', context_module::instance($cmid));

try {
    $quizobj = mod_quiz\quiz_settings::create($cm->instance, $cm->id);
    $timenow = time();
    $attempt = quiz_prepare_and_start_new_attempt($quizobj, 1, false, false, [], [], $USER->id);
    echo "preview_attempt_ok id={$attempt->id} uniqueid={$attempt->uniqueid}\n";

    $DB->delete_records('question_usages', ['id' => $attempt->uniqueid]);
    $DB->delete_records('quiz_attempts', ['id' => $attempt->id]);
    echo "attempt_cleanup_ok\n";
} catch (Throwable $e) {
    echo 'attempt_error=' . $e->getMessage() . "\n";
    if (!empty($e->debuginfo)) {
        echo 'attempt_debug=' . $e->debuginfo . "\n";
    }
    echo 'attempt_class=' . get_class($e) . "\n";
}

echo "=== done ===\n";
