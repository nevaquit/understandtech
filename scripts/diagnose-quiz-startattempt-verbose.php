<?php
/**
 * Simulate quiz preview start with verbose errors (run as www-data on VM).
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);
ini_set('display_errors', '1');
error_reporting(E_ALL);

$cmid = (int) ($argv[1] ?? 60);
$username = $argv[2] ?? 'admin';

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/classes/quiz_settings.php');

global $DB, $USER;

$user = $DB->get_record('user', ['username' => $username, 'deleted' => 0], '*', MUST_EXIST);
$USER = $user;
echo "user={$USER->username} id={$USER->id}\n";

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
echo "quiz={$quiz->name} behaviour={$quiz->preferredbehaviour}\n";

$start = microtime(true);
register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err !== null) {
        echo 'shutdown_error=' . json_encode($err) . "\n";
    }
});

try {
    $quizobj = mod_quiz\quiz_settings::create($cm->instance, $cm->id);
    echo 'quizobj_ok elapsed=' . round(microtime(true) - $start, 2) . "s\n";

    $attempt = quiz_prepare_and_start_new_attempt($quizobj, 1, false, false, [], [], $USER->id);
    echo 'attempt_ok id=' . $attempt->id . ' uniqueid=' . $attempt->uniqueid .
        ' elapsed=' . round(microtime(true) - $start, 2) . "s\n";

    $DB->delete_records('question_usages', ['id' => $attempt->uniqueid]);
    $DB->delete_records('quiz_attempts', ['id' => $attempt->id]);
    echo "cleanup_ok\n";
} catch (Throwable $e) {
    echo 'exception=' . get_class($e) . ' msg=' . $e->getMessage() . "\n";
    if (!empty($e->debuginfo)) {
        echo 'debuginfo=' . $e->debuginfo . "\n";
    }
    echo $e->getTraceAsString() . "\n";
}
