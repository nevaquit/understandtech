<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * UnderstandTech theme settings.
 *
 * @package   theme_understandtech
 * @copyright 2026 UnderstandTech
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs(
        'themesettingunderstandtech',
        get_string('configtitle', 'theme_understandtech')
    );

    // ── Brand Colours Tab ─────────────────────────────────────────────────────
    $page = new admin_settingpage(
        'theme_understandtech_brand',
        get_string('configtitle', 'theme_understandtech')
    );

    // Navy
    $name        = 'theme_understandtech/brand_navy';
    $title       = get_string('brand_navy', 'theme_understandtech');
    $description = get_string('brand_navy_desc', 'theme_understandtech');
    $setting     = new admin_setting_configcolourpicker($name, $title, $description, '#0B1F3A');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Gold
    $name        = 'theme_understandtech/brand_gold';
    $title       = get_string('brand_gold', 'theme_understandtech');
    $description = get_string('brand_gold_desc', 'theme_understandtech');
    $setting     = new admin_setting_configcolourpicker($name, $title, $description, '#C9A227');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Teal
    $name        = 'theme_understandtech/brand_teal';
    $title       = get_string('brand_teal', 'theme_understandtech');
    $description = get_string('brand_teal_desc', 'theme_understandtech');
    $setting     = new admin_setting_configcolourpicker($name, $title, $description, '#1A8A7D');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    // ── Layout & Logo Tab ─────────────────────────────────────────────────────
    $page = new admin_settingpage(
        'theme_understandtech_layout',
        get_string('configtitle', 'theme_understandtech')
    );

    // Custom logo
    $name        = 'theme_understandtech/custom_logo';
    $title       = get_string('custom_logo', 'theme_understandtech');
    $description = get_string('custom_logo_desc', 'theme_understandtech');
    $setting     = new admin_setting_configstoredfile(
        $name, $title, $description, 'custom_logo', 0,
        ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg', '.svg']]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Skool layout toggle
    $name        = 'theme_understandtech/enable_skool_layout';
    $title       = get_string('enable_skool_layout', 'theme_understandtech');
    $description = get_string('enable_skool_layout_desc', 'theme_understandtech');
    $setting     = new admin_setting_configcheckbox($name, $title, $description, 1);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    // ── Advanced Tab ──────────────────────────────────────────────────────────
    $page = new admin_settingpage(
        'theme_understandtech_advanced',
        get_string('configtitle', 'theme_understandtech')
    );

    // Footer footnote
    $name        = 'theme_understandtech/footnote';
    $title       = get_string('footnote', 'theme_understandtech');
    $description = get_string('footnote_desc', 'theme_understandtech');
    $setting     = new admin_setting_confightmleditor($name, $title, $description, '');
    $page->add($setting);

    // Raw SCSS (pre)
    $name        = 'theme_understandtech/rawscsspre';
    $title       = get_string('rawscsspre', 'theme_understandtech');
    $description = get_string('rawscsspre_desc', 'theme_understandtech');
    $setting     = new admin_setting_configtextarea($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS (post)
    $name        = 'theme_understandtech/rawscss';
    $title       = get_string('rawscss', 'theme_understandtech');
    $description = get_string('rawscss_desc', 'theme_understandtech');
    $setting     = new admin_setting_configtextarea($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}
