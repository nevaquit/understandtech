<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_integrations', get_string('pluginname', 'local_integrations'));

    $settings->add(new admin_setting_heading('local_integrations/discord', get_string('discordheading', 'local_integrations'), ''));
    $settings->add(new admin_setting_configtext('local_integrations/discordclientid', get_string('discordclientid', 'local_integrations'),
        get_string('discordclientid_desc', 'local_integrations'), '', PARAM_TEXT));

    $settings->add(new admin_setting_heading('local_integrations/bbb', get_string('bbbheading', 'local_integrations'), ''));
    $settings->add(new admin_setting_configtext('local_integrations/bbburl', get_string('bbburl', 'local_integrations'),
        get_string('bbburl_desc', 'local_integrations'), '', PARAM_URL));

    $settings->add(new admin_setting_heading('local_integrations/lti', get_string('ltiheading', 'local_integrations'), ''));
    $settings->add(new admin_setting_configtext('local_integrations/ltiissuer', get_string('ltiissuer', 'local_integrations'),
        get_string('ltiissuer_desc', 'local_integrations'), '', PARAM_URL));

    $settings->add(new admin_setting_heading('local_integrations/loom', get_string('loomheading', 'local_integrations'), ''));
    $settings->add(new admin_setting_configtext('local_integrations/loomworkspace', get_string('loomworkspace', 'local_integrations'),
        get_string('loomworkspace_desc', 'local_integrations'), '', PARAM_TEXT));

    $ADMIN->add('localplugins', $settings);
}
