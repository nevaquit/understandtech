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

    $ADMIN->add('localplugins', $settings);
}
