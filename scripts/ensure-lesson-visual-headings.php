<?php
/**
 * Insert Visual Representation headings before ut-lesson-diagram blocks that lack one.
 *
 * @copyright 2026 AI Tech Pros, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$lessonsdir = dirname(__DIR__) . '/content/security-plus/lessons';
$files = glob($lessonsdir . '/sy701_*.html') ?: [];

foreach ($files as $file) {
    $html = file_get_contents($file);
    if ($html === false) {
        fwrite(STDERR, "read_fail {$file}\n");
        continue;
    }

    $original = $html;
    $offset = 0;

    while (preg_match('/<div\s+class="[^"]*\but-lesson-diagram\b[^"]*"/i', $html, $match, PREG_OFFSET_CAPTURE, $offset)) {
        $diagramstart = $match[0][1];
        $lookback = substr($html, max(0, $diagramstart - 800), min(800, $diagramstart));

        if (preg_match('/<h4>Visual Representation:[^<]*<\/h4>/i', $lookback)) {
            $offset = $diagramstart + 1;
            continue;
        }

        $title = 'Lesson Concept Overview';
        $snippet = substr($html, $diagramstart, 600);
        if (preg_match('/<div class="diagram-title">([^<]+)<\/div>/i', $snippet, $t)) {
            $title = trim(strip_tags($t[1]));
            $title = preg_replace('/^[\p{So}\p{Sk}\s]+/u', '', $title);
        } else if (preg_match('/<h4>([^<]+)<\/h4>/i', $snippet, $t)) {
            $title = trim(strip_tags($t[1]));
            $title = preg_replace('/^[\p{So}\p{Sk}\s]+/u', '', $title);
        }

        $block = '<h4>Visual Representation: ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h4>\n"
            . "<p>The following diagram illustrates this concept.</p>\n";

        $html = substr($html, 0, $diagramstart) . $block . substr($html, $diagramstart);
        echo 'heading ' . basename($file) . ' @ ' . $diagramstart . " ({$title})\n";
        $offset = $diagramstart + strlen($block) + 1;
    }

    if ($html !== $original) {
        file_put_contents($file, $html);
    }
}

echo "ensure_lesson_visual_headings_complete=1\n";
