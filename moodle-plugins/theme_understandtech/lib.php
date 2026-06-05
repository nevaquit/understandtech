<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Inject brand variables before Boost SCSS compiles.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_understandtech_get_pre_scss($theme): string {
    $navy = get_config('theme_understandtech', 'brand_navy') ?: '#0B1F3A';
    $gold = get_config('theme_understandtech', 'brand_gold') ?: '#C9A227';
    $teal = get_config('theme_understandtech', 'brand_teal') ?: '#1A8A7D';

    return <<<SCSS
\$ut-brand-navy: {$navy};
\$ut-brand-gold: {$gold};
\$ut-brand-teal: {$teal};
\$primary: {$navy};
\$secondary: {$gold};
\$success: {$teal};
SCSS;
}

/**
 * Return the main SCSS preset content.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_understandtech_get_main_scss_content($theme): string {
    $scsspath = $theme->dir . '/scss/preset/default.scss';
    if (!is_readable($scsspath)) {
        return '';
    }
    return file_get_contents($scsspath);
}

/**
 * Load post-SCSS overrides after Boost styles.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_understandtech_get_extra_scss($theme): string {
    $scsspath = $theme->dir . '/scss/post.scss';
    if (!is_readable($scsspath)) {
        return '';
    }
    return file_get_contents($scsspath);
}

/**
 * Post-process compiled CSS (placeholder for future font URL rewrites).
 *
 * @param string $css
 * @param theme_config $theme
 * @return string
 */
function theme_understandtech_process_css(string $css, $theme): string {
    return $css;
}

/**
 * Serve the theme favicon.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_understandtech_get_favicon_url($theme): string {
    global $CFG;
    return $CFG->wwwroot . '/theme/understandtech/pix/favicon.svg';
}

/**
 * Google Fonts and meta tags injected in page head.
 *
 * @return string
 */
function theme_understandtech_before_standard_html_head(): string {
    return <<<HTML
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Share+Tech+Mono&family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&display=swap" rel="stylesheet">
HTML;
}
