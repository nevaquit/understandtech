<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity instance configuration form.
 */
class mod_ctfflag_mod_form extends moodleform_mod {

    /**
     * Define form fields.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('ctfflagname', 'mod_ctfflag'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements(get_string('intro', 'mod_ctfflag'));

        $mform->addElement(
            'text',
            'expected_flag_regex',
            get_string('expectedflagregex', 'mod_ctfflag'),
            ['size' => '64']
        );
        $mform->setType('expected_flag_regex', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('expected_flag_regex', 'expectedflagregex', 'mod_ctfflag');
        $mform->addRule('expected_flag_regex', null, 'required', null, 'client');
        $mform->setDefault('expected_flag_regex', 'UT\\{[A-Za-z0-9_\\-]+\\}');

        $mform->addElement('text', 'xp_award', get_string('xpaward', 'mod_ctfflag'), ['size' => '6']);
        $mform->setType('xp_award', PARAM_INT);
        $mform->addHelpButton('xp_award', 'xpaward', 'mod_ctfflag');
        $mform->setDefault('xp_award', 100);

        $mform->addElement('selectyesno', 'completion_required', get_string('completionrequired', 'mod_ctfflag'));
        $mform->setDefault('completion_required', 1);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
