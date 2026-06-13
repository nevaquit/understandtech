<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Block instance configuration form.
 */
class block_portfolio_edit_form extends block_edit_form {

    #[\Override]
    protected function specific_definition($mform): void {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_portfolio'));
        $mform->setType('config_title', PARAM_TEXT);
        $mform->addHelpButton('config_title', 'blocktitle', 'block_portfolio');

        $options = [0 => get_string('choosedots')];
        if (class_exists('\local_certmaster\api')) {
            $options += \local_certmaster\api::get_certification_options();
        }

        $mform->addElement(
            'select',
            'config_certificationid',
            get_string('certification', 'block_portfolio'),
            $options
        );
        $mform->setType('config_certificationid', PARAM_INT);
        $mform->addHelpButton('config_certificationid', 'certification', 'block_portfolio');
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (empty($data['config_certificationid'])) {
            $errors['config_certificationid'] = get_string('certificationrequired', 'block_portfolio');
        }
        return $errors;
    }
}
