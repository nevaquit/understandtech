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
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/format/gift/format.php');
require_once($CFG->dirroot . '/lib/questionlib.php');
require_once($CFG->libdir . '/filterlib.php');
require_once(__DIR__ . '/lib/moodle-cert-practice-exam.php');
require_once(__DIR__ . '/lib/ctfflag_seed.php');

$admin = get_admin();
\core\session\manager::set_user($admin);

/**
 * Build fallback lesson HTML when no CyberKraft content file exists.
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
 * Load lesson HTML from CyberKraft-derived content file when present.
 *
 * @param string $repopath Repository root on VM
 * @param string $code Objective shortname e.g. sy701_1_1
 * @param string $title Objective title for fallback
 * @return string
 */
function security_plus_load_lesson_html(string $repopath, string $code, string $title): string {
    $path = $repopath . '/content/security-plus/lessons/' . $code . '.html';
    if (is_readable($path)) {
        $html = file_get_contents($path);
        if ($html !== false && trim($html) !== '') {
            return $html;
        }
    }
    $diagrampath = $repopath . '/content/security-plus/diagrams/' . $code . '.html';
    $base = security_plus_lesson_html($code, $title);
    if (is_readable($diagrampath)) {
        $diagram = trim((string) file_get_contents($diagrampath));
        if ($diagram !== '') {
            return str_replace(
                '</div>' . "\n" . '<h4>Next steps</h4>',
                $diagram . "\n" . '</div>' . "\n" . '<h4>Next steps</h4>',
                $base
            );
        }
    }
    return $base;
}

/**
 * Load sub-lesson HTML (_scenario or _exam suffix).
 *
 * @param string $repopath
 * @param string $code e.g. sy701_1_1_scenario
 * @param string $title
 * @return string
 */
function security_plus_load_sublesson_html(string $repopath, string $code, string $title): string {
    $path = $repopath . '/content/security-plus/lessons/' . $code . '.html';
    if (is_readable($path)) {
        $html = file_get_contents($path);
        if ($html !== false && trim($html) !== '') {
            return $html;
        }
    }
    return security_plus_lesson_html($code, $title);
}

/**
 * Build Moodle page title for a sub-lesson.
 *
 * @param string $objectiveshortname e.g. sy701_1_1
 * @param string $suffix _scenario or _exam
 * @param string $fullname Objective full name
 * @return string
 */
function security_plus_sublesson_pagename(string $objectiveshortname, string $suffix, string $fullname): string {
    $code = strtoupper(str_replace('sy701_', 'SY0-701 ', str_replace('_', '.', $objectiveshortname)));
    $label = $suffix === '_scenario' ? 'Scenario Study' : 'Exam Focus';
    return "{$code} — {$label}: {$fullname}";
}

/**
 * @param int $courseid
 * @param int $sectionnum
 * @param string $name
 * @return stdClass|null Page row
 */
function security_plus_find_page(int $courseid, int $sectionnum, string $name): ?stdClass {
    global $DB;
    $sql = "SELECT p.*
              FROM {page} p
              JOIN {course_modules} cm ON cm.instance = p.id
              JOIN {modules} m ON m.id = cm.module AND m.name = 'page'
              JOIN {course_sections} cs ON cs.id = cm.section
             WHERE cm.course = :courseid
               AND cs.section = :sectionnum
               AND p.name = :name";
    return $DB->get_record_sql($sql, [
        'courseid' => $courseid,
        'sectionnum' => $sectionnum,
        'name' => $name,
    ]) ?: null;
}

/**
 * @param int $courseid
 * @param int $sectionnum
 * @param string $name
 * @return bool True if page already existed
 */
function security_plus_page_exists(int $courseid, int $sectionnum, string $name): bool {
    return security_plus_find_page($courseid, $sectionnum, $name) !== null;
}

/**
 * Update page content through Moodle APIs so filter/modinfo caches stay valid.
 *
 * @param stdClass $course
 * @param stdClass $page
 * @param string $name
 * @param string $html
 * @return void
 */
/**
 * Disable text filters on lesson page modules (prevents filter MUC DB errors on large HTML).
 *
 * @param stdClass $course
 * @return void
 */
function security_plus_disable_page_module_filters(stdClass $course): void {
    require_once(__DIR__ . '/lib/moodle-cert-course-filters.php');
    ut_disable_cert_course_text_filters($course, false);
}

/**
 * Update page content through Moodle APIs so filter/modinfo caches stay valid.
 *
 * @param stdClass $course
 * @param stdClass $page
 * @param string $name
 * @param string $html
 * @return void
 */
function security_plus_update_page_content(stdClass $course, stdClass $page, string $name, string $html): void {
    global $DB;

    $update = new stdClass();
    $update->id = (int) $page->id;
    $update->name = $name;
    $update->content = $html;
    $update->contentformat = FORMAT_HTML;
    $update->timemodified = time();
    $update->revision = (int) $page->revision + 1;
    $DB->update_record('page', $update);
}

/**
 * Create or update a lesson page with CyberKraft content.
 *
 * @param stdClass $course
 * @param int $sectionnum
 * @param string $name
 * @param string $html
 * @return void
 */
function security_plus_upsert_page(stdClass $course, int $sectionnum, string $name, string $html): void {
    $existing = security_plus_find_page((int) $course->id, $sectionnum, $name);
    if ($existing) {
        $hasdiagram = strpos($html, 'ut-lesson-diagram') !== false;
        $storedhasdiagram = strpos((string) $existing->content, 'ut-lesson-diagram') !== false;
        $needsdiagramsync = $hasdiagram && (
            !$storedhasdiagram
            || strpos((string) $existing->content, 'ut-svg-figure') === false && strpos($html, 'ut-svg-figure') !== false
            || strpos((string) $existing->content, 'diagram-title') === false
        );
        if ($existing->content !== $html || $needsdiagramsync) {
            security_plus_update_page_content($course, $existing, $name, $html);
            echo "page_updated id={$existing->id} name={$name} section={$sectionnum}\n";
        } else {
            echo "page_unchanged id={$existing->id} name={$name} section={$sectionnum}\n";
        }
        return;
    }

    security_plus_add_page($course, $sectionnum, $name, $html);
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
    $page->display = 0; // PAGE_DISPLAY_OPEN
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
 * Find an existing question category by name and optional idnumber.
 *
 * @param int $contextid
 * @param string $categoryname
 * @param string|null $idnumber
 * @param int|null $parentid
 * @return stdClass|null
 */
function security_plus_find_question_category(
    int $contextid,
    string $categoryname,
    ?string $idnumber = null,
    ?int $parentid = null
): ?stdClass {
    global $DB;

    $conditions = ['contextid' => $contextid, 'name' => $categoryname];
    if ($parentid !== null) {
        $conditions['parent'] = $parentid;
    }
    $existing = $DB->get_record('question_categories', $conditions, '*', IGNORE_MULTIPLE);
    if ($existing) {
        return $existing;
    }

    if ($idnumber !== null && $idnumber !== '') {
        $existing = $DB->get_record(
            'question_categories',
            ['contextid' => $contextid, 'idnumber' => $idnumber],
            '*',
            IGNORE_MULTIPLE
        );
        if ($existing) {
            return $existing;
        }
    }

    return null;
}

/**
 * @param int $contextid
 * @param string $categoryname
 * @param string $idnumber Stable idnumber for idempotent lookup (PostgreSQL rejects duplicate '').
 * @return stdClass
 */
function security_plus_get_question_category(int $contextid, string $categoryname, string $idnumber = 'sec701-main'): stdClass {
    global $DB;

    $existing = security_plus_find_question_category($contextid, $categoryname, $idnumber);
    if ($existing) {
        return $existing;
    }

    $parent = question_get_top_category($contextid, true);
    $manager = new \core_question\category_manager();
    $categoryid = $manager->add_category(
        "{$parent->id},{$contextid}",
        $categoryname,
        'Security+ SY0-701 objective-aligned questions',
        FORMAT_HTML,
        $idnumber
    );
    return $DB->get_record('question_categories', ['id' => $categoryid], '*', MUST_EXIST);
}

/**
 * Child question category under an existing parent (practice exam banks).
 *
 * @param stdClass $parentcategory
 * @param string $categoryname
 * @param string $idnumber Stable idnumber for idempotent lookup.
 * @return stdClass
 */
function security_plus_get_child_question_category(stdClass $parentcategory, string $categoryname, string $idnumber): stdClass {
    global $DB;

    $existing = security_plus_find_question_category(
        (int) $parentcategory->contextid,
        $categoryname,
        $idnumber,
        (int) $parentcategory->id
    );
    if ($existing) {
        return $existing;
    }

    $manager = new \core_question\category_manager();
    $categoryid = $manager->add_category(
        "{$parentcategory->id},{$parentcategory->contextid}",
        $categoryname,
        'Security+ SY0-701 practice exam bank',
        FORMAT_HTML,
        $idnumber
    );
    return $DB->get_record('question_categories', ['id' => $categoryid], '*', MUST_EXIST);
}

function security_plus_count_tagged_questions(int $categoryid): int {
    global $DB;
    $count = 0;
    foreach (security_plus_category_question_ids($categoryid) as $qid) {
        $name = (string) $DB->get_field('question', 'name', ['id' => $qid]);
        if (preg_match('/\b(sy701_\d+_\d+)\b/', $name)) {
            $count++;
        }
    }
    return $count;
}

/**
 * Count ::sy701_* questions declared in a GIFT file.
 *
 * @param string $giftpath
 * @return int
 */
function security_plus_gift_expected_count(string $giftpath): int {
    if (!is_readable($giftpath)) {
        return 28;
    }
    $content = file_get_contents($giftpath);
    if ($content === false) {
        return 28;
    }
    return preg_match_all('/::[^:\n]*sy701_\d+_\d+/m', $content) ?: 28;
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

    $expected = security_plus_gift_expected_count($giftpath);
    $tagged = security_plus_count_tagged_questions((int) $category->id);
    if ($tagged >= 28 && $expected <= 28) {
        echo "gift_skip_existing tagged={$tagged} expected={$expected} total="
            . count(security_plus_category_question_ids((int) $category->id)) . "\n";
        return count(security_plus_category_question_ids((int) $category->id));
    }
    if ($expected > 28 && $tagged >= ($expected + 28)) {
        echo "gift_skip_existing tagged={$tagged} expected_extra={$expected} total="
            . count(security_plus_category_question_ids((int) $category->id)) . "\n";
        return count(security_plus_category_question_ids((int) $category->id));
    }

    $context = context::instance_by_id($contextid);
    $before = count(security_plus_category_question_ids((int) $category->id));

    $qformat = new qformat_gift();
    $qformat->setCategory($category);
    $qformat->setContexts([$context]);
    $qformat->setFilename($giftpath);
    $qformat->setStoponerror(false);
    if (!$qformat->importprocess()) {
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
        if (!preg_match('/\b(sy701_\d+_\d+)\b/', $row->name, $m)) {
            continue;
        }
        $key = $m[1];
        $qid = (int) $row->id;
        if (!isset($map[$key]) || $qid < $map[$key]) {
            $map[$key] = $qid;
        }
    }
    return $map;
}

/**
 * @param int $categoryid
 * @return array<string,int[]> Map objective shortname => question ids
 */
function security_plus_map_all_questions_by_objective(int $categoryid): array {
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
        if (!preg_match('/\b(sy701_\d+_\d+)\b/', $row->name, $m)) {
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
 * @param int $categoryid
 * @return array<int,int[]> Map domain number => question ids
 */
function security_plus_map_questions_by_domain(int $categoryid): array {
    $all = security_plus_map_all_questions_by_objective($categoryid);
    $domains = [
        1 => [],
        2 => [],
        3 => [],
        4 => [],
        5 => [],
    ];
    foreach ($all as $objshort => $qids) {
        if (preg_match('/sy701_(\d)_/', $objshort, $m)) {
            $domains[(int) $m[1]] = array_merge($domains[(int) $m[1]], $qids);
        }
    }
    foreach ($domains as $num => $qids) {
        $qids = array_values(array_unique($qids));
        sort($qids);
        $domains[$num] = $qids;
    }
    return $domains;
}

/**
 * Apply Moodle 4.5 quiz form defaults (matches mod_quiz_generator).
 *
 * @param stdClass $quiz
 * @return stdClass
 */
function security_plus_quiz_apply_defaults(stdClass $quiz): stdClass {
    $defaults = [
        'timeopen' => 0,
        'timeclose' => 0,
        'preferredbehaviour' => 'deferredfeedback',
        'canredoquestions' => 0,
        'attempts' => 0,
        'attemptonlast' => 0,
        'grademethod' => QUIZ_GRADEHIGHEST,
        'decimalpoints' => 2,
        'questiondecimalpoints' => -1,
        'attemptduring' => 1,
        'correctnessduring' => 1,
        'maxmarksduring' => 1,
        'marksduring' => 1,
        'specificfeedbackduring' => 1,
        'generalfeedbackduring' => 1,
        'rightanswerduring' => 1,
        'overallfeedbackduring' => 0,
        'attemptimmediately' => 1,
        'correctnessimmediately' => 1,
        'maxmarksimmediately' => 1,
        'marksimmediately' => 1,
        'specificfeedbackimmediately' => 1,
        'generalfeedbackimmediately' => 1,
        'rightanswerimmediately' => 1,
        'overallfeedbackimmediately' => 1,
        'attemptopen' => 1,
        'correctnessopen' => 1,
        'maxmarksopen' => 1,
        'marksopen' => 1,
        'specificfeedbackopen' => 1,
        'generalfeedbackopen' => 1,
        'rightansweropen' => 1,
        'overallfeedbackopen' => 1,
        'attemptclosed' => 1,
        'correctnessclosed' => 1,
        'maxmarksclosed' => 1,
        'marksclosed' => 1,
        'specificfeedbackclosed' => 1,
        'generalfeedbackclosed' => 1,
        'rightanswerclosed' => 1,
        'overallfeedbackclosed' => 1,
        'questionsperpage' => 1,
        'navmethod' => QUIZ_NAVMETHOD_FREE,
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
 * @param stdClass $course
 * @param int $sectionnum
 * @param string $quizname
 * @param int[] $questionids
 * @return void
 */
function security_plus_add_quiz(stdClass $course, int $sectionnum, string $quizname, array $questionids): void {
    global $DB;

    if (!$questionids) {
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

    $targetbehaviour = 'deferredfeedback';
    if (array_key_exists('certmasterconfidence', core_component::get_plugin_list('qbehaviour'))) {
        try {
            question_engine::get_behaviour_type('certmasterconfidence');
            $targetbehaviour = 'certmasterconfidence';
        } catch (Throwable $e) {
            echo "quiz_warn behaviour_unavailable fallback=deferredfeedback name={$quizname}\n";
        }
    } else {
        echo "quiz_warn behaviour_missing fallback=deferredfeedback name={$quizname}\n";
    }

    $quiz = security_plus_quiz_apply_defaults(new stdClass());
    $quiz->course = $course->id;
    $quiz->name = $quizname;
    $quiz->intro = '<p>Knowledge check aligned to SY0-701 domain objectives. Rate your confidence after each question.</p>';
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

    $added = 0;
    foreach ($questionids as $qid) {
        try {
            if (quiz_add_quiz_question($qid, $quizrecord, 0) === false) {
                echo "quiz_question_exists quiz={$quizname} qid={$qid}\n";
                continue;
            }
            $added++;
        } catch (Throwable $e) {
            echo "quiz_question_failed quiz={$quizname} qid={$qid} error=" . $e->getMessage() . "\n";
            break;
        }
    }

    if ($added === 0) {
        echo "quiz_no_questions_added name={$quizname} deleting_empty=1\n";
        course_delete_module($cm->coursemodule);
        return;
    }

    $quizrecord = $DB->get_record('quiz', ['id' => $quizrecord->id], '*', MUST_EXIST);
    $quizrecord->preferredbehaviour = $targetbehaviour;
    $DB->update_record('quiz', $quizrecord);
    quiz_update_sumgrades($quizrecord);

    echo "quiz_created id={$quizrecord->id} name={$quizname} questions={$added} behaviour={$targetbehaviour}\n";
}

/**
 * Add missing questions to an existing domain quiz.
 *
 * @param stdClass $course
 * @param int $sectionnum
 * @param string $quizname
 * @param int[] $questionids
 * @return void
 */
function security_plus_sync_quiz(stdClass $course, int $sectionnum, string $quizname, array $questionids): void {
    require_once(__DIR__ . '/lib/moodle-cert-quiz-dedup.php');
    ut_sync_knowledge_check_quiz($course, $sectionnum, $quizname, $questionids, 'security_plus_add_quiz');
}

/**
 * Import a GIFT file into a question category (always attempts import).
 *
 * @param int $contextid
 * @param stdClass $category
 * @param string $giftpath
 * @return int Questions in category after import
 */
function security_plus_import_gift_unconditional(
    int $contextid,
    stdClass $category,
    string $giftpath,
    ?string $skipprefix = null,
    int $skiptarget = 90
): int {
    global $DB;

    if (!is_readable($giftpath)) {
        echo "gift_missing path={$giftpath}\n";
        return count(security_plus_category_question_ids((int) $category->id));
    }

    if ($skipprefix !== null && function_exists('ut_practice_exam_category_question_ids')) {
        $existing = ut_practice_exam_category_question_ids((int) $category->id, $skipprefix);
        if (count($existing) >= $skiptarget) {
            echo "gift_skip path={$giftpath} reason=sufficient_existing count=" . count($existing) . "\n";
            return count(security_plus_category_question_ids((int) $category->id));
        }
    }

    $context = context::instance_by_id($contextid);
    $before = count(security_plus_category_question_ids((int) $category->id));

    $qformat = new qformat_gift();
    $qformat->setCategory($category);
    $qformat->setContexts([$context]);
    $qformat->setFilename($giftpath);
    $qformat->setStoponerror(false);
    if (!$qformat->importprocess()) {
        echo "gift_import_failed path={$giftpath}\n";
        return $before;
    }

    $after = count(security_plus_category_question_ids((int) $category->id));
    echo "gift_imported path={$giftpath} added=" . ($after - $before) . " total={$after}\n";
    return $after;
}

/**
 * Create a timed full-length practice exam quiz (uses proven add_quiz path, then updates settings).
 *
 * @param stdClass $course
 * @param int $sectionnum
 * @param string $quizname
 * @param int[] $questionids
 * @param int $timelimitsecs Time limit in seconds (default 90 minutes)
 * @return void
 */
function security_plus_add_practice_exam(
    stdClass $course,
    int $sectionnum,
    string $quizname,
    array $questionids,
    int $timelimitsecs = 5400
): void {
    security_plus_add_quiz($course, $sectionnum, $quizname, $questionids);
    security_plus_apply_practice_exam_settings($course, $quizname, $timelimitsecs);
}

/**
 * Apply practice exam timing and intro to an existing quiz.
 *
 * @param stdClass $course
 * @param string $quizname
 * @param int $timelimitsecs
 * @return void
 */
function security_plus_apply_practice_exam_settings(stdClass $course, string $quizname, int $timelimitsecs = 5400): void {
    global $DB;

    $quizrecord = $DB->get_record_sql(
        "SELECT q.*
           FROM {quiz} q
           JOIN {course_modules} cm ON cm.instance = q.id
           JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
          WHERE cm.course = :courseid AND q.name = :name",
        ['courseid' => $course->id, 'name' => $quizname]
    );
    if (!$quizrecord) {
        echo "practice_exam_settings_missing name={$quizname}\n";
        return;
    }

    $quizrecord->intro = '<p>Full-length Security+ SY0-701 practice exam. 90 questions, 90-minute time limit. '
        . 'Rate your confidence after each question. Target passing score: 83% (750/900 equivalent).</p>';
    $quizrecord->introformat = FORMAT_HTML;
    $quizrecord->timelimit = $timelimitsecs;
    $quizrecord->attempts = 2;
    $DB->update_record('quiz', $quizrecord);
    echo "practice_exam_settings_applied id={$quizrecord->id} name={$quizname} timelimit={$timelimitsecs}\n";
}

/**
 * Reconcile a practice exam quiz to the target question set.
 *
 * @param stdClass $course
 * @param int $sectionnum
 * @param string $quizname
 * @param int[] $questionids
 * @param int $timelimitsecs
 * @return void
 */
function security_plus_sync_practice_exam(
    stdClass $course,
    int $sectionnum,
    string $quizname,
    array $questionids,
    int $timelimitsecs = 5400
): void {
    require_once(__DIR__ . '/lib/moodle-cert-quiz-dedup.php');
    ut_sync_knowledge_check_quiz(
        $course,
        $sectionnum,
        $quizname,
        $questionids,
        static function (stdClass $c, int $sec, string $name, array $ids) use ($timelimitsecs): void {
            security_plus_add_practice_exam($c, $sec, $name, $ids, $timelimitsecs);
        }
    );
    security_plus_apply_practice_exam_settings($course, $quizname, $timelimitsecs);
}

/**
 * @param array<string,int|int[]> $questionmap
 * @return int Mappings created
 */
function security_plus_link_questions_to_objectives(array $questionmap): int {
    global $DB;

    $linked = 0;
    foreach ($questionmap as $objshort => $questionids) {
        $ids = is_array($questionids) ? $questionids : [(int) $questionids];
        $objective = $DB->get_record('certmaster_objectives', ['shortname' => $objshort]);
        if (!$objective) {
            continue;
        }
        foreach ($ids as $questionid) {
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
        'visible' => 1,
    ]);
    $category = $DB->get_record('course_categories', ['id' => $created->id], '*', MUST_EXIST);
    echo "category_created id={$category->id}\n";
} else if ((int) $category->visible !== 1) {
    $DB->set_field('course_categories', 'visible', 1, ['id' => (int) $category->id]);
    echo "category_visible_enabled id={$category->id}\n";
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
        . 'Five domains, 28 exam objectives, core + scenario + exam-focus lessons, '
        . 'confidence-rated knowledge checks, three practice exams, and hands-on labs.</p>';
    $newcourse->summaryformat = FORMAT_HTML;
    $course = create_course($newcourse);
    echo "course_created id={$course->id}\n";
} else {
    echo "course_exists id={$course->id}\n";
}

course_create_sections_if_missing($course, 7);
for ($i = 1; $i <= 7; $i++) {
    if (!$DB->record_exists('course_sections', ['course' => $course->id, 'section' => $i])) {
        course_create_section($course, $i);
    }
}
rebuild_course_cache((int) $course->id, true);

$sectionnames = [
    1 => 'Domain 1: General Security Concepts (12%)',
    2 => 'Domain 2: Threats, Vulnerabilities, and Mitigations (22%)',
    3 => 'Domain 3: Security Architecture (18%)',
    4 => 'Domain 4: Security Operations (28%)',
    5 => 'Domain 5: Security Program Management and Oversight (20%)',
    6 => 'Practice Exams',
    7 => 'Hands-on Labs',
];
foreach ($sectionnames as $num => $label) {
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $num], '*', MUST_EXIST);
    if ((string) $section->name !== $label) {
        $DB->set_field('course_sections', 'name', $label, ['id' => $section->id]);
    }
}
rebuild_course_cache((int) $course->id, true);

$domainsection = [
    'general_concepts' => 1,
    'threats_vulns' => 2,
    'security_architecture' => 3,
    'security_operations' => 4,
    'program_management' => 5,
];

foreach ($objectives as $objective) {
    $sectionnum = $domainsection[$objective->domainshort] ?? 1;
    $pagename = strtoupper(str_replace('sy701_', 'SY0-701 ', str_replace('_', '.', $objective->shortname)))
        . ': ' . $objective->fullname;
    $html = security_plus_load_lesson_html($repopath, $objective->shortname, $objective->fullname);

    if (security_plus_page_exists((int) $course->id, $sectionnum, $pagename)) {
        security_plus_upsert_page($course, $sectionnum, $pagename, $html);
        continue;
    }
    // Legacy title from earlier seed runs.
    $legacy = strtoupper(str_replace('sy701_', 'SY701.', str_replace('_', '.', $objective->shortname)))
        . ': ' . $objective->fullname;
    $legacypage = security_plus_find_page((int) $course->id, $sectionnum, $legacy)
        ?: security_plus_find_page((int) $course->id, $sectionnum, 'SY0-701 ' . $legacy);
    if ($legacypage) {
        if ($legacypage->name !== $pagename) {
            $legacypage->name = $pagename;
        }
        if ($legacypage->content !== $html) {
            security_plus_update_page_content($course, $legacypage, $pagename, $html);
            echo "page_updated_legacy id={$legacypage->id} name={$pagename} section={$sectionnum}\n";
        } else {
            echo "page_unchanged_legacy id={$legacypage->id} name={$pagename} section={$sectionnum}\n";
        }
        continue;
    }
    security_plus_upsert_page($course, $sectionnum, $pagename, $html);
}

foreach ($objectives as $objective) {
    $sectionnum = $domainsection[$objective->domainshort] ?? 1;
    foreach (['_scenario', '_exam'] as $suffix) {
        $code = $objective->shortname . $suffix;
        $pagename = security_plus_sublesson_pagename($objective->shortname, $suffix, $objective->fullname);
        $html = security_plus_load_sublesson_html($repopath, $code, $objective->fullname);
        security_plus_upsert_page($course, $sectionnum, $pagename, $html);
    }
}
echo 'sublessons_seeded=' . (count($objectives) * 2) . "\n";

$context = context_course::instance((int) $course->id);
$qcat = security_plus_get_question_category((int) $context->id, 'Security+ SY0-701');
$giftbase = $repopath . '/content/security-plus/sy0-701-quiz.gift';
$giftextra = $repopath . '/content/security-plus/sy0-701-quiz-extra.gift';
security_plus_import_gift((int) $context->id, $qcat, $giftbase);
if (is_readable($giftextra)) {
    security_plus_import_gift((int) $context->id, $qcat, $giftextra);
}
$giftlaunch = $repopath . '/content/security-plus/sy0-701-quiz-launch.gift';
if (is_readable($giftlaunch)) {
    security_plus_import_gift_unconditional((int) $context->id, $qcat, $giftlaunch);
}

require_once(__DIR__ . '/lib/moodle-cert-quiz-dedup.php');
ut_dedupe_question_bank_category((int) $qcat->id);

$allquestionmap = security_plus_map_all_questions_by_objective((int) $qcat->id);
$linked = security_plus_link_questions_to_objectives($allquestionmap);
echo "question_objective_links={$linked}\n";

for ($domainnum = 1; $domainnum <= 5; $domainnum++) {
    $num = $domainnum;
    $qids = ut_curate_knowledge_check_questions(
        $allquestionmap,
        static function (string $obj) use ($num): bool {
            return (bool) preg_match('/^sy701_' . $num . '_/', $obj);
        }
    );
    if (!$qids) {
        continue;
    }
    security_plus_sync_quiz(
        $course,
        $domainnum,
        "Domain {$domainnum} Knowledge Check",
        $qids
    );
}

echo "practice_exam_block_start\n";
$pegift = $repopath . '/content/security-plus/practice-exam-1.gift';
if (is_readable($pegift)) {
    try {
        $pecategory = security_plus_get_child_question_category($qcat, 'Practice Exam 1 Bank', 'sec701-pe1-bank');
        echo "practice_exam_category_id={$pecategory->id}\n";
        security_plus_import_gift_unconditional((int) $context->id, $pecategory, $pegift, 'pe1_q');
        $pequestionids = ut_practice_exam_category_question_ids((int) $pecategory->id, 'pe1_q');
    } catch (Throwable $e) {
        echo 'practice_exam_1_category_failed error=' . $e->getMessage() . ' class=' . get_class($e) . "\n";
        $pequestionids = ut_select_practice_exam_questions((int) $qcat->id, 90);
    }
} else {
    $pequestionids = ut_select_practice_exam_questions((int) $qcat->id, 90);
}
echo 'practice_exam_1_pool=' . count($pequestionids) . "\n";
for ($labsection = 6; $labsection <= 7; $labsection++) {
    if (!$DB->record_exists('course_sections', ['course' => $course->id, 'section' => $labsection])) {
        course_create_section($course, $labsection);
        echo "section_created num={$labsection}\n";
    }
}
rebuild_course_cache((int) $course->id, true);
if ($pequestionids) {
    try {
        security_plus_sync_practice_exam(
            $course,
            6,
            'Practice Exam 1',
            $pequestionids,
            5400
        );
        echo 'practice_exam_1_questions=' . count($pequestionids) . "\n";
    } catch (Throwable $e) {
        echo 'practice_exam_1_failed error=' . $e->getMessage() . "\n";
    }
} else {
    echo "practice_exam_1_skipped reason=no_questions\n";
}

for ($examnum = 2; $examnum <= 3; $examnum++) {
    $pename = "Practice Exam {$examnum}";
    $pecatname = "Practice Exam {$examnum}";
    $pegiftpath = $repopath . '/content/security-plus/practice-exam-' . $examnum . '.gift';
    $peprefix = 'pe' . $examnum . '_q';
    if (is_readable($pegiftpath)) {
        try {
            $pecat = security_plus_get_child_question_category(
                $qcat,
                "Practice Exam {$examnum} Bank",
                "sec701-pe{$examnum}-bank"
            );
            security_plus_import_gift_unconditional((int) $context->id, $pecat, $pegiftpath, $peprefix);
            $peids = ut_practice_exam_category_question_ids((int) $pecat->id, $peprefix);
        } catch (Throwable $e) {
            echo "practice_exam_{$examnum}_category_failed error=" . $e->getMessage()
                . ' class=' . get_class($e) . "\n";
            $peids = ut_select_practice_exam_questions((int) $qcat->id, 90);
        }
    } else {
        $peids = ut_select_practice_exam_questions((int) $qcat->id, 90);
    }
    echo "practice_exam_{$examnum}_pool=" . count($peids) . "\n";
    if ($peids) {
        try {
            security_plus_sync_practice_exam($course, 6, $pename, $peids, 5400);
        } catch (Throwable $e) {
            echo "practice_exam_{$examnum}_failed error=" . $e->getMessage() . "\n";
        }
    }
}

echo "labs_block_start\n";
$lab1intro = ut_load_lab_intro(
    $repopath,
    'security-plus',
    'lab-1-siem-triage',
    '<p>SIEM alert triage lab — see course materials for the scenario.</p>'
);
try {
    ut_upsert_ctfflag(
        $course,
        7,
        'Lab 1: SIEM alert triage',
        $lab1intro,
        'UT\\{[A-Fa-f0-9]{8}\\}',
        100
    );
} catch (Throwable $e) {
    echo 'ctfflag_lab1_failed error=' . $e->getMessage() . "\n";
}

$lab2intro = ut_load_lab_intro(
    $repopath,
    'security-plus',
    'lab-2-phishing-analysis',
    '<p>Phishing analysis lab.</p>'
);
try {
    ut_upsert_ctfflag(
        $course,
        7,
        'Lab 2: Phishing email analysis',
        $lab2intro,
        'UT\\{UT-PHISH-2026-Q2-7F3A\\}',
        100
    );
} catch (Throwable $e) {
    echo 'ctfflag_lab2_failed error=' . $e->getMessage() . "\n";
}

$lab3intro = ut_load_lab_intro(
    $repopath,
    'security-plus',
    'lab-3-firewall-rule-review',
    '<p>Firewall rule review lab.</p>'
);
try {
    ut_upsert_ctfflag(
        $course,
        7,
        'Lab 3: Firewall rule review',
        $lab3intro,
        'UT\\{RS-EDGE-9912\\}',
        100
    );
} catch (Throwable $e) {
    echo 'ctfflag_lab3_failed error=' . $e->getMessage() . "\n";
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

security_plus_disable_page_module_filters($course);
echo "page_filters_disabled=1\n";

echo "COURSE_PATH=/course/view.php?id={$course->id}\n";
echo "=== seed complete ===\n";
