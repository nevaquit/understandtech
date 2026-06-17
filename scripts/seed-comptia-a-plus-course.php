<?php
/**
 * Seed CompTIA A+ certification course, objectives, and lesson pages on Moodle.
 *
 * Idempotent: safe to re-run; skips existing activities by name.
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/seed-comptia-a-plus-course.php
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
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->libdir . '/filterlib.php');

/**
 * @param string $code Objective shortname e.g. ap1101_1_1
 * @param string $title Official objective title
 * @return string
 */
function aplus_lesson_html(string $code, string $title): string {
    $displaycode = preg_replace('/^ap110[12]_/', '', $code);
    $displaycode = str_replace('_', '.', $displaycode);
    $exam = str_starts_with($code, 'ap1101_') ? '220-1101' : '220-1102';
    $esc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<div class="ut-lesson-content">
<h3>Exam objective {$exam} {$displaycode}</h3>
<p><strong>{$esc}</strong></p>
<p>This lesson aligns with the CompTIA A+ certification blueprint. Focus on hands-on
scenario recognition and troubleshooting methodology, not isolated memorization.</p>
<h4>Next steps</h4>
<p>Use the AI tutor for scenario-based questions about this topic. The tutor will guide you
Socratically without revealing assessment answers.</p>
</div>
HTML;
}

/**
 * @param string $repopath Repository root on VM
 * @param string $code Objective shortname
 * @param string $title Objective title for fallback
 * @return string
 */
function aplus_load_lesson_html(string $repopath, string $code, string $title): string {
    $path = $repopath . '/content/a-plus/lessons/' . $code . '.html';
    if (is_readable($path)) {
        $html = file_get_contents($path);
        if ($html !== false && trim($html) !== '') {
            return $html;
        }
    }
    return aplus_lesson_html($code, $title);
}

/**
 * @param int $courseid
 * @param int $sectionnum
 * @param string $name
 * @return stdClass|null
 */
function aplus_find_page(int $courseid, int $sectionnum, string $name): ?stdClass {
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
 * @param stdClass $course
 * @param stdClass $page
 * @param string $name
 * @param string $html
 * @return void
 */
function aplus_update_page_content(stdClass $course, stdClass $page, string $name, string $html): void {
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
 * @param stdClass $course
 * @param int $sectionnum
 * @param string $name
 * @param string $html
 * @return void
 */
function aplus_add_page(stdClass $course, int $sectionnum, string $name, string $html): void {
    global $DB;

    if (aplus_find_page((int) $course->id, $sectionnum, $name)) {
        echo "page_exists name={$name} section={$sectionnum}\n";
        return;
    }

    $page = new stdClass();
    $page->course = $course->id;
    $page->name = $name;
    $page->intro = '';
    $page->introformat = FORMAT_HTML;
    $page->content = $html;
    $page->contentformat = FORMAT_HTML;
    $page->display = 0;
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
 * @param int $courseid
 * @return int
 */
function aplus_count_lesson_pages(int $courseid): int {
    global $DB;
    return (int) $DB->count_records_sql(
        "SELECT COUNT(p.id)
           FROM {page} p
           JOIN {course_modules} cm ON cm.instance = p.id
           JOIN {modules} m ON m.id = cm.module AND m.name = 'page'
          WHERE cm.course = :courseid",
        ['courseid' => $courseid]
    );
}

/**
 * @param stdClass $course
 * @param int $sectionnum
 * @param string $name
 * @param string $html
 * @return void
 */
function aplus_upsert_page(stdClass $course, int $sectionnum, string $name, string $html): void {
    $existing = aplus_find_page((int) $course->id, $sectionnum, $name);
    if ($existing) {
        if ($existing->content !== $html) {
            aplus_update_page_content($course, $existing, $name, $html);
            echo "page_updated id={$existing->id} name={$name} section={$sectionnum}\n";
        } else {
            echo "page_unchanged id={$existing->id} name={$name} section={$sectionnum}\n";
        }
        return;
    }
    aplus_add_page($course, $sectionnum, $name, $html);
}

/**
 * @param stdClass $course
 * @return void
 */
function aplus_disable_page_module_filters(stdClass $course): void {
    require_once(__DIR__ . '/lib/moodle-cert-course-filters.php');
    ut_disable_cert_course_text_filters($course, false);
}

/**
 * @param string $shortname Objective shortname
 * @return string Page title prefix
 */
function aplus_page_title_prefix(string $shortname): string {
    if (preg_match('/^ap1101_(\d+)_(\d+(?:_\d+)*|[a-z_]+)$/', $shortname, $m)) {
        $obj = $m[1] . '.' . str_replace('_', '.', $m[2]);
        return '220-1101 ' . $obj;
    }
    if (preg_match('/^ap1102_(\d+)_(\d+(?:_\d+)*|[a-z_]+)$/', $shortname, $m)) {
        $obj = $m[1] . '.' . str_replace('_', '.', $m[2]);
        return '220-1102 ' . $obj;
    }
    return strtoupper($shortname);
}

/**
 * @param int $contextid
 * @param string $categoryname
 * @return stdClass
 */
function aplus_get_question_category(int $contextid, string $categoryname): stdClass {
    global $DB;

    $existing = $DB->get_record('question_categories', ['contextid' => $contextid, 'name' => $categoryname]);
    if ($existing) {
        return $existing;
    }

    $parent = question_get_top_category($contextid, true);
    $record = new stdClass();
    $record->name = $categoryname;
    $record->contextid = $contextid;
    $record->info = 'CompTIA A+ certification objective-aligned questions';
    $record->infoformat = FORMAT_HTML;
    $record->stamp = make_unique_id_code();
    $record->parent = $parent->id;
    $record->sortorder = 999;
    $record->idnumber = '';
    $record->id = $DB->insert_record('question_categories', $record);
    return $record;
}

/**
 * @param int $categoryid
 * @return int[]
 */
function aplus_category_question_ids(int $categoryid): array {
    global $DB;

    $records = $DB->get_records_sql(
        "SELECT q.id
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
 * @return array<string,int[]>
 */
function aplus_map_all_questions_by_objective(int $categoryid): array {
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
        if (!preg_match('/\b(ap110[12]_\d+_\d+)\b/', $row->name, $m)) {
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
 * @return array<int,int[]>
 */
function aplus_map_questions_by_section(int $categoryid): array {
    $all = aplus_map_all_questions_by_objective($categoryid);
    $sections = array_fill(1, 9, []);
    foreach ($all as $objshort => $qids) {
        if (preg_match('/^ap1101_(\d+)_/', $objshort, $m)) {
            $sections[(int) $m[1]] = array_merge($sections[(int) $m[1]], $qids);
        } else if (preg_match('/^ap1102_(\d+)_/', $objshort, $m)) {
            $sections[5 + (int) $m[1]] = array_merge($sections[5 + (int) $m[1]], $qids);
        }
    }
    foreach ($sections as $num => $qids) {
        $sections[$num] = array_values(array_unique($qids));
        sort($sections[$num]);
    }
    return $sections;
}

/**
 * @param int $contextid
 * @param stdClass $category
 * @param string $giftpath
 * @return int
 */
function aplus_import_gift(int $contextid, stdClass $category, string $giftpath): int {
    if (!is_readable($giftpath)) {
        echo "gift_missing path={$giftpath}\n";
        return 0;
    }

    $expected = aplus_gift_expected_count($giftpath);
    $tagged = aplus_count_tagged_questions((int) $category->id);
    $total = count(aplus_category_question_ids((int) $category->id));
    if (getenv('APLUS_FORCE_GIFT') === '1') {
        echo "gift_force_import expected={$expected} tagged={$tagged} total={$total}\n";
    } else if ($tagged >= $expected && $expected > 9) {
        echo "gift_skip_existing tagged={$tagged} expected={$expected} total={$total}\n";
        return $total;
    } else if ($tagged >= 9 && $expected <= 9) {
        echo "gift_skip_existing tagged={$tagged} expected={$expected} total={$total}\n";
        return $total;
    }

    $context = context::instance_by_id($contextid);
    $before = count(aplus_category_question_ids((int) $category->id));

    $qformat = new qformat_gift();
    $qformat->setCategory($category);
    $qformat->setContexts([$context]);
    $qformat->setFilename($giftpath);
    $qformat->setStoponerror(false);
    if (!$qformat->importprocess()) {
        echo "gift_import_failed path={$giftpath}\n";
        return $before;
    }

    $after = count(aplus_category_question_ids((int) $category->id));
    echo "gift_imported path={$giftpath} added=" . ($after - $before) . " total={$after} expected={$expected}\n";
    return $after;
}

/**
 * Count ap110* tagged questions in the course question category.
 *
 * @param int $categoryid
 * @return int
 */
function aplus_count_tagged_questions(int $categoryid): int {
    global $DB;
    $count = 0;
    foreach (aplus_category_question_ids($categoryid) as $qid) {
        $name = (string) $DB->get_field('question', 'name', ['id' => $qid]);
        if (preg_match('/\b(ap110[12]_\d+_\d+)\b/', $name)) {
            $count++;
        }
    }
    return $count;
}

/**
 * Count ::ap110_* questions declared in a GIFT file.
 *
 * @param string $giftpath
 * @return int
 */
function aplus_gift_expected_count(string $giftpath): int {
    if (!is_readable($giftpath)) {
        return 9;
    }
    $content = file_get_contents($giftpath);
    if ($content === false) {
        return 9;
    }
    return preg_match_all('/::[^:\n]*ap110[12]_\d+_\d+/m', $content) ?: 9;
}

/**
 * Apply Moodle 4.5 quiz form defaults (matches mod_quiz_generator / SEC701 seed).
 *
 * @param stdClass $quiz
 * @return stdClass
 */
function aplus_quiz_apply_defaults(stdClass $quiz): stdClass {
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
function aplus_add_quiz(stdClass $course, int $sectionnum, string $quizname, array $questionids): void {
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
    }

    $quiz = aplus_quiz_apply_defaults(new stdClass());
    $quiz->course = $course->id;
    $quiz->name = $quizname;
    $quiz->intro = '<p>Domain knowledge check for CompTIA A+ certification. Rate your confidence after each question.</p>';
    $quiz->introformat = FORMAT_HTML;
    $quiz->module = $DB->get_field('modules', 'id', ['name' => 'quiz']);
    $quiz->modulename = 'quiz';
    $quiz->section = $sectionnum;
    $quiz->visible = 1;
    $quiz->cmidnumber = '';

    try {
        $cm = add_moduleinfo($quiz, $course);
    } catch (Throwable $e) {
        $debug = property_exists($e, 'debuginfo') ? (string) $e->debuginfo : '';
        echo "quiz_create_failed name={$quizname} error=" . $e->getMessage()
            . ($debug !== '' ? " debug={$debug}" : '') . "\n";
        return;
    }

    $quizrecord = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
    $quizrecord->cmid = $cm->coursemodule;

    $added = 0;
    foreach ($questionids as $qid) {
        try {
            if (quiz_add_quiz_question($qid, $quizrecord, 0) === false) {
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
 * @param stdClass $course
 * @param int $sectionnum
 * @param string $quizname
 * @param int[] $questionids
 * @return void
 */
function aplus_sync_quiz(stdClass $course, int $sectionnum, string $quizname, array $questionids): void {
    global $DB;

    if (!$questionids) {
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
        aplus_add_quiz($course, $sectionnum, $quizname, $questionids);
        return;
    }

    $cm = get_coursemodule_from_instance('quiz', (int) $quizrecord->id, (int) $course->id, false, MUST_EXIST);
    $quizrecord->cmid = $cm->id;

    $added = 0;
    foreach ($questionids as $qid) {
        try {
            if (quiz_add_quiz_question($qid, $quizrecord, 0) === false) {
                continue;
            }
            $added++;
        } catch (Throwable $e) {
            echo "quiz_sync_failed name={$quizname} qid={$qid} error=" . $e->getMessage() . "\n";
            break;
        }
    }

    if ($added > 0) {
        quiz_update_sumgrades($quizrecord);
    }
    echo "quiz_synced id={$quizrecord->id} name={$quizname} added={$added} total="
        . count($questionids) . "\n";
}

/**
 * @param array<string,int[]> $questionmap
 * @return int
 */
function aplus_link_questions_to_objectives(array $questionmap): int {
    global $DB;

    $linked = 0;
    foreach ($questionmap as $objshort => $questionids) {
        $objective = $DB->get_record('certmaster_objectives', ['shortname' => $objshort]);
        if (!$objective) {
            continue;
        }
        foreach ($questionids as $questionid) {
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

/**
 * Enable manual and self enrolment for learner access.
 *
 * @param stdClass $course
 * @return void
 */
function aplus_enable_enrolment(stdClass $course): void {
    global $DB;

    $studentroleid = (int) $DB->get_field('role', 'id', ['shortname' => 'student']);
    if (!$studentroleid) {
        echo "role_missing shortname=student\n";
        return;
    }

    $enabled = array_filter(explode(',', (string) get_config('moodle', 'enrol_plugins_enabled')));
    foreach (['manual', 'self'] as $pluginname) {
        if (!in_array($pluginname, $enabled, true)) {
            $enabled[] = $pluginname;
            echo "enrol_plugin_enabled={$pluginname}\n";
        }
    }
    set_config('enrol_plugins_enabled', implode(',', $enabled));

    $manual = enrol_get_plugin('manual');
    if ($manual) {
        $hasmanual = false;
        foreach (enrol_get_instances($course->id, true) as $instance) {
            if ($instance->enrol === 'manual') {
                $hasmanual = true;
                break;
            }
        }
        if (!$hasmanual) {
            $manual->add_instance($course);
            echo "manual_enrol_instance_created=1\n";
        }
    }

    $self = enrol_get_plugin('self');
    if ($self) {
        $selfinstance = null;
        foreach (enrol_get_instances($course->id, true) as $instance) {
            if ($instance->enrol === 'self') {
                $selfinstance = $instance;
                break;
            }
        }
        if (!$selfinstance) {
            $self->add_instance($course, [
                'status' => ENROL_INSTANCE_ENABLED,
                'roleid' => $studentroleid,
                'password' => '',
                'customint6' => 1,
            ]);
            echo "self_enrol_instance_created=1\n";
        } else if ((int) $selfinstance->status !== ENROL_INSTANCE_ENABLED) {
            $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, ['id' => $selfinstance->id]);
            echo "self_enrol_instance_enabled=1\n";
        }
    }
}

/**
 * Ensure CompTIA A+ certification framework exists (idempotent).
 *
 * @return stdClass Certification record
 */
function aplus_ensure_certification(): stdClass {
    global $DB;

    $existing = $DB->get_record('certmaster_certifications', ['shortname' => 'comptia_a_plus']);
    if ($existing) {
        return $existing;
    }

    if (!$DB->get_manager()->table_exists('certmaster_certifications')) {
        fwrite(STDERR, "error=certmaster_tables_missing\n");
        exit(1);
    }

    $now = time();
    $certid = $DB->insert_record('certmaster_certifications', (object) [
        'shortname' => 'comptia_a_plus',
        'fullname' => 'CompTIA A+ certification',
        'exam_code' => '220-1101 / 220-1102',
        'timecreated' => $now,
        'timemodified' => $now,
    ]);

    $domains = [
        ['shortname' => 'mobile_devices', 'fullname' => 'Mobile Devices (Core 1)', 'weight' => 6.50, 'sortorder' => 1],
        ['shortname' => 'networking', 'fullname' => 'Networking (Core 1)', 'weight' => 11.50, 'sortorder' => 2],
        ['shortname' => 'hardware', 'fullname' => 'Hardware (Core 1)', 'weight' => 12.50, 'sortorder' => 3],
        ['shortname' => 'virtualization', 'fullname' => 'Virtualization and Cloud Computing (Core 1)', 'weight' => 5.50, 'sortorder' => 4],
        ['shortname' => 'hw_net_troubleshooting', 'fullname' => 'Hardware and Network Troubleshooting (Core 1)', 'weight' => 14.00, 'sortorder' => 5],
        ['shortname' => 'operating_systems', 'fullname' => 'Operating Systems (Core 2)', 'weight' => 15.50, 'sortorder' => 6],
        ['shortname' => 'security', 'fullname' => 'Security (Core 2)', 'weight' => 12.50, 'sortorder' => 7],
        ['shortname' => 'software_troubleshooting', 'fullname' => 'Software Troubleshooting (Core 2)', 'weight' => 11.00, 'sortorder' => 8],
        ['shortname' => 'operational_procedures', 'fullname' => 'Operational Procedures (Core 2)', 'weight' => 11.00, 'sortorder' => 9],
    ];

    foreach ($domains as $domain) {
        $DB->insert_record('certmaster_domains', (object) [
            'certificationid' => $certid,
            'shortname' => $domain['shortname'],
            'fullname' => $domain['fullname'],
            'blueprint_weight' => $domain['weight'],
            'sortorder' => $domain['sortorder'],
        ]);
    }

    echo "certification_bootstrapped id={$certid}\n";
    return $DB->get_record('certmaster_certifications', ['id' => $certid], '*', MUST_EXIST);
}

echo "=== CompTIA A+ certification course seed ===\n";

$cert = aplus_ensure_certification();

$csvpath = $repopath . '/content/a-plus/aplus-objectives.csv';
if (!is_readable($csvpath)) {
    echo "error=csv_missing path={$csvpath}\n";
    exit(1);
}
$existingobjectives = (int) $DB->count_records_sql(
    "SELECT COUNT(o.id)
       FROM {certmaster_objectives} o
       JOIN {certmaster_domains} d ON d.id = o.domainid
      WHERE d.certificationid = :certid",
    ['certid' => $cert->id]
);
if ($existingobjectives >= 57 && getenv('APLUS_FORCE_OBJECTIVES') !== '1') {
    echo "objectives_skip_existing count={$existingobjectives}\n";
    $imported = 0;
} else {
    $imported = \local_certmaster\csv_importer::import_from_csv(file_get_contents($csvpath));
    echo "objectives_imported={$imported}\n";
}

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

$course = $DB->get_record('course', ['shortname' => 'APLUS']);
$courseisnew = false;
if (!$course) {
    $courseisnew = true;
    $newcourse = new stdClass();
    $newcourse->fullname = 'CompTIA A+ certification';
    $newcourse->shortname = 'APLUS';
    $newcourse->category = $category->id;
    $newcourse->format = 'topics';
    $newcourse->visible = 1;
    $newcourse->summary = '<p>CompTIA A+ certification track covering Core 1 (220-1101) and Core 2 (220-1102). '
        . 'Nine blueprint domains, CyberKraft study guide lessons, and CertMaster readiness tracking.</p>';
    $newcourse->summaryformat = FORMAT_HTML;
    $course = create_course($newcourse);
    echo "course_created id={$course->id}\n";
} else {
    $DB->set_field('course', 'fullname', 'CompTIA A+ certification', ['id' => $course->id]);
    echo "course_exists id={$course->id}\n";
}

$sectioncount = 9;
course_create_sections_if_missing($course, $sectioncount);
for ($i = 1; $i <= $sectioncount; $i++) {
    if (!$DB->record_exists('course_sections', ['course' => $course->id, 'section' => $i])) {
        course_create_section($course, $i);
    }
}
if ($courseisnew) {
    rebuild_course_cache((int) $course->id, true);
}

$sectionnames = [
    1 => 'Core 1 · Domain 1: Mobile Devices (13%)',
    2 => 'Core 1 · Domain 2: Networking (23%)',
    3 => 'Core 1 · Domain 3: Hardware (25%)',
    4 => 'Core 1 · Domain 4: Virtualization and Cloud (11%)',
    5 => 'Core 1 · Domain 5: Hardware and Network Troubleshooting (28%)',
    6 => 'Core 2 · Domain 1: Operating Systems (31%)',
    7 => 'Core 2 · Domain 2: Security (25%)',
    8 => 'Core 2 · Domain 3: Software Troubleshooting (22%)',
    9 => 'Core 2 · Domain 4: Operational Procedures (22%)',
];
foreach ($sectionnames as $num => $label) {
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $num], '*', MUST_EXIST);
    if ((string) $section->name !== $label) {
        $DB->set_field('course_sections', 'name', $label, ['id' => $section->id]);
    }
}

$domainsection = [
    'mobile_devices' => 1,
    'networking' => 2,
    'hardware' => 3,
    'virtualization' => 4,
    'hw_net_troubleshooting' => 5,
    'operating_systems' => 6,
    'security' => 7,
    'software_troubleshooting' => 8,
    'operational_procedures' => 9,
];

$expectedpages = count($objectives);
$pagecount = aplus_count_lesson_pages((int) $course->id);
if ($pagecount >= $expectedpages && getenv('APLUS_FORCE_PAGES') !== '1') {
    echo "pages_skip_existing count={$pagecount} expected={$expectedpages}\n";
} else {
    foreach ($objectives as $objective) {
        $sectionnum = $domainsection[$objective->domainshort] ?? 1;
        $pagename = aplus_page_title_prefix($objective->shortname) . ': ' . $objective->fullname;
        $html = aplus_load_lesson_html($repopath, $objective->shortname, $objective->fullname);
        aplus_upsert_page($course, $sectionnum, $pagename, $html);
    }
}

$context = context_course::instance((int) $course->id);
$qcat = aplus_get_question_category((int) $context->id, 'CompTIA A+ certification');
$giftpath = $repopath . '/content/a-plus/aplus-quiz.gift';
aplus_import_gift((int) $context->id, $qcat, $giftpath);

$allquestionmap = aplus_map_all_questions_by_objective((int) $qcat->id);
$linked = aplus_link_questions_to_objectives($allquestionmap);
echo "question_objective_links={$linked}\n";

$sectionquestions = aplus_map_questions_by_section((int) $qcat->id);
foreach ($sectionquestions as $sectionnum => $qids) {
    if (!$qids) {
        continue;
    }
    aplus_sync_quiz(
        $course,
        $sectionnum,
        "Domain {$sectionnum} Knowledge Check",
        $qids
    );
}

aplus_enable_enrolment($course);

echo "page_filters_deferred=fix-aplus-course-filters.php\n";

$enrollscript = $repopath . '/scripts/enroll-aplus-default-users.php';
if (is_readable($enrollscript)) {
    echo "--- default enrolments ---\n";
    passthru(PHP_BINARY . ' ' . escapeshellarg($enrollscript), $enrollstatus);
    if ($enrollstatus !== 0) {
        fwrite(STDERR, "enroll_aplus_failed exit={$enrollstatus}\n");
        exit($enrollstatus);
    }
} else {
    echo "enroll_script_missing path={$enrollscript}\n";
}

echo "COURSE_PATH=/course/view.php?id={$course->id}\n";
echo "=== seed complete ===\n";
