<?php
/**
 * Audit Visual Representation sections in SEC701 lesson HTML.
 *
 * @copyright 2026 AI Tech Pros, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$lessonsdir = dirname(__DIR__) . '/content/security-plus/lessons';
$files = glob($lessonsdir . '/sy701_*.html') ?: [];

echo str_pad('Lesson', 14)
    . str_pad('VR', 4)
    . str_pad('SVG', 4)
    . str_pad('Infog', 6)
    . "Issues\n";
echo str_repeat('-', 72) . "\n";

foreach ($files as $file) {
    $html = (string) file_get_contents($file);
    $vr = preg_match_all('/Visual Representation:/i', $html);
    $svg = substr_count($html, 'ut-svg-figure');
    $infog = substr_count($html, 'ut-infographic');
    $issues = [];
    if (strpos($html, 'illustrates this concept') !== false) {
        $issues[] = 'generic-intro';
    }
    if ($vr > 0 && $svg < $vr) {
        $issues[] = 'svg<' . $vr;
    }
    if (preg_match('/<div class="ut-lesson-diagram[^"]*">\s*<\/div>/', $html)) {
        $issues[] = 'empty-diagram';
    }
    echo str_pad(basename($file), 14)
        . str_pad((string) $vr, 4)
        . str_pad((string) $svg, 4)
        . str_pad((string) $infog, 6)
        . (empty($issues) ? 'ok' : implode(',', $issues)) . "\n";
}

echo "audit_visual_representations_complete=1\n";
