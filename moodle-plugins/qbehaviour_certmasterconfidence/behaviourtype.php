<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/behaviour/deferredfeedback/behaviourtype.php');

/**
 * Behaviour type registration for CertMaster confidence rating (deferred feedback + confidence).
 */
class qbehaviour_certmasterconfidence_type extends qbehaviour_deferredfeedback_type {

    #[\Override]
    public function is_archetypal(): bool {
        return true;
    }
}
