<?php
// This file is part of Moodle - http://moodle.org/

namespace local_certmaster\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class import_form extends \moodleform {

    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('filepicker', 'csvfile', get_string('csvfile', 'local_certmaster'), null, [
            'accepted_types' => ['.csv', 'text/csv'],
        ]);
        $mform->addRule('csvfile', null, 'required');
        $mform->addElement('static', 'help', '', get_string('csvhelp', 'local_certmaster'));
        $this->add_action_buttons(true, get_string('import', 'local_certmaster'));
    }
}
