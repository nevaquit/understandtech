<?php
/**
 * Shared knowledge-check quiz deduplication for certification courses.
 *
 * Requires Moodle bootstrap (config.php loaded).
 *
 * @package    understandtech
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir . '/questionlib.php');

/**
 * Map question ids grouped by objective shortname extracted from question name.
 *
 * @param int $categoryid
 * @param string $objectivepattern PCRE with capture group 1 = objective shortname
 * @return array<string,int[]>
 */
function ut_map_questions_by_objective(int $categoryid, string $objectivepattern): array {
    global $DB;

    /** @var array<string,int[]> $map */
    $map = [];
    $records = $DB->get_records_sql(
        "SELECT q.id, q.name
           FROM {question} q
           JOIN {question_versions} qv ON qv.questionid = q.id
           JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
          WHERE qbe.questioncategoryid = :catid
            AND qv.status = :status
       ORDER BY q.id ASC",
        ['catid' => $categoryid, 'status' => 'ready']
    );
    foreach ($records as $row) {
        if (!preg_match($objectivepattern, (string) $row->name, $m)) {
            continue;
        }
        $key = $m[1];
        if (!isset($map[$key])) {
            $map[$key] = [];
        }
        $map[$key][] = (int) $row->id;
    }
    return $map;
}

/**
 * @param int $questionid
 * @return string
 */
function ut_quiz_question_name(int $questionid): string {
    global $DB;
    return (string) $DB->get_field('question', 'name', ['id' => $questionid], MUST_EXIST);
}

/**
 * @param int $questionid
 * @return string
 */
function ut_quiz_question_text_hash(int $questionid): string {
    global $DB;
    $text = (string) $DB->get_field('question', 'questiontext', ['id' => $questionid], MUST_EXIST);
    $plain = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    return sha1($plain);
}

/**
 * Remove duplicate question bank entries (same question name) in a category.
 *
 * @param int $categoryid
 * @return int Deleted count
 */
function ut_dedupe_question_bank_category(int $categoryid): int {
    global $DB;

    $records = $DB->get_records_sql(
        "SELECT q.id, q.name
           FROM {question} q
           JOIN {question_versions} qv ON qv.questionid = q.id
           JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
          WHERE qbe.questioncategoryid = :catid
            AND qv.status = :status
       ORDER BY q.id ASC",
        ['catid' => $categoryid, 'status' => 'ready']
    );

    /** @var array<string,int> $seen */
    $seen = [];
    $deleted = 0;
    foreach ($records as $row) {
        $name = (string) $row->name;
        if (!isset($seen[$name])) {
            $seen[$name] = (int) $row->id;
            continue;
        }
        question_delete_question((int) $row->id);
        $deleted++;
        echo "question_bank_deleted name={$name} id={$row->id} kept={$seen[$name]}\n";
    }

    echo "question_bank_deduped deleted={$deleted} unique=" . count($seen) . "\n";
    return $deleted;
}

/**
 * Pick one knowledge-check question per objective (practice bank preferred over study guide).
 *
 * @param array<string,int[]> $allbyobjective
 * @param callable $includeobjective function(string $objectiveshort): bool
 * @return int[]
 */
function ut_curate_knowledge_check_questions(array $allbyobjective, callable $includeobjective): array {
    $selected = [];
    $seennames = [];
    $seentext = [];

    ksort($allbyobjective);

    foreach ($allbyobjective as $objshort => $qids) {
        if (!$includeobjective($objshort) || $qids === []) {
            continue;
        }

        $pick = ut_pick_best_knowledge_check_question($qids);
        if ($pick === null) {
            continue;
        }

        $name = ut_quiz_question_name($pick);
        $texthash = ut_quiz_question_text_hash($pick);
        if (isset($seennames[$name]) || isset($seentext[$texthash])) {
            continue;
        }

        $seennames[$name] = true;
        $seentext[$texthash] = true;
        $selected[] = $pick;
    }

    sort($selected);
    return $selected;
}

/**
 * @param int[] $questionids
 * @return int|null
 */
function ut_pick_best_knowledge_check_question(array $questionids): ?int {
    if ($questionids === []) {
        return null;
    }

    $candidates = [];
    foreach ($questionids as $qid) {
        $qid = (int) $qid;
        $name = ut_quiz_question_name($qid);
        $isstudyguide = (bool) preg_match('/_sg\d+\b/', $name);
        $candidates[] = [
            'id' => $qid,
            'name' => $name,
            'studyguide' => $isstudyguide,
        ];
    }

    usort($candidates, static function (array $a, array $b): int {
        if ($a['studyguide'] !== $b['studyguide']) {
            return $a['studyguide'] <=> $b['studyguide'];
        }
        return strcmp($a['name'], $b['name']);
    });

    return $candidates[0]['id'];
}

/**
 * @param int[] $questionids
 * @return int[]
 */
function ut_unique_question_ids_by_name(array $questionids): array {
    $seen = [];
    $out = [];
    foreach ($questionids as $qid) {
        $qid = (int) $qid;
        $name = ut_quiz_question_name($qid);
        if (isset($seen[$name])) {
            continue;
        }
        $seen[$name] = true;
        $out[] = $qid;
    }
    return $out;
}

/**
 * Remove all questions from a quiz using Moodle 4.5 structure API.
 *
 * @param stdClass $quizrecord
 * @param int $courseid
 * @return int Slots removed
 */
function ut_clear_quiz_slots(stdClass $quizrecord, int $courseid): int {
    $cm = get_coursemodule_from_instance('quiz', (int) $quizrecord->id, $courseid, false, MUST_EXIST);
    $quizobj = \mod_quiz\quiz_settings::create((int) $quizrecord->id, (int) $cm->id);
    $structure = $quizobj->get_structure();

    $removed = 0;
    while ($structure->get_question_count() > 0) {
        $structure->remove_slot($structure->get_question_count());
        $removed++;
        $structure = $quizobj->get_structure();
    }

    return $removed;
}

/**
 * Replace quiz slots with a deduplicated question set (idempotent).
 *
 * @param stdClass $quizrecord Quiz row with cmid set
 * @param int[] $targetquestionids
 * @param int $courseid
 * @return array{removed: int, added: int, total: int}
 */
function ut_rebuild_knowledge_check_quiz(stdClass $quizrecord, array $targetquestionids, int $courseid): array {
    $targetquestionids = ut_unique_question_ids_by_name($targetquestionids);

    $removed = ut_clear_quiz_slots($quizrecord, $courseid);

    $added = 0;
    foreach ($targetquestionids as $qid) {
        if (quiz_add_quiz_question($qid, $quizrecord, 0) !== false) {
            $added++;
        }
    }

    if ($added > 0) {
        quiz_update_sumgrades($quizrecord);
    }

    return [
        'removed' => $removed,
        'added' => $added,
        'total' => count($targetquestionids),
    ];
}

/**
 * @param stdClass $course
 * @param int $sectionnum
 * @param string $quizname
 * @param int[] $questionids
 * @param callable $addquiz function(stdClass,int,string,array): void
 * @return void
 */
function ut_sync_knowledge_check_quiz(
    stdClass $course,
    int $sectionnum,
    string $quizname,
    array $questionids,
    callable $addquiz
): void {
    global $DB;

    $questionids = ut_unique_question_ids_by_name($questionids);
    if ($questionids === []) {
        echo "quiz_skip_empty name={$quizname}\n";
        return;
    }

    $quizrecord = $DB->get_record_sql(
        "SELECT q.*
           FROM {quiz} q
           JOIN {course_modules} cm ON cm.instance = q.id
           JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
          WHERE cm.course = :courseid AND q.name = :name",
        ['courseid' => $course->id, 'name' => $quizname]
    );

    if (!$quizrecord) {
        $addquiz($course, $sectionnum, $quizname, $questionids);
        return;
    }

    $cm = get_coursemodule_from_instance('quiz', (int) $quizrecord->id, (int) $course->id, false, MUST_EXIST);
    $quizrecord->cmid = $cm->id;

    $stats = ut_rebuild_knowledge_check_quiz($quizrecord, $questionids, (int) $course->id);
    echo "quiz_reconciled id={$quizrecord->id} name={$quizname} removed={$stats['removed']}"
        . " added={$stats['added']} total={$stats['total']}\n";
}

/**
 * @param int $quizid
 * @param int $courseid
 * @return int Duplicate slots removed
 */
function ut_remove_duplicate_quiz_slots(int $quizid, int $courseid): int {
    global $DB;

    $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('quiz', $quizid, $courseid, false, MUST_EXIST);
    $quizobj = \mod_quiz\quiz_settings::create((int) $quiz->id, (int) $cm->id);
    $structure = $quizobj->get_structure();

    $seennames = [];
    $removed = 0;
    foreach ($structure->get_questions() as $questiondata) {
        $name = ut_quiz_question_name((int) $questiondata->questionid);
        if (!isset($seennames[$name])) {
            $seennames[$name] = true;
            continue;
        }
        $structure->remove_slot((int) $questiondata->slot);
        $removed++;
        $structure = $quizobj->get_structure();
    }

    if ($removed > 0) {
        quiz_update_sumgrades($quiz);
    }

    return $removed;
}

/**
 * Apply Moodle 4.5 quiz form defaults.
 *
 * @param stdClass $quiz
 * @return stdClass
 */
function ut_quiz_apply_defaults(stdClass $quiz): stdClass {
    $defaults = [
        'timeopen' => 0,
        'timeclose' => 0,
        'preferredbehaviour' => 'deferredfeedback',
        'canredoquestions' => 0,
        'attempts' => 0,
        'attemptonlast' => 0,
        'grademethod' => 1,
        'decimalpoints' => 2,
        'questiondecimalpoints' => -1,
        'questionsperpage' => 1,
        'navmethod' => 'free',
        'shuffleanswers' => 1,
        'sumgrades' => 0,
        'grade' => 100,
        'timelimit' => 0,
        'overduehandling' => 'autosubmit',
        'graceperiod' => 0,
        'quizpassword' => '',
        'subnet' => '',
        'browsersecurity' => '',
        'delay1' => 0,
        'delay2' => 0,
        'showuserpicture' => 0,
        'showblocks' => 0,
        'completionattemptsexhausted' => 0,
        'completionpass' => 0,
        'allowofflineattempts' => 0,
        'visibleoncoursepage' => 1,
    ];
    foreach ($defaults as $key => $value) {
        if (!isset($quiz->$key)) {
            $quiz->$key = $value;
        }
    }
    return $quiz;
}

/**
 * Resolve certmasterconfidence when available.
 *
 * @return string
 */
function ut_quiz_preferred_behaviour(): string {
    if (!array_key_exists('certmasterconfidence', core_component::get_plugin_list('qbehaviour'))) {
        return 'deferredfeedback';
    }
    try {
        question_engine::get_behaviour_type('certmasterconfidence');
        return 'certmasterconfidence';
    } catch (Throwable $e) {
        return 'deferredfeedback';
    }
}

/**
 * Create a knowledge-check quiz with deduplicated questions.
 *
 * @param stdClass $course
 * @param int $sectionnum
 * @param string $quizname
 * @param int[] $questionids
 * @param string $introhtml
 * @return void
 */
function ut_add_knowledge_check_quiz(
    stdClass $course,
    int $sectionnum,
    string $quizname,
    array $questionids,
    string $introhtml = '<p>Domain knowledge check — one unique question per objective.</p>'
): void {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/course/modlib.php');

    $questionids = ut_unique_question_ids_by_name($questionids);
    if ($questionids === []) {
        echo "quiz_skip_empty name={$quizname}\n";
        return;
    }

    $exists = $DB->record_exists_sql(
        "SELECT q.id
           FROM {quiz} q
           JOIN {course_modules} cm ON cm.instance = q.id
           JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
          WHERE cm.course = :courseid AND q.name = :name",
        ['courseid' => $course->id, 'name' => $quizname]
    );
    if ($exists) {
        echo "quiz_exists name={$quizname}\n";
        return;
    }

    $targetbehaviour = ut_quiz_preferred_behaviour();
    $quiz = ut_quiz_apply_defaults(new stdClass());
    $quiz->course = $course->id;
    $quiz->name = $quizname;
    $quiz->intro = $introhtml;
    $quiz->introformat = FORMAT_HTML;
    $quiz->module = $DB->get_field('modules', 'id', ['name' => 'quiz']);
    $quiz->modulename = 'quiz';
    $quiz->section = $sectionnum;
    $quiz->visible = 1;
    $quiz->cmidnumber = '';

    try {
        $cm = add_moduleinfo($quiz, $course);
    } catch (Throwable $e) {
        echo "quiz_create_failed name={$quizname} error=" . $e->getMessage() . "\n";
        return;
    }

    $quizrecord = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
    $quizrecord->cmid = $cm->coursemodule;
    $stats = ut_rebuild_knowledge_check_quiz($quizrecord, $questionids, (int) $course->id);

    $quizrecord = $DB->get_record('quiz', ['id' => $quizrecord->id], '*', MUST_EXIST);
    $quizrecord->preferredbehaviour = $targetbehaviour;
    $DB->update_record('quiz', $quizrecord);

    echo "quiz_created id={$quizrecord->id} name={$quizname} questions={$stats['added']}"
        . " behaviour={$targetbehaviour}\n";
}
