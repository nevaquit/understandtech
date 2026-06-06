<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_certmaster', get_string('pluginname', 'local_certmaster'));

    $settings->add(new admin_setting_heading(
        'local_certmaster/frameworks',
        get_string('settingsheading', 'local_certmaster'),
        get_string('settingsdesc', 'local_certmaster')
    ));

    $settings->add(new admin_setting_description(
        'local_certmaster/manageinfo',
        '',
        get_string('manageframeworks', 'local_certmaster')
    ));

    $settings->add(new admin_setting_heading(
        'local_certmaster/streamheading',
        get_string('streamsettingsheading', 'local_certmaster'),
        get_string('streamsettingsdesc', 'local_certmaster')
    ));

    $settings->add(new admin_setting_configtext(
        'local_certmaster/streamsigningkid',
        get_string('streamsigningkid', 'local_certmaster'),
        get_string('streamsigningkid_desc', 'local_certmaster'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_certmaster/streamcustomersubdomain',
        get_string('streamcustomersubdomain', 'local_certmaster'),
        get_string('streamcustomersubdomain_desc', 'local_certmaster'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $ADMIN->add('localplugins', $settings);
}
