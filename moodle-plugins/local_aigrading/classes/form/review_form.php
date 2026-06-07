<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_aigrading\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Instructor review form for AI grading recommendations.
 */
class review_form extends \moodleform {

    /**
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;
        $rec = $this->_customdata['recommendation'];

        $mform->addElement('static', 'learner', get_string('learner', 'local_aigrading'),
            fullname(\core_user::get_user($rec->userid)));
        $mform->addElement('static', 'submission', get_string('submission', 'local_aigrading'),
            format_text($rec->submissiontext, FORMAT_PLAIN));
        $mform->addElement('static', 'airec', get_string('airecommendation', 'local_aigrading'),
            $rec->ai_score . ' / ' . $rec->ai_maxscore . '<br>' . format_text($rec->ai_feedback, FORMAT_PLAIN));

        $mform->addElement('text', 'instructor_score', get_string('score', 'local_aigrading'));
        $mform->setType('instructor_score', PARAM_FLOAT);
        $mform->setDefault('instructor_score', $rec->ai_score);

        $mform->addElement('textarea', 'instructor_feedback', get_string('feedback', 'local_aigrading'), 'rows="6" cols="60"');
        $mform->setType('instructor_feedback', PARAM_RAW);
        $mform->setDefault('instructor_feedback', $rec->ai_feedback);

        $mform->addElement('hidden', 'id', $rec->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'cmid', $rec->cmid);
        $mform->setType('cmid', PARAM_INT);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'accept', get_string('accept', 'local_aigrading'));
        $buttonarray[] = $mform->createElement('submit', 'modify', get_string('modify', 'local_aigrading'));
        $buttonarray[] = $mform->createElement('submit', 'reject', get_string('reject', 'local_aigrading'));
        $mform->addGroup($buttonarray, 'actions', '', ' ', false);
    }
}
