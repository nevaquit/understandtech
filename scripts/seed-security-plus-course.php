<?php
/**
 * Seed CompTIA Security+ SY0-701 course, objectives, lessons, and quizzes on Moodle.
 *
 * Idempotent: safe to re-run; skips existing activities by name.
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/seed-security-plus-course.php
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

$repopath = getenv('PLUGINS_REPO_DIR') ?: '/opt/understandtech-plugins';
chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/page/lib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/format/gift/format.php');
require_once($CFG->dirroot . '/lib/questionlib.php');

/**
 * Build lesson HTML for an SY0-701 objective (original summary aligned to exam objective text).
 *
 * @param string $code Objective code e.g. sy701_1_1
 * @param string $title Official objective title
 * @return string
 */
function security_plus_lesson_html(string $code, string $title): string {
    $displaycode = strtoupper(str_replace('sy701_', '', str_replace('_', '.', $code)));
    $esc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<div class="ut-lesson-content">
<h3>Exam objective {$displaycode}</h3>
<p><strong>{$esc}</strong></p>
<p>This lesson aligns with the CompTIA Security+ SY0-701 exam blueprint (Version 5.0). Focus on
understanding how the concept appears in real enterprise scenarios, not memorizing isolated definitions.</p>
<h4>Study approach</h4>
<ul>
<li>Relate the objective to CIA, defense-in-depth, and least privilege where applicable.</li>
<li>Practice explaining trade-offs (security vs. usability, cost, and availability).</li>
<li>Complete the domain knowledge check quiz after this lesson and rate your confidence honestly.</li>
</ul>
<h4>Next steps</h4>
<p>Use the AI tutor to explore scenario-based questions about this topic. The tutor will guide you
Socratically without revealing assessment answers.</p>
</div>
HTML;
}

/**
 * @param int $courseid
 * @param int $sectionnum
 * @param string $name
 * @return bool True if page already existed
 */
function security_plus_page_exists(int $courseid, int $sectionnum, string $name): bool {
    global $DB;
    $sql = "SELECT p.id
              FROM {page} p
              JOIN {course_modules} cm ON cm.instance = p.id
              JOIN {modules} m ON m.id = cm.module AND m.name = 'page'
              JOIN {course_sections} cs ON cs.id = cm.section
             WHERE cm.course = :courseid
               AND cs.section = :sectionnum
               AND p.name = :name";
    return $DB->record_exists_sql($sql, [
        'courseid' => $courseid,
        'sectionnum' => $sectionnum,
        'name' => $name,
    ]);
}

/**
 * @param stdClass $course
 * @param int $sectionnum
 * @param string $name
 * @param string $html
 * @return void
 */
function security_plus_add_page(stdClass $course, int $sectionnum, string $name, string $html): void {
    global $DB;

    if (security_plus_page_exists((int) $course->id, $sectionnum, $name)) {
        echo "page_exists name={$name} section={$sectionnum}\n";
        return;
    }

    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $sectionnum], '*', MUST_EXIST);

    $page = new stdClass();
    $page->course = $course->id;
    $page->name = $name;
    $page->intro = '';
    $page->introformat = FORMAT_HTML;
    $page->content = $html;
    $page->contentformat = FORMAT_HTML;
    $page->display = PAGE_DISPLAY_OPEN;
    $page->displayoptions = 'a:0:{}';
    $page->revision = 1;
    $page->module = $DB->get_field('modules', 'id', ['name' => 'page']);
    $page->modulename = 'page';
    $page->section = $sectionnum;
    $page->visible = 1;
    $page->cmidnumber = '';

    $cm = add_moduleinfo($page, $course);
    echo "page_created id={$cm->instance} name={$name} section={$sectionnum}\n";
}

/**
 * @param int $contextid
 * @param string $categoryname
 * @return stdClass
 */
function security_plus_get_question_category(int $contextid, string $categoryname): stdClass {
    global $DB;

    $existing = $DB->get_record('question_categories', ['contextid' => $contextid, 'name' => $categoryname]);
    if ($existing) {
        return $existing;
    }

    $parent = question_get_top_category($contextid, true);
    $record = new stdClass();
    $record->name = $categoryname;
    $record->contextid = $contextid;
    $record->info = 'Security+ SY0-701 objective-aligned questions';
    $record->infoformat = FORMAT_HTML;
    $record->stamp = make_unique_id_code();
    $record->parent = $parent->id;
    $record->sortorder = 999;
    $record->idnumber = '';
    $record->id = $DB->insert_record('question_categories', $record);
    return $record;
}

/**
 * @param int $contextid
 * @param stdClass $category
 * @param string $giftpath
 * @return int Number of questions in category after import
 */
function security_plus_import_gift(int $contextid, stdClass $category, string $giftpath): int {
    global $DB;

    if (!is_readable($giftpath)) {
        echo "gift_missing path={$giftpath}\n";
        return 0;
    }

    $context = context::instance_by_id($contextid);
    $before = count(security_plus_category_question_ids((int) $category->id));

    $lines = file($giftpath);
    $qformat = new qformat_gift();
    $qformat->setCategory($category);
    $qformat->setContexts([$context]);
    $qformat->setCourse(null);
    $qformat->setFilename(basename($giftpath));
    $qformat->setRepparse(true);
    $qformat->setStoponerror(false);
    if (!$qformat->importprocess($lines)) {
        echo "gift_import_failed path={$giftpath}\n";
        return $before;
    }

    $after = count(security_plus_category_question_ids((int) $category->id));
    echo "gift_imported path={$giftpath} added=" . ($after - $before) . " total={$after}\n";
    return $after;
}

/**
 * @param int $categoryid
 * @return int[]
 */
function security_plus_category_question_ids(int $categoryid): array {
    global $DB;

    $records = $DB->get_records_sql(
        "SELECT q.id, q.name
           FROM {question} q
           JOIN {question_versions} qv ON qv.questionid = q.id
           JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
          WHERE qbe.questioncategoryid = :catid
            AND qv.status = :status",
        ['catid' => $categoryid, 'status' => 'ready']
    );
    return array_map(static fn($r) => (int) $r->id, $records);
}

/**
 * @param int $categoryid
 * @return array<string,int> Map objective shortname => question id
 */
function security_plus_map_questions_by_objective(int $categoryid): array {
    global $DB;

    $map = [];
    $records = $DB->get_records_sql(
        "SELECT q.id, q.name
           FROM {question} q
           JOIN {question_versions} qv ON qv.questionid = q.id
           JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
          WHERE qbe.questioncategoryid = :catid
            AND qv.status = :status",
        ['catid' => $categoryid, 'status' => 'ready']
    );
    foreach ($records as $row) {
        if (preg_match('/\b(sy701_\d+_\d+)\b/', $row->name, $m)) {
            $map[$m[1]] = (int) $row->id;
        }
    }
    return $map;
}

/**
 * @param stdClass $course
 * @param int $sectionnum
 * @param string $quizname
 * @param int[] $questionids
 * @return void
 */
function security_plus_add_quiz(stdClass $course, int $sectionnum, string $quizname, array $questionids): void {
    global $DB;

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

    $quiz = new stdClass();
    $quiz->course = $course->id;
    $quiz->name = $quizname;
    $quiz->intro = '<p>Knowledge check aligned to SY0-701 domain objectives. Rate your confidence after each question.</p>';
    $quiz->introformat = FORMAT_HTML;
    $quiz->timeopen = 0;
    $quiz->timeclose = 0;
    $quiz->timelimit = 0;
    $quiz->overduehandling = 'autosubmit';
    $quiz->graceperiod = 0;
    $quiz->preferredbehaviour = 'certmasterconfidence';
    $quiz->canredoquestions = 0;
    $quiz->attempts = 0;
    $quiz->attemptonlast = 0;
    $quiz->grademethod = 1;
    $quiz->decimalpoints = 2;
    $quiz->questiondecimalpoints = -1;
    $quiz->reviewattempt = 69888;
    $quiz->reviewcorrectness = 4352;
    $quiz->reviewmarks = 4352;
    $quiz->reviewspecificfeedback = 4352;
    $quiz->reviewgeneralfeedback = 4352;
    $quiz->reviewrightanswer = 4352;
    $quiz->reviewoverallfeedback = 4352;
    $quiz->questionsperpage = 1;
    $quiz->navmethod = QUIZ_NAVMETHOD_FREE;
    $quiz->shuffleanswers = 1;
    $quiz->sumgrades = count($questionids);
    $quiz->grade = 100;
    $quiz->timecreated = time();
    $quiz->timemodified = time();
    $quiz->password = '';
    $quiz->subnet = '';
    $quiz->browsersecurity = '-';
    $quiz->delay1 = 0;
    $quiz->delay2 = 0;
    $quiz->showuserpicture = 0;
    $quiz->showblocks = 0;
    $quiz->completionattemptsexhausted = 0;
    $quiz->completionpass = 0;
    $quiz->allowofflineattempts = 0;
    $quiz->module = $DB->get_field('modules', 'id', ['name' => 'quiz']);
    $quiz->modulename = 'quiz';
    $quiz->section = $sectionnum;
    $quiz->visible = 1;
    $quiz->cmidnumber = '';

    $cm = add_moduleinfo($quiz, $course);
    $quizrecord = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
    $slot = 1;
    foreach ($questionids as $qid) {
        quiz_add_quiz_question($qid, $quizrecord, $slot);
        $slot++;
    }
    echo "quiz_created id={$quizrecord->id} name={$quizname} questions=" . count($questionids) . "\n";
}

/**
 * @param array<string,int> $questionmap
 * @return int Mappings created
 */
function security_plus_link_questions_to_objectives(array $questionmap): int {
    global $DB;

    $linked = 0;
    foreach ($questionmap as $objshort => $questionid) {
        $objective = $DB->get_record('certmaster_objectives', ['shortname' => $objshort]);
        if (!$objective) {
            continue;
        }
        if ($DB->record_exists('certmaster_question_objective', [
            'questionid' => $questionid,
            'objectiveid' => $objective->id,
        ])) {
            continue;
        }
        $DB->insert_record('certmaster_question_objective', (object) [
            'questionid' => $questionid,
            'objectiveid' => $objective->id,
        ]);
        $linked++;
    }
    return $linked;
}

// --- Main ---

echo "=== Security+ SY0-701 course seed ===\n";

$cert = $DB->get_record('certmaster_certifications', ['shortname' => 'security_plus_sy0_701']);
if (!$cert) {
    echo "error=certification_missing\n";
    exit(1);
}

$weights = [
    'general_concepts' => 12.00,
    'threats_vulns' => 22.00,
    'security_architecture' => 18.00,
    'security_operations' => 28.00,
    'program_management' => 20.00,
];
foreach ($weights as $shortname => $weight) {
    $DB->set_field('certmaster_domains', 'blueprint_weight', $weight, [
        'certificationid' => $cert->id,
        'shortname' => $shortname,
    ]);
}
echo "blueprint_weights_updated=1\n";

$csvpath = $repopath . '/content/security-plus/sy0-701-objectives.csv';
if (!is_readable($csvpath)) {
    echo "error=csv_missing path={$csvpath}\n";
    exit(1);
}
$imported = \local_certmaster\csv_importer::import_from_csv(file_get_contents($csvpath));
echo "objectives_imported={$imported}\n";

$objectives = $DB->get_records_sql(
    "SELECT o.id, o.shortname, o.fullname, d.shortname AS domainshort, d.sortorder AS domainsort
       FROM {certmaster_objectives} o
       JOIN {certmaster_domains} d ON d.id = o.domainid
      WHERE d.certificationid = :certid
   ORDER BY d.sortorder ASC, o.shortname ASC",
    ['certid' => $cert->id]
);
echo 'objectives_total=' . count($objectives) . "\n";

$category = $DB->get_record('course_categories', ['name' => 'Certifications']);
if (!$category) {
    $created = core_course_category::create([
        'name' => 'Certifications',
        'description' => 'Industry certification training tracks',
        'descriptionformat' => FORMAT_HTML,
    ]);
    $category = $DB->get_record('course_categories', ['id' => $created->id], '*', MUST_EXIST);
    echo "category_created id={$category->id}\n";
}

$course = $DB->get_record('course', ['shortname' => 'SEC701']);
if (!$course) {
    $newcourse = new stdClass();
    $newcourse->fullname = 'CompTIA Security+ SY0-701';
    $newcourse->shortname = 'SEC701';
    $newcourse->category = $category->id;
    $newcourse->format = 'topics';
    $newcourse->visible = 1;
    $newcourse->summary = '<p>Official CompTIA Security+ SY0-701 blueprint-aligned track. '
        . 'Five domains, 28 exam objectives, lesson pages, and confidence-rated knowledge checks.</p>';
    $newcourse->summaryformat = FORMAT_HTML;
    $course = create_course($newcourse);
    echo "course_created id={$course->id}\n";
} else {
    echo "course_exists id={$course->id}\n";
}

course_create_sections_if_missing($course, 5);

$sectionnames = [
    1 => 'Domain 1: General Security Concepts (12%)',
    2 => 'Domain 2: Threats, Vulnerabilities, and Mitigations (22%)',
    3 => 'Domain 3: Security Architecture (18%)',
    4 => 'Domain 4: Security Operations (28%)',
    5 => 'Domain 5: Security Program Management and Oversight (20%)',
];
foreach ($sectionnames as $num => $label) {
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $num], '*', MUST_EXIST);
    if ((string) $section->name !== $label) {
        $section->name = $label;
        course_update_section($course, $section);
    }
}

$domainsection = [
    'general_concepts' => 1,
    'threats_vulns' => 2,
    'security_architecture' => 3,
    'security_operations' => 4,
    'program_management' => 5,
];

foreach ($objectives as $objective) {
    $sectionnum = $domainsection[$objective->domainshort] ?? 1;
    $pagename = 'SY0-701 ' . strtoupper(str_replace('sy701_', '', str_replace('_', '.', $objective->shortname)))
        . ': ' . $objective->fullname;
    security_plus_add_page(
        $course,
        $sectionnum,
        $pagename,
        security_plus_lesson_html($objective->shortname, $objective->fullname)
    );
}

$context = context_course::instance((int) $course->id);
$qcat = security_plus_get_question_category((int) $context->id, 'Security+ SY0-701');
$giftpath = $repopath . '/content/security-plus/sy0-701-quiz.gift';
security_plus_import_gift((int) $context->id, $qcat, $giftpath);

$questionmap = security_plus_map_questions_by_objective((int) $qcat->id);
$linked = security_plus_link_questions_to_objectives($questionmap);
echo "question_objective_links={$linked}\n";

$domainquestions = [
    1 => [],
    2 => [],
    3 => [],
    4 => [],
    5 => [],
];
foreach ($questionmap as $objshort => $qid) {
    if (preg_match('/sy701_(\d)_/', $objshort, $m)) {
        $domainquestions[(int) $m[1]][] = $qid;
    }
}
foreach ($domainquestions as $domainnum => $qids) {
    if (!$qids) {
        continue;
    }
    sort($qids);
    security_plus_add_quiz(
        $course,
        $domainnum,
        "Domain {$domainnum} Knowledge Check",
        $qids
    );
}

$enrol = enrol_get_plugin('manual');
if ($enrol) {
    $instances = enrol_get_instances($course->id, true);
    $hasmanual = false;
    foreach ($instances as $instance) {
        if ($instance->enrol === 'manual') {
            $hasmanual = true;
            break;
        }
    }
    if (!$hasmanual) {
        $enrol->add_instance($course);
        echo "manual_enrol_enabled=1\n";
    }
}

rebuild_course_cache((int) $course->id, true);
echo "COURSE_PATH=/course/view.php?id={$course->id}\n";
echo "=== seed complete ===\n";
