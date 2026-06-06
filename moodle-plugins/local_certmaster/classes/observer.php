<?php
// This file is part of Moodle - http://moodle.org/

namespace local_certmaster;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers bridging lab/quiz activity to readiness recalculation.
 */
class observer {

    /**
     * Handle CTF lab flag success — stub toward future objective mapping.
     *
     * @param \mod_ctfflag\event\flag_submitted $event
     * @return void
     */
    public static function flag_submitted(\mod_ctfflag\event\flag_submitted $event): void {
        // When mod_ctfflag maps labs to certmaster objectives, recalculate mastery here.
        // Full lab → objective wiring is Phase 3; this observer preserves the event contract.
    }
}
