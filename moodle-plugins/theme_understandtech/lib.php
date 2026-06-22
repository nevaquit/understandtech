<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * UnderstandTech theme lib.php
 *
 * Provides pre_scss() for design token injection, page_init() for AMD loading,
 * and helper functions for the custom renderer.
 *
 * @package   theme_understandtech
 * @copyright 2026 UnderstandTech
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Inject brand colour tokens as SCSS variables before Boost compiles its stylesheet.
 * This allows admin-configurable colours to override the defaults in _design-tokens.scss.
 *
 * @param theme_config $theme The theme configuration object.
 * @return string SCSS variable declarations.
 */
function theme_understandtech_get_pre_scss(theme_config $theme): string {
    $navy  = $theme->settings->brand_navy  ?? '#0B1F3A';
    $gold  = $theme->settings->brand_gold  ?? '#C9A227';
    $teal  = $theme->settings->brand_teal  ?? '#1A8A7D';

    // Sanitise — only allow valid hex colours.
    $navy = preg_match('/^#[0-9A-Fa-f]{3,6}$/', $navy) ? $navy : '#0B1F3A';
    $gold = preg_match('/^#[0-9A-Fa-f]{3,6}$/', $gold) ? $gold : '#C9A227';
    $teal = preg_match('/^#[0-9A-Fa-f]{3,6}$/', $teal) ? $teal : '#1A8A7D';

    // Derived tokens via shared palette builder.
    $derived = theme_understandtech_derive_palette($navy, $gold, $teal);

    $scss  = "// === UnderstandTech Admin-Configurable Brand Tokens ===\n";
    foreach ($derived['scss'] as $var => $value) {
        $scss .= "\${$var}: {$value};\n";
    }

    if (!empty($theme->settings->rawscsspre)) {
        $scss .= $theme->settings->rawscsspre;
    }

    return $scss;
}

/**
 * Inject Google Fonts preconnect and preload links into the page <head>.
 * Fixes the render-blocking font loading deficiency identified in the audit.
 *
 * @param moodle_page $page The current page object.
 * @return void
 */
function theme_understandtech_page_init(moodle_page $page): void {
    global $CFG;

    // FIRST on every pagelayout (course, incourse, mydashboard, frontpage, …):
    // patch core/templates before any placeholder hydration runs (fixes Y.NodeList crash).
    $page->requires->js_amd_inline(
        "require(['theme_understandtech/templates_dom_patch']);",
    );

    $page->add_body_class('ut-theme');

    $theme = theme_config::load('understandtech');
    if (!empty($theme->settings->enable_skool_layout)) {
        $page->add_body_class('ut-skool-enabled');
    }

    if ($page->cm && $page->cm->modname === 'page') {
        $page->add_body_class('ut-lesson-page');
    }

    if ($page->cm && $page->cm->modname === 'ctfflag') {
        $page->add_body_class('ut-lab-page');
    }

    if ($page->pagelayout === 'frontpage') {
        $page->add_body_class('ut-frontpage');
    }

    if ($page->cm && $page->cm->modname === 'quiz') {
        $page->add_body_class('ut-quiz-page');
        // Question flags use YUI M.core_question_flags.init; when YUI fails to boot the
        // raw checkbox stays visible and clicks do not persist — AMD fallback handles it.
        $page->requires->js_amd_inline(
            "require(['theme_understandtech/quiz_flag_fallback'], function(m) { m.init(); });",
        );
    }

    if ($page->context) {
        if ($page->context->contextlevel === CONTEXT_MODULE) {
            $page->add_body_class('ut-incourse');
        } else if (
            $page->context->contextlevel === CONTEXT_COURSE
            && $page->course
            && (int) $page->course->id !== SITEID
        ) {
            $page->add_body_class('ut-incourse');
        }
    }

    // Marketing/login UX only — never on incourse pages. js_call_amd registers a pending
    // operation before core/first boots; a missing amd/build file blocks the whole AMD
    // pipeline and prevents the course-index placeholder from hydrating.
    if (in_array($page->pagelayout, ['frontpage', 'login'], true)) {
        $page->requires->js_amd_inline(
            "require(['theme_understandtech/theme'], function(m) { m.init(); });",
        );
    }

    // Course index drawer: server prerender + AMD fallback (templates_dom_patch fixes core path).
    if (
        in_array($page->pagelayout, ['course', 'incourse'], true)
        && $page->course
        && (int) $page->course->id !== SITEID
    ) {
        $courseid = (int) $page->course->id;

        if (\theme_understandtech\output\course_index_prerender::should_prerender($page)) {
            $indexhtml = \theme_understandtech\output\course_index_prerender::render_html($courseid);
            if ($indexhtml !== '') {
                $page->requires->data_for_js(
                    'theme_understandtech',
                    ['courseindexhtml' => $indexhtml],
                    true,
                );
                $page->requires->js_amd_inline(
                    "require(['theme_understandtech/courseindex_prerender'], function(m) { m.init(); });",
                );
            }
        }

        $page->requires->js_amd_inline(
            "require(['theme_understandtech/courseindex_fallback'], function(m) { m.init({$courseid}); });",
        );
    }

    // My courses / dashboard: block_myoverview and block_timeline use Templates.replaceNodeContents (YUI).
    // When Y.NodeList is unavailable course cards and timeline events stay as skeleton placeholders forever.
    if (in_array($page->pagelayout, ['mycourses', 'mydashboard'], true)) {
        $page->requires->js_amd_inline(
            "require(['theme_understandtech/myoverview_fallback'], function(m) { m.init(); });",
        );
        $page->requires->js_amd_inline(
            "require(['theme_understandtech/timeline_fallback'], function(m) { m.init(); });",
        );
    }
}

/**
 * Return the main SCSS content for the theme preset.
 * Required by Moodle when $THEME->scss is set as a callback in config.php.
 * Reads scss/preset/default.scss which @imports all five partials.
 *
 * @param theme_config $theme The theme configuration object.
 * @return string Main SCSS content.
 */
function theme_understandtech_get_main_scss_content(theme_config $theme): string {
    global $CFG;
    // Start with Boost's default preset so Bootstrap + Boost SCSS variables and
    // mixins are available when our custom rules are compiled.
    $boostpreset = $CFG->dirroot . '/theme/boost/scss/preset/default.scss';
    $scss = is_readable($boostpreset) ? file_get_contents($boostpreset) : '';
    // Append our custom design system SCSS after Boost's preset.
    $customscss = $theme->dir . '/scss/preset/default.scss';
    if (is_readable($customscss)) {
        $scss .= "\n" . file_get_contents($customscss);
    }
    return $scss;
}

/**
 * Return extra SCSS to append after the preset (post.scss equivalent via PHP).
 * This is where widget-specific overrides live that need access to compiled vars.
 *
 * @param theme_config $theme The theme configuration object.
 * @return string Extra SCSS.
 */
function theme_understandtech_get_extra_scss(theme_config $theme): string {
    $scsspath = $theme->dir . '/scss/post.scss';
    $scss = is_readable($scsspath) ? file_get_contents($scsspath) : '';
    if (!empty($theme->settings->rawscss)) {
        $scss .= "\n" . $theme->settings->rawscss;
    }
    return $scss;
}

/**
 * CSS post-processor callback declared in config.php.
 * Currently a pass-through — returns the compiled CSS unchanged.
 * Reserved for future CSS variable injection or autoprefixing.
 *
 * @param string $css The compiled CSS.
 * @param theme_config $theme The theme configuration object.
 * @return string The processed CSS.
 */
function theme_understandtech_process_css(string $css, theme_config $theme): string {
    $navy = $theme->settings->brand_navy ?? '#0B1F3A';
    $gold = $theme->settings->brand_gold ?? '#C9A227';
    $teal = $theme->settings->brand_teal ?? '#1A8A7D';
    $navy = preg_match('/^#[0-9A-Fa-f]{3,6}$/', $navy) ? $navy : '#0B1F3A';
    $gold = preg_match('/^#[0-9A-Fa-f]{3,6}$/', $gold) ? $gold : '#C9A227';
    $teal = preg_match('/^#[0-9A-Fa-f]{3,6}$/', $teal) ? $teal : '#1A8A7D';

    $derived = theme_understandtech_derive_palette($navy, $gold, $teal);
    $vars = theme_understandtech_build_css_root($derived['css']);

    return $vars . $css;
}

/**
 * Serve theme uploaded files (custom logo).
 *
 * @param stdClass $course Course object.
 * @param stdClass $cm Course module object.
 * @param context $context Context object.
 * @param string $filearea File area name.
 * @param array $args Remaining args (itemid, path, filename).
 * @param bool $forcedownload Force download.
 * @param array $options Additional options.
 * @return bool False if not handled.
 */
function theme_understandtech_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = [],
) {
    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== 'custom_logo') {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'theme_understandtech', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
}

// ── Colour Utility Functions ──────────────────────────────────────────────────

/**
 * Derive the full UnderstandTech palette from admin brand anchors.
 *
 * @param string $navy Brand navy hex.
 * @param string $gold Brand gold hex.
 * @param string $teal Brand teal hex.
 * @return array{scss: array<string, string>, css: array<string, string>}
 */
function theme_understandtech_derive_palette(string $navy, string $gold, string $teal): array {
    $navydeep = theme_understandtech_darken_hex($navy, 0.3);
    $navymid = '#1a3358';
    $surface = '#ffffff';
    $surface2 = '#f5f5f5';
    $surfaceelevated = '#ffffff';
    $surfacehover = '#f5f5f5';
    $goldlight = theme_understandtech_lighten_hex($gold, 0.15);
    $goldhover = theme_understandtech_lighten_hex($gold, 0.08);
    $teallight = theme_understandtech_lighten_hex($teal, 0.15);
    $tealdark = theme_understandtech_darken_hex($teal, 0.08);
    $ocwred = '#A31F34';
    $ocwreddark = '#750014';
    $tealonlight = '#0d5c52';
    $goldonlight = '#7a5f10';
    $textprimary = '#1a1a1a';
    $textmuted = '#525252';

    $scss = [
        'ut-navy' => $navy,
        'ut-navy-deep' => $navydeep,
        'ut-navy-mid' => $navymid,
        'ut-bg' => $surface,
        'ut-surface' => $surface,
        'ut-surface-2' => $surface2,
        'ut-surface-elevated' => $surfaceelevated,
        'ut-surface-hover' => $surfacehover,
        'ut-gold' => $gold,
        'ut-gold-light' => $goldlight,
        'ut-gold-hover' => $goldhover,
        'ut-gold-on-light' => $goldonlight,
        'ut-teal' => $teal,
        'ut-teal-light' => $teallight,
        'ut-teal-dark' => $tealdark,
        'ut-teal-on-light' => $tealonlight,
        'ut-ocw-red' => $ocwred,
        'ut-ocw-red-dark' => $ocwreddark,
        'ut-success' => $tealonlight,
        'ut-warning' => $goldonlight,
        'ut-error' => $ocwred,
        'ut-info' => $navymid,
        'ut-text-primary' => $textprimary,
        'ut-text-secondary' => $textmuted,
        'ut-text-muted' => $textmuted,
        'ut-border-subtle' => '#e0e0e0',
        'ut-border-default' => '#e0e0e0',
        'ut-border-strong' => '#dadce0',
        'ut-action' => $ocwred,
        'ut-action-hover' => $ocwreddark,
        'ut-action-on' => '#ffffff',
        'ut-action-secondary' => $teal,
        'ut-action-secondary-hover' => $tealdark,
        'ut-focus-ring' => $navy,
        'ut-link' => $ocwred,
        'ut-link-hover' => $ocwreddark,
        'ut-brand-navy' => $navy,
        'ut-brand-gold' => $gold,
        'ut-brand-teal' => $teal,
        'primary' => $ocwred,
        'secondary' => $teal,
        'body-bg' => $surface,
        'body-color' => $textprimary,
        'headings-color' => $navy,
        'link-color' => $ocwred,
        'link-hover-color' => $ocwreddark,
        'progress-bar-bg' => $teal,
    ];

    $css = [
        '--ut-navy' => $navy,
        '--ut-navy-deep' => $navydeep,
        '--ut-navy-mid' => $navymid,
        '--ut-bg' => $surface,
        '--ut-surface' => $surface,
        '--ut-surface-2' => $surface2,
        '--ut-surface-elevated' => $surfaceelevated,
        '--ut-surface-hover' => $surfacehover,
        '--ut-gold' => $gold,
        '--ut-gold-light' => $goldlight,
        '--ut-gold-hover' => $goldhover,
        '--ut-gold-on-light' => $goldonlight,
        '--ut-teal' => $teal,
        '--ut-teal-light' => $teallight,
        '--ut-teal-dark' => $tealdark,
        '--ut-teal-on-light' => $tealonlight,
        '--ut-ocw-red' => $ocwred,
        '--ut-ocw-red-dark' => $ocwreddark,
        '--ut-success' => $tealonlight,
        '--ut-warning' => $goldonlight,
        '--ut-error' => $ocwred,
        '--ut-info' => $navymid,
        '--ut-success-bg' => 'rgba(13, 92, 82, 0.10)',
        '--ut-warning-bg' => 'rgba(201, 162, 39, 0.12)',
        '--ut-error-bg' => 'rgba(163, 31, 52, 0.08)',
        '--ut-info-bg' => 'rgba(11, 31, 58, 0.06)',
        '--ut-text' => $textprimary,
        '--ut-text-primary' => $textprimary,
        '--ut-text-secondary' => $textmuted,
        '--ut-text-muted' => $textmuted,
        '--ut-text-subtle' => $textmuted,
        '--ut-text-disabled' => '#9ca3af',
        '--ut-border' => '#e0e0e0',
        '--ut-border-subtle' => '#e0e0e0',
        '--ut-border-default' => '#e0e0e0',
        '--ut-border-strong' => '#dadce0',
        '--ut-border-focus' => 'rgba(11, 31, 58, 0.45)',
        '--ut-action' => $ocwred,
        '--ut-action-hover' => $ocwreddark,
        '--ut-action-on' => '#ffffff',
        '--ut-action-secondary' => $teal,
        '--ut-action-secondary-hover' => $tealdark,
        '--ut-focus-ring' => $navy,
        '--ut-progress' => $teal,
        '--ut-link' => $ocwred,
        '--ut-link-hover' => $ocwreddark,
        '--ut-lesson-outer' => $surface,
        '--ut-lesson-surface' => $surface,
        '--ut-lesson-prose' => $textprimary,
        '--ut-lesson-muted' => $textmuted,
        '--ut-lesson-card' => $surface,
        '--ut-lesson-border' => '#e0e0e0',
        '--ut-font-heading' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
        '--ut-font-body' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
        '--ut-font-mono' => 'ui-monospace, "Cascadia Code", "Segoe UI Mono", Consolas, monospace',
        '--ut-radius-sm' => '0.375rem',
        '--ut-radius-md' => '0.75rem',
        '--ut-radius-lg' => '1.25rem',
        '--ut-content-max' => '75rem',
    ];

    return ['scss' => $scss, 'css' => $css];
}

/**
 * Build a :root CSS custom property block from a token map.
 *
 * @param array<string, string> $tokens CSS variable name => value (without -- prefix).
 * @return string CSS :root block.
 */
function theme_understandtech_build_css_root(array $tokens): string {
    $parts = [];
    foreach ($tokens as $name => $value) {
        $parts[] = "--{$name}:{$value}";
    }
    return ':root{' . implode(';', $parts) . ";}\n";
}

/**
 * Darken a hex colour by a given ratio (0–1).
 *
 * @param string $hex  Hex colour e.g. '#0B1F3A'
 * @param float  $ratio Darkening ratio 0–1
 * @return string Darkened hex colour
 */
function theme_understandtech_darken_hex(string $hex, float $ratio): string {
    [$r, $g, $b] = theme_understandtech_hex_to_rgb($hex);
    $r = max(0, (int)round($r * (1 - $ratio)));
    $g = max(0, (int)round($g * (1 - $ratio)));
    $b = max(0, (int)round($b * (1 - $ratio)));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/**
 * Lighten a hex colour by a given ratio (0–1).
 *
 * @param string $hex  Hex colour e.g. '#0B1F3A'
 * @param float  $ratio Lightening ratio 0–1
 * @return string Lightened hex colour
 */
function theme_understandtech_lighten_hex(string $hex, float $ratio): string {
    [$r, $g, $b] = theme_understandtech_hex_to_rgb($hex);
    $r = min(255, (int)round($r + (255 - $r) * $ratio));
    $g = min(255, (int)round($g + (255 - $g) * $ratio));
    $b = min(255, (int)round($b + (255 - $b) * $ratio));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/**
 * Convert a hex colour string to an [R, G, B] array.
 *
 * @param string $hex Hex colour e.g. '#0B1F3A' or '0B1F3A'
 * @return array{int, int, int}
 */
function theme_understandtech_hex_to_rgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}
