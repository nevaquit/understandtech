<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Behaviour type registration.
 */
class qbehaviour_certmasterconfidence_type extends question_behaviour_type {

    #[\Override]
    public function is_archetypal(): bool {
        return false;
    }

    #[\Override]
    public function get_unused_display_options(): array {
        return [];
    }
}
