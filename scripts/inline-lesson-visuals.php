<?php
/**
 * Move ut-lesson-diagram blocks to immediately follow "Visual Representation" headings.
 *
 * @copyright 2026 AI Tech Pros, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

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

    while (preg_match('/<h4>Visual Representation:[^<]*<\/h4>/i', $html, $headingmatch, PREG_OFFSET_CAPTURE, $offset)) {
        $headingstart = $headingmatch[0][1];
        $headingend = $headingstart + strlen($headingmatch[0][0]);
        $offset = $headingend;

        if (!preg_match('/<div\s+class="[^"]*\but-lesson-diagram\b[^"]*"/i', $html, $diagmatch, PREG_OFFSET_CAPTURE, $headingend)) {
            continue;
        }
        $diagramstart = $diagmatch[0][1];

        // Already adjacent (only whitespace / one intro <p> between heading and diagram).
        $between = substr($html, $headingend, $diagramstart - $headingend);
        if (strlen(trim(strip_tags($between))) < 280 && !preg_match('/\but-lesson-diagram\b/i', $between)) {
            continue;
        }

        $depth = 0;
        $pos = $diagramstart;
        $len = strlen($html);
        $diagramend = null;
        while ($pos < $len) {
            if (preg_match('/<div\b[^>]*>/i', $html, $openmatch, PREG_OFFSET_CAPTURE, $pos)) {
                $depth++;
                $pos = $openmatch[0][1] + strlen($openmatch[0][0]);
                continue;
            }
            if (preg_match('/<\/div>/i', $html, $closematch, PREG_OFFSET_CAPTURE, $pos)) {
                $depth--;
                $pos = $closematch[0][1] + strlen($closematch[0][0]);
                if ($depth === 0) {
                    $diagramend = $pos;
                    break;
                }
                continue;
            }
            break;
        }

        if ($diagramend === null) {
            continue;
        }

        $diagramhtml = substr($html, $diagramstart, $diagramend - $diagramstart);
        $html = substr($html, 0, $diagramstart) . substr($html, $diagramend);

        // Re-find heading after removal shifted positions.
        if (!preg_match('/<h4>Visual Representation:[^<]*<\/h4>/i', $html, $h2, PREG_OFFSET_CAPTURE, $headingstart)) {
            break;
        }
        $insertat = $h2[0][1] + strlen($h2[0][0]);

        // Keep at most one intro paragraph after the heading.
        if (preg_match('/\s*<p>[^<]*(?:diagram|illustrates|matrix|following)[^<]*<\/p>/i', $html, $intro, PREG_OFFSET_CAPTURE, $insertat)) {
            $insertat = $intro[0][1] + strlen($intro[0][0]);
        }

        $html = substr($html, 0, $insertat) . "\n" . $diagramhtml . "\n" . substr($html, $insertat);
        $offset = $insertat + strlen($diagramhtml);
    }

    if ($html !== $original) {
        file_put_contents($file, $html);
        echo 'inlined ' . basename($file) . "\n";
    }
}

echo "inline_lesson_visuals_complete=1\n";
