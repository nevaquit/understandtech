<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_ctfflag\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider (stub).
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata:nullprovider';
    }
}
