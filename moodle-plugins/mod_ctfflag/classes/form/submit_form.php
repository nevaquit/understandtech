<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Learner flag submission form.
 */
class mod_ctfflag_submit_form extends moodleform {

    /**
     * @param stdClass|null $customdata Custom data including readonly flag.
     */
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true) {
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable);
    }

    /**
     * Define submission fields.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;
        $readonly = !empty($this->_customdata['readonly']);

        $mform->addElement(
            'text',
            'flagvalue',
            get_string('flagvalue', 'mod_ctfflag'),
            ['size' => '48', 'autocomplete' => 'off']
        );
        $mform->setType('flagvalue', PARAM_RAW_TRIMMED);
        $mform->addRule('flagvalue', null, 'required', null, 'client');

        if ($readonly) {
            $mform->hardFreezeAllVisibleExcept([]);
        } else {
            $this->add_action_buttons(false, get_string('submitflag', 'mod_ctfflag'));
        }
    }
}
