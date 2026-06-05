<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingunderstandtech', get_string('configtitle', 'theme_understandtech'));

    $page = new admin_settingpage('theme_understandtech_brand', get_string('configtitle', 'theme_understandtech'));

    $name = 'theme_understandtech/brand_navy';
    $title = get_string('brand_navy', 'theme_understandtech');
    $description = get_string('brand_navy_desc', 'theme_understandtech');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#0B1F3A');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_understandtech/brand_gold';
    $title = get_string('brand_gold', 'theme_understandtech');
    $description = get_string('brand_gold_desc', 'theme_understandtech');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#C9A227');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_understandtech/brand_teal';
    $title = get_string('brand_teal', 'theme_understandtech');
    $description = get_string('brand_teal_desc', 'theme_understandtech');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#1A8A7D');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_understandtech/custom_logo';
    $title = get_string('custom_logo', 'theme_understandtech');
    $description = get_string('custom_logo_desc', 'theme_understandtech');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'custom_logo', 0,
        ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg', '.svg']]);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_understandtech/enable_skool_layout';
    $title = get_string('enable_skool_layout', 'theme_understandtech');
    $description = get_string('enable_skool_layout_desc', 'theme_understandtech');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 1);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}
