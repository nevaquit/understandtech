<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aigrading', get_string('pluginname', 'local_aigrading'));

    $settings->add(new admin_setting_configcheckbox(
        'local_aigrading/enabled',
        get_string('enabled', 'local_aigrading'),
        get_string('enabled_desc', 'local_aigrading'),
        1
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_aigrading/defaultrubric',
        get_string('defaultrubric', 'local_aigrading'),
        get_string('defaultrubric_desc', 'local_aigrading'),
        'Grade on clarity, accuracy, completeness, and professional tone. Scale 0-100.',
        PARAM_RAW
    ));

    $ADMIN->add('localplugins', $settings);
}
