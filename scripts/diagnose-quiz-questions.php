<?php
/**
 * Load each quiz question individually to find broken slots.
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

$cmid = (int) ($argv[1] ?? 60);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/classes/quiz_settings.php');

global $DB;

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$quizobj = mod_quiz\quiz_settings::create($cm->instance, $cm->id);
$structure = $quizobj->get_structure();

echo "quiz={$quiz->name} slots=" . count($structure->get_slots()) . "\n";

foreach ($structure->get_slots() as $slot) {
    $questiondata = $structure->get_question_in_slot($slot->slot);
    try {
        $question = question_bank::load_question($questiondata->questionid, $questiondata->qversion);
        echo "slot_ok slot={$slot->slot} qid={$questiondata->questionid} type={$question->get_type_name()}\n";
    } catch (Throwable $e) {
        echo "slot_fail slot={$slot->slot} qid={$questiondata->questionid} err=" . $e->getMessage() . "\n";
    }
}

echo "=== done ===\n";
