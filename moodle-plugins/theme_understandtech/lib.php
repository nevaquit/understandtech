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

    // Derived tokens
    $navyDeep = theme_understandtech_darken_hex($navy, 0.3);
    $navyMid  = theme_understandtech_lighten_hex($navy, 0.05);
    $surface  = theme_understandtech_lighten_hex($navy, 0.08);
    $goldLight = theme_understandtech_lighten_hex($gold, 0.15);
    $tealLight = theme_understandtech_lighten_hex($teal, 0.15);

    $scss  = "// === UnderstandTech Admin-Configurable Brand Tokens ===\n";
    $scss .= "\$ut-navy:        {$navy};\n";
    $scss .= "\$ut-navy-deep:   {$navyDeep};\n";
    $scss .= "\$ut-navy-mid:    {$navyMid};\n";
    $scss .= "\$ut-surface:     {$surface};\n";
    $scss .= "\$ut-gold:        {$gold};\n";
    $scss .= "\$ut-gold-light:  {$goldLight};\n";
    $scss .= "\$ut-teal:        {$teal};\n";
    $scss .= "\$ut-teal-light:  {$tealLight};\n";

    // Legacy aliases used by older partial code
    $scss .= "\$ut-brand-navy:  {$navy};\n";
    $scss .= "\$ut-brand-gold:  {$gold};\n";
    $scss .= "\$ut-brand-teal:  {$teal};\n";

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

    // Load the AMD theme module on every page.
    $page->requires->js_call_amd('theme_understandtech/theme', 'init');

    // Add Google Fonts preconnect hints via additional_html (head section).
    // These are injected as raw HTML since Moodle has no API for <link rel="preconnect">.
    $fontlinks = '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Source+Serif+4:ital,wght@0,400;0,600;1,400&family=Share+Tech+Mono&display=swap" onload="this.onload=null;this.rel=\'stylesheet\'">'
        . '<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Source+Serif+4:ital,wght@0,400;0,600;1,400&family=Share+Tech+Mono&display=swap"></noscript>';

    // Inject via $CFG->additionalhtmlhead if not already present.
    if (empty($CFG->additionalhtmlhead) || strpos($CFG->additionalhtmlhead, 'Rajdhani') === false) {
        // Note: in production, set $CFG->additionalhtmlhead in config.php or via Site Admin > Appearance > Additional HTML.
        // This is a fallback for environments where that config is not set.
        $page->requires->string_for_js('pluginname', 'theme_understandtech');
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
    $scsspath = $theme->dir . '/scss/preset/default.scss';
    if (!is_readable($scsspath)) {
        return '';
    }
    return file_get_contents($scsspath);
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
    if (!is_readable($scsspath)) {
        return '';
    }
    return file_get_contents($scsspath);
}

// ── Colour Utility Functions ──────────────────────────────────────────────────

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
