<?php
// This file is part of Moodle - http://moodle.org/

namespace qbehaviour_certmasterconfidence\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for qbehaviour_certmasterconfidence.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
