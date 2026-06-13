<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aitutor', get_string('pluginname', 'local_aitutor'));

    $settings->add(new admin_setting_configcheckbox(
        'local_aitutor/enablesidebar',
        get_string('enablesidebar', 'local_aitutor'),
        get_string('enablesidebar_desc', 'local_aitutor'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_aitutor/workerurl',
        get_string('workerurl', 'local_aitutor'),
        get_string('workerurl_desc', 'local_aitutor'),
        'https://ai.understandtech.app/tutor',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_aitutor/tokenexpiry',
        get_string('tokenexpiry', 'local_aitutor'),
        get_string('tokenexpiry_desc', 'local_aitutor'),
        300,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_aitutor/jwtsharedsecret',
        get_string('jwtsharedsecret', 'local_aitutor'),
        get_string('jwtsharedsecret_desc', 'local_aitutor'),
        ''
    ));

    $ADMIN->add('localplugins', $settings);
}
