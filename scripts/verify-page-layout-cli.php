<?php
/**
 * Verify mod_page layout fix using admin CLI context (no curl).
 *
 * Run: sudo -u www-data php /opt/understandtech-plugins/scripts/verify-page-layout-cli.php 4
 */

define('CLI_SCRIPT', true);

$cmid = (int) ($argv[1] ?? 0);
if ($cmid <= 0) {
    fwrite(STDERR, "Usage: php verify-page-layout-cli.php <cmid>\n");
    exit(1);
}

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/mod/page/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/filterlib.php');

global $DB, $USER, $CFG;

$admin = get_admin();
if (!$admin) {
    echo "admin_missing\n";
    exit(1);
}
$USER = $admin;

$cm = get_coursemodule_from_id('page', $cmid, 0, false, MUST_EXIST);
$page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cmid);

$content = file_rewrite_pluginfile_urls(
    $page->content,
    'pluginfile.php',
    $context->id,
    'mod_page',
    'content',
    0
);
$options = new stdClass();
$options->noclean = true;
$options->overflowdiv = true;
$options->context = $context;
$formatted = format_text($content, $page->contentformat, $options);

echo 'page_name=' . $page->name . "\n";
echo 'content_has_ut_lesson=' . (strpos($formatted, 'ut-lesson-content') !== false ? 'yes' : 'no') . "\n";
echo 'content_len=' . strlen($formatted) . "\n";

$csspath = $CFG->dirroot . '/theme/understandtech/style/lesson-content.css';
$css = file_get_contents($csspath);
echo 'css_card_rule=' . (strpos($css, '.path-mod-page .ut-lesson-content') !== false ? 'yes' : 'no') . "\n";
echo 'css_collapse_h100=' . (strpos($css, 'height: auto !important') !== false ? 'yes' : 'no') . "\n";

$theme = theme_config::load('understandtech');
$sheets = $theme->sheets;
echo 'theme_sheets=' . implode(',', $sheets) . "\n";
echo 'theme_version=' . $theme->version . "\n";

echo "=== done ===\n";
