<?php
/**
 * Authenticated curl check for mod/page layout markers (VM localhost).
 *
 * Run on VM: bash /opt/understandtech-plugins/scripts/diagnose-page-layout-vm.sh [cmid]
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

$cmid = (int) ($argv[1] ?? 0);
if ($cmid <= 0) {
    fwrite(STDERR, "Usage: php diagnose-page-layout.php <cmid>\n");
    exit(1);
}

$csspath = '/var/www/moodle/theme/understandtech/style/lesson-content.css';
$css = is_readable($csspath) ? file_get_contents($csspath) : '';

echo 'theme_version=';
include '/var/www/moodle/theme/understandtech/version.php';
echo $plugin->version . "\n";

$checks = [
    'card_background' => (bool) preg_match('/\.path-mod-page \.ut-lesson-content/', $css),
    'collapse_h100' => (bool) preg_match('/height:\s*auto\s*!important/', $css),
    'hide_empty_blocks' => (bool) preg_match('/\[data-region="blocks-content"\]/', $css),
];
foreach ($checks as $key => $ok) {
    echo 'css_' . $key . '=' . ($ok ? 'yes' : 'no') . "\n";
}

$htmlfile = sys_get_temp_dir() . '/ut-page-layout.html';
$cmd = 'bash /opt/understandtech-plugins/scripts/curl-page-view-vm.sh '
    . escapeshellarg((string) $cmid) . ' http://127.0.0.1 /learn 2>&1';
passthru($cmd, $exitcode);

$pagepath = '/tmp/page.html';
if (!is_readable($pagepath)) {
    echo "page_html_missing\n";
    exit(1);
}

$html = file_get_contents($pagepath);
echo 'page_len=' . strlen($html) . "\n";

$pagechecks = [
    'ut-lesson-content' => (bool) preg_match('/ut-lesson-content/', $html),
    'path-mod-page' => (bool) preg_match('/path-mod-page/', $html),
    'lesson-content.css' => (bool) preg_match('/lesson-content\.css/', $html),
    'styles.php' => (bool) preg_match('/styles\.php/', $html),
    'region-main-h100' => (bool) preg_match('/id="region-main"[^>]*class="[^"]*h-100/', $html),
    'db_error' => (bool) preg_match('/Error reading from database/', $html),
];
foreach ($pagechecks as $key => $ok) {
    echo 'page_' . $key . '=' . ($ok ? 'yes' : 'no') . "\n";
}

if (preg_match('/<title>([^<]*)<\/title>/', $html, $m)) {
    echo 'page_title=' . trim($m[1]) . "\n";
}

echo "=== done ===\n";
