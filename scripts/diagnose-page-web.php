<?php
/**
 * Simulate authenticated mod/page/view rendering and capture exceptions.
 *
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/diagnose-page-web.php 4 [username]
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

$cmid = (int) ($argv[1] ?? 0);
$username = $argv[2] ?? 'admin';

if ($cmid <= 0) {
    fwrite(STDERR, "Usage: php diagnose-page-web.php <cmid> [username]\n");
    exit(1);
}

echo "boot cmid={$cmid} user={$username}\n";

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/mod/page/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/filterlib.php');

global $DB, $USER, $PAGE, $OUTPUT, $CFG;

$user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
if (!$user) {
    echo "user_missing username={$username}\n";
    $user = $DB->get_record('user', ['id' => 2]);
    if ($user) {
        echo "fallback_user id=2 username={$user->username}\n";
    } else {
        exit(1);
    }
}

$USER = $user;
echo "user_ok id={$USER->id} username={$USER->username}\n";

$cm = get_coursemodule_from_id('page', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$context = context_module::instance($cmid);

echo "login_ok course={$course->id}\n";

$page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
echo 'page_name=' . $page->name . "\n";

try {
    $options = new stdClass();
    $options->noclean = true;
    $options->overflowdiv = true;
    $options->context = $context;
    $formatted = format_text($page->content, $page->contentformat, $options);
    echo 'format_text_ok len=' . strlen($formatted) . "\n";
} catch (Throwable $e) {
    echo 'format_text_error=' . $e->getMessage() . "\n";
    if (!empty($e->debuginfo)) {
        echo 'format_text_debug=' . $e->debuginfo . "\n";
    }
}

echo "=== done ===\n";
