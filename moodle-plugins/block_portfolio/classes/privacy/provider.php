<?php
// This file is part of Moodle - http://moodle.org/

namespace block_portfolio\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for block_portfolio.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
