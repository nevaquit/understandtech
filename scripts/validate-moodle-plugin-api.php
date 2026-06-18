<?php
/**
 * CI gate: catch Moodle 4.5-incompatible plugin API usage before production deploy.
 *
 * Usage: php scripts/validate-moodle-plugin-api.php
 *
 * @package   understandtech
 * @copyright 2026 UnderstandTech
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

$root = dirname(__DIR__);
$plugindir = $root . '/moodle-plugins';

$allowlistpath = $root . '/scripts/moodle-45-page-api-allowlist.txt';
$blocklistpath = $root . '/scripts/moodle-45-page-api-blocklist.txt';

/**
 * Load a method list file (comments and blanks ignored).
 *
 * @param string $path File path.
 * @return array<string, bool>
 */
function load_method_list(string $path): array {
    $methods = [];
    if (!is_readable($path)) {
        fwrite(STDERR, "Missing list file: {$path}\n");
        exit(2);
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $methods[$line] = true;
    }
    return $methods;
}

/**
 * Find PHP files under moodle-plugins.
 *
 * @param string $dir Root directory.
 * @return array<int, string>
 */
function find_plugin_php(string $dir): array {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        if (str_contains($path, '/vendor/') || str_contains($path, '/.git/')) {
            continue;
        }
        $files[] = $path;
    }
    sort($files);
    return $files;
}

/**
 * Report a validation error.
 *
 * @param string $message Error text.
 * @return void
 */
function report_error(string $message): void {
    global $errors;
    $errors[] = $message;
    fwrite(STDERR, "ERROR: {$message}\n");
}

$allowlist = load_method_list($allowlistpath);
$blocklist = load_method_list($blocklistpath);
$errors = [];

if (!is_dir($plugindir)) {
    fwrite(STDERR, "No moodle-plugins directory\n");
    exit(2);
}

// --- 1. moodle_page / $PAGE method calls ---
$pagecallpattern = '/\$((?:page|PAGE))->([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/';
foreach (find_plugin_php($plugindir) as $filepath) {
    if (str_ends_with($filepath, 'settings.php')) {
        // admin_settingpage uses $page->add(), not moodle_page.
        continue;
    }
    $lines = file($filepath);
    foreach ($lines as $lineno => $line) {
        if (!preg_match_all($pagecallpattern, $line, $matches, PREG_SET_ORDER)) {
            continue;
        }
        foreach ($matches as $match) {
            $method = $match[2];
            $relpath = str_replace($root . DIRECTORY_SEPARATOR, '', $filepath);
            $location = "{$relpath}:" . ($lineno + 1);

            if (isset($blocklist[$method])) {
                report_error("Blocked moodle_page::{$method}() at {$location} (not in Moodle 4.5)");
                continue;
            }
            if (!isset($allowlist[$method])) {
                report_error("Unknown moodle_page::{$method}() at {$location} — add to allowlist or fix API usage");
            }
        }
    }
}

// --- 2. js_call_amd in theme page_init / hooks (breaks core/first pipeline) ---
$jscallbackpattern = '/->js_call_amd\s*\(/';
foreach (find_plugin_php($plugindir) as $filepath) {
    if (!preg_match('/(?:theme_understandtech\/lib\.php|hook_callbacks\.php)$/i', $filepath)) {
        continue;
    }
    $content = file_get_contents($filepath);
    if (!preg_match($jscallbackpattern, $content)) {
        continue;
    }
    $relpath = str_replace($root . DIRECTORY_SEPARATOR, '', $filepath);
    report_error(
        "js_call_amd() in {$relpath} — use js_amd_inline to avoid blocking core/first",
    );
}

// --- 3. AMD src requires matching build/min file ---
$amdpattern = $plugindir . '/*/amd/src/*.js';
foreach (glob($amdpattern) as $srcfile) {
    $base = basename($srcfile, '.js');
    $buildmin = dirname(dirname($srcfile)) . '/build/' . $base . '.min.js';
    if (!is_readable($buildmin)) {
        $relpath = str_replace($root . DIRECTORY_SEPARATOR, '', $srcfile);
        report_error("Missing AMD build for {$relpath} — expected " . basename($buildmin));
    }
}

// --- 4. Privacy provider signatures (Moodle 4.5 plugin\provider) ---
$privacypattern = $plugindir . '/*/classes/privacy/provider.php';
foreach (glob($privacypattern) ?: [] as $privacyfile) {
    $content = file_get_contents($privacyfile);
    if ($content === false) {
        continue;
    }
    if (!preg_match('/\\\\core_privacy\\\\local\\\\request\\\\plugin\\\\provider/', $content)) {
        continue;
    }
    $relpath = str_replace($root . DIRECTORY_SEPARATOR, '', $privacyfile);
    if (preg_match(
        '/function\s+get_contexts_for_userid\s*\(\s*\\\\?core_privacy\\\\local\\\\request\\\\contextlist/',
        $content
    )) {
        report_error(
            "Invalid privacy get_contexts_for_userid signature in {$relpath} — first parameter must be int \$userid",
        );
        continue;
    }
    if (!preg_match(
        '/function\s+get_contexts_for_userid\s*\(\s*int\s+\$userid\s*\)/',
        $content
    )) {
        report_error(
            "Invalid privacy get_contexts_for_userid signature in {$relpath} — expected (int \$userid)",
        );
    }
}

if (!empty($errors)) {
    fwrite(STDERR, "\nvalidate_moodle_plugin_api_failed count=" . count($errors) . "\n");
    exit(1);
}

echo "validate_moodle_plugin_api_ok=1\n";
exit(0);
