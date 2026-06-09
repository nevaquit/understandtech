<?php
/**
 * Audit and upgrade all SEC701 Visual Representation diagram sections.
 *
 * - Adds ut-visual-representation class to VR headings
 * - Replaces generic intro copy
 * - Adds ut-infographic + SVG banner where missing
 * - Inserts flow-arrow separators
 * - Normalizes diagram-title (strip leading emoji from VR titles)
 *
 * @copyright 2026 AI Tech Pros, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$lessonsdir = dirname(__DIR__) . '/content/security-plus/lessons';
$snippetdir = dirname(__DIR__) . '/content/security-plus/snippets/svg';
$files = glob($lessonsdir . '/sy701_*.html') ?: [];

/**
 * Strip leading symbols/emoji from a title string.
 *
 * @param string $title
 * @return string
 */
function ut_strip_title_prefix(string $title): string {
    $title = trim($title);
    return preg_replace('/^[\p{So}\p{Sk}\p{Emoji}\s]+/u', '', $title) ?? $title;
}

/**
 * Load SVG fragment with unique gradient id.
 *
 * @param string $snippetdir
 * @param string $name
 * @return string
 */
function ut_load_svg_frag(string $snippetdir, string $name): string {
    $path = $snippetdir . '/' . $name;
    if (!is_readable($path)) {
        return '';
    }
    $frag = (string) file_get_contents($path);
    if (strpos($frag, '{{GRAD_ID}}') !== false) {
        $frag = str_replace('{{GRAD_ID}}', 'utGrad' . bin2hex(random_bytes(4)), $frag);
    }
    return $frag;
}

/**
 * Pick banner fragment for diagram body.
 *
 * @param string $body
 * @return string
 */
function ut_pick_banner(string $body): string {
    global $snippetdir;
    if (strpos($body, 'flow-diagram') !== false) {
        return ut_load_svg_frag($snippetdir, 'flow-banner.frag.html');
    }
    if (strpos($body, 'controls-matrix') !== false || strpos($body, 'threat-actors') !== false) {
        return ut_load_svg_frag($snippetdir, 'matrix-banner.frag.html');
    }
    if (strpos($body, 'malware-grid') !== false) {
        return ut_load_svg_frag($snippetdir, 'grid-banner.frag.html');
    }
    if (strpos($body, 'concept-grid') !== false || strpos($body, 'cia-triad') !== false) {
        return ut_load_svg_frag($snippetdir, 'grid-banner.frag.html');
    }
    return ut_load_svg_frag($snippetdir, 'cycle-banner.frag.html');
}

/**
 * Insert flow arrows between adjacent flow-step elements.
 *
 * @param string $html
 * @return string
 */
function ut_add_flow_arrows(string $html): string {
    return preg_replace_callback(
        '/(<div class="flow-diagram">\s*)(.*?)(\s*<\/div>)/s',
        static function (array $m): string {
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
    ) ?? $html;
}

foreach ($files as $file) {
    $html = file_get_contents($file);
    if ($html === false) {
        continue;
    }
    $original = $html;
    $basename = basename($file);

    // VR headings: add class, clean emoji from heading text.
    $html = preg_replace_callback(
        '/<h4>Visual Representation:\s*([^<]*)<\/h4>/u',
        static function (array $m): string {
            $title = ut_strip_title_prefix($m[1]);
            return '<h4 class="ut-visual-representation">Visual Representation: '
                . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h4>';
        },
        $html
    ) ?? $html;

    // Replace generic intro paragraphs after VR headings.
    $html = preg_replace_callback(
        '/(<h4 class="ut-visual-representation">Visual Representation:\s*([^<]*)<\/h4>\s*)'
        . '<p>The following diagram illustrates this concept\.<\/p>/u',
        static function (array $m): string {
            $topic = ut_strip_title_prefix($m[2]);
            return $m[1] . '<p>The following diagram illustrates <strong>'
                . htmlspecialchars($topic, ENT_QUOTES, 'UTF-8')
                . '</strong> with a branded visual summary.</p>';
        },
        $html
    ) ?? $html;

    // Normalize ut-lesson-diagram opening tags.
    $html = preg_replace(
        '/<div class="ut-lesson-diagram(\s+ut-infographic)?">\s*\n\s*\n\s*/',
        '<div class="ut-lesson-diagram$1">' . "\n",
        $html
    ) ?? $html;

    // Ensure ut-infographic on every diagram block.
    $html = preg_replace(
        '/<div class="ut-lesson-diagram">/',
        '<div class="ut-lesson-diagram ut-infographic">',
        $html
    ) ?? $html;

    // Process each diagram: add SVG banner if missing.
    $offset = 0;
    while (preg_match(
        '/<div class="ut-lesson-diagram ut-infographic">/i',
        $html,
        $m,
        PREG_OFFSET_CAPTURE,
        $offset
    )) {
        $start = $m[0][1];
        $depth = 0;
        $pos = $start;
        $len = strlen($html);
        $end = null;
        while ($pos < $len) {
            if (preg_match('/<div\b[^>]*>/i', $html, $open, PREG_OFFSET_CAPTURE, $pos)) {
                $depth++;
                $pos = $open[0][1] + strlen($open[0][0]);
                continue;
            }
            if (preg_match('/<\/div>/i', $html, $close, PREG_OFFSET_CAPTURE, $pos)) {
                $depth--;
                $pos = $close[0][1] + strlen($close[0][0]);
                if ($depth === 0) {
                    $end = $pos;
                    break;
                }
                continue;
            }
            break;
        }
        if ($end === null) {
            break;
        }

        $block = substr($html, $start, $end - $start);
        if (strpos($block, 'ut-svg-figure') === false) {
            $banner = ut_pick_banner($block);
            if ($banner !== '') {
                if (preg_match(
                    '/(<div class="diagram-title">[^<]*<\/div>)/i',
                    $block,
                    $titlematch
                )) {
                    $insert = $titlematch[0] . "\n" . $banner . "\n";
                    $block = str_replace($titlematch[0], $insert, $block);
                } else {
                    $block = preg_replace(
                        '/<div class="ut-lesson-diagram ut-infographic">/',
                        '<div class="ut-lesson-diagram ut-infographic">' . "\n" . $banner . "\n",
                        $block,
                        1
                    ) ?? $block;
                }
                $html = substr($html, 0, $start) . $block . substr($html, $end);
                $end = $start + strlen($block);
            }
        }
        $offset = $end;
    }

    $html = ut_add_flow_arrows($html);

    if ($html !== $original) {
        file_put_contents($file, $html);
        echo "upgraded {$basename}\n";
    } else {
        echo "unchanged {$basename}\n";
    }
}

echo "upgrade_all_lesson_visuals_complete=1\n";
