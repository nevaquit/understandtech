<?php
// This file is part of Moodle - http://moodle.org/

namespace local_gamification;

defined('MOODLE_INTERNAL') || die();

/**
 * Server-side gamification event hooks (Level Up XP integration stub).
 */
class observer {

    /**
     * React to quiz attempt submission for XP calibration.
     *
     * When block_xp (Level Up XP) is installed, configure matching rules in its admin UI
     * or extend this observer to call block_xp APIs. Without block_xp, this is a no-op.
     *
     * @param \mod_quiz\event\attempt_submitted $event
     * @return void
     */
    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event): void {
        if (!class_exists('\block_xp\local\rule\event_rule')) {
            return;
        }

        // Level Up XP ships its own quiz rules engine; custom grants belong here when
        // readiness thresholds (local_certmaster) should unlock bonus XP tiers.
    }
}
