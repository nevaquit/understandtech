<?php
/**
 * Insert flow-arrow separators between adjacent flow-step elements in lesson HTML.
 *
 * @copyright 2026 AI Tech Pros, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$lessonsdir = dirname(__DIR__) . '/content/security-plus/lessons';
$files = glob($lessonsdir . '/sy701_*.html') ?: [];

foreach ($files as $file) {
    $html = file_get_contents($file);
    if ($html === false) {
        continue;
    }
    $original = $html;

    $html = preg_replace_callback(
        '/(<div class="flow-diagram">\s*)(.*?)(\s*<\/div>)/s',
        function (array $m): string {
            $inner = $m[2];
            if (strpos($inner, 'flow-arrow') !== false) {
                return $m[0];
            }
            $inner = preg_replace(
                '/(<\/div>)\s*(<div class="flow-step">)/',
                '$1<div class="flow-arrow" aria-hidden="true">→</div>$2',
                $inner
            );
            return $m[1] . $inner . $m[3];
        },
        $html
    );

    if ($html !== $original) {
        file_put_contents($file, $html);
        echo 'arrows ' . basename($file) . "\n";
    }
}

echo "add_flow_arrows_complete=1\n";
