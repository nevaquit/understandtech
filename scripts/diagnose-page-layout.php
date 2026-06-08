<?php
/**
 * Render mod/page/view internally and report layout/CSS markers.
 *
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/diagnose-page-layout.php 4 [username]
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', false);
define('ABORT_AFTER_CONFIG', false);
define('NO_OUTPUT_BUFFERING', true);

$cmid = (int) ($argv[1] ?? 0);
$username = $argv[2] ?? 'admin';

if ($cmid <= 0) {
    fwrite(STDERR, "Usage: php diagnose-page-layout.php <cmid> [username]\n");
    exit(1);
}

$_GET['id'] = $cmid;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/mod/page/view.php';
$_SERVER['REQUEST_URI'] = '/mod/page/view.php?id=' . $cmid;
$_SERVER['HTTP_HOST'] = 'understandtech.app';
$_SERVER['SERVER_NAME'] = 'understandtech.app';

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');

global $DB, $USER, $CFG;

$user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
if (!$user) {
    echo "user_missing username={$username}\n";
    exit(1);
}

\core\session\manager::set_user($user);

ob_start();
require($CFG->dirroot . '/mod/page/view.php');
$html = ob_get_clean();

echo 'render_ok len=' . strlen($html) . "\n";

$checks = [
    'ut-lesson-content' => (bool) preg_match('/ut-lesson-content/', $html),
    'path-mod-page' => (bool) preg_match('/path-mod-page/', $html),
    'page-mod-page-view' => (bool) preg_match('/page-mod-page-view/', $html),
    'lesson-content.css' => (bool) preg_match('/lesson-content\.css/', $html),
    'styles.php' => (bool) preg_match('/styles\.php/', $html),
    'drawers' => (bool) preg_match('/class="[^"]*drawers/', $html),
    'courseindex' => (bool) preg_match('/courseindex|data-region="courseindex"/', $html),
    'region-main-h100' => (bool) preg_match('/id="region-main"[^>]*class="[^"]*h-100/', $html),
];

foreach ($checks as $key => $ok) {
    echo $key . '=' . ($ok ? 'yes' : 'no') . "\n";
}

if (preg_match('/<body[^>]*class="([^"]*)"/', $html, $m)) {
    echo 'body_classes=' . trim(preg_replace('/\s+/', ' ', $m[1])) . "\n";
}

if (preg_match('/<title>([^<]*)<\/title>/', $html, $m)) {
    echo 'title=' . trim($m[1]) . "\n";
}

preg_match_all('/href="([^"]*lesson-content[^"]*)"/', $html, $csslinks);
echo 'lesson_css_links=' . count($csslinks[1]) . "\n";
foreach ($csslinks[1] as $link) {
    echo '  ' . $link . "\n";
}

echo "=== done ===\n";
