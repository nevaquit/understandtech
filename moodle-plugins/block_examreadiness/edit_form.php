<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Block instance configuration form.
 */
class block_examreadiness_edit_form extends block_edit_form {

    #[\Override]
    protected function specific_definition($mform): void {
        $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_examreadiness'));
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('text', 'config_certificationid', get_string('certificationid', 'block_examreadiness'));
        $mform->setType('config_certificationid', PARAM_INT);
        $mform->addHelpButton('config_certificationid', 'certificationid', 'block_examreadiness');
    }
}
