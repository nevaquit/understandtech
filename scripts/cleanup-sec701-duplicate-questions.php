<?php
/**
 * Remove duplicate Security+ SY0-701 question bank entries (keeps lowest id per objective tag).
 *
 * Idempotent. Run on VM before seed when re-imports created duplicates.
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/lib/questionlib.php');

$course = $DB->get_record('course', ['shortname' => 'SEC701']);
if (!$course) {
    echo "error=course_missing\n";
    exit(1);
}

$context = context_course::instance((int) $course->id);
$category = $DB->get_record('question_categories', [
    'contextid' => $context->id,
    'name' => 'Security+ SY0-701',
]);
if (!$category) {
    echo "error=category_missing\n";
    exit(0);
}

$records = $DB->get_records_sql(
    "SELECT q.id, q.name
       FROM {question} q
       JOIN {question_versions} qv ON qv.questionid = q.id
       JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
      WHERE qbe.questioncategoryid = :catid
        AND qv.status = :status
   ORDER BY q.id ASC",
    ['catid' => $category->id, 'status' => 'ready']
);

/** @var array<string,int[]> */
$groups = [];
foreach ($records as $row) {
    if (!preg_match('/\b(sy701_\d+_\d+)\b/', $row->name, $m)) {
        continue;
    }
    $groups[$m[1]][] = (int) $row->id;
}

$deleted = 0;
$kept = 0;
foreach ($groups as $tag => $ids) {
    sort($ids);
    $keepid = array_shift($ids);
    $kept++;
    foreach ($ids as $duplicateid) {
        question_delete_question($duplicateid);
        $deleted++;
        echo "question_deleted tag={$tag} id={$duplicateid} kept={$keepid}\n";
    }
}

echo "questions_kept={$kept} questions_deleted={$deleted} category_total=" . count($records) . "\n";
