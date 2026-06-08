<?php
/**
 * Simulate authenticated mod/page/view rendering and capture exceptions.
 *
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/diagnose-page-web.php 4 [username]
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);
define('ABORT_AFTER_CONFIG', false);
define('NO_OUTPUT_BUFFERING', true);

$cmid = (int) ($argv[1] ?? 0);
$username = $argv[2] ?? 'admin';

if ($cmid <= 0) {
    fwrite(STDERR, "Usage: php diagnose-page-web.php <cmid> [username]\n");
    exit(1);
}

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/mod/page/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/filterlib.php');

global $DB, $USER, $PAGE, $OUTPUT, $CFG;

$user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
if (!$user) {
    echo "user_missing username={$username}\n";
    exit(1);
}
\core\session\manager::set_user($user);
echo "user_ok id={$user->id} username={$username}\n";

$cm = get_coursemodule_from_id('page', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$context = context_module::instance($cmid);
require_login($course, false, $cm);

echo "login_ok course={$course->id}\n";

$page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
echo 'page_name=' . $page->name . "\n";

$PAGE->set_url('/mod/page/view.php', ['id' => $cmid]);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($course->shortname . ': ' . $page->name);
$PAGE->set_heading($course->fullname);

try {
    $options = new stdClass();
    $options->noclean = true;
    $options->overflowdiv = true;
    $options->context = $context;
    $formatted = format_text($page->content, $page->contentformat, $options);
    echo 'format_text_ok len=' . strlen($formatted) . "\n";
} catch (Throwable $e) {
    echo 'format_text_error=' . $e->getMessage() . "\n";
    if (property_exists($e, 'debuginfo') && $e->debuginfo) {
        echo 'format_text_debug=' . $e->debuginfo . "\n";
    }
    if (method_exists($e, 'getTraceAsString')) {
        echo 'format_text_trace=' . str_replace("\n", ' | ', $e->getTraceAsString()) . "\n";
    }
}

try {
    ob_start();
    echo $OUTPUT->header();
    $renderer = $PAGE->get_renderer('mod_page');
    if (method_exists($renderer, 'page_content')) {
        echo $renderer->page_content($page, $cm, $course);
    } else {
        $fmtopts = new stdClass();
        $fmtopts->noclean = true;
        $fmtopts->overflowdiv = true;
        $fmtopts->context = $context;
        echo '<div class="box generalbox center clearfix">' . format_text($page->content, $page->contentformat, $fmtopts) . '</div>';
    }
    echo $OUTPUT->footer();
    $html = ob_get_clean();
    if (strpos($html, 'Error reading from database') !== false) {
        echo "render_contains_db_error=1\n";
    } else {
        echo 'render_ok len=' . strlen($html) . "\n";
    }
} catch (Throwable $e) {
    ob_end_clean();
    echo 'render_error=' . $e->getMessage() . "\n";
    if (property_exists($e, 'debuginfo') && $e->debuginfo) {
        echo 'render_debug=' . $e->debuginfo . "\n";
    }
}

echo "=== done ===\n";
