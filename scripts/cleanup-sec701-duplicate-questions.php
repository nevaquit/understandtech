<?php
/**
 * Remove duplicate Security+ SY0-701 question bank entries (same question name).
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
    echo "question_deleted name={$name} id={$row->id} kept={$seen[$name]}\n";
}

echo "questions_deleted={$deleted} unique=" . count($seen) . " category_total=" . count($records) . "\n";
