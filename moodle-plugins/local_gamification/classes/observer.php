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
        if (!class_exists('\block_xp\local\factory')) {
            return;
        }

        $userid = (int) $event->userid;
        $courseid = (int) $event->courseid;
        $bonus = 10;

        if (class_exists('\local_certmaster\api')) {
            $cert = self::first_certification();
            if ($cert) {
                $readiness = \local_certmaster\api::get_user_readiness($userid, (int) $cert->id);
                if (($readiness['overall_readiness'] ?? 0) >= 80) {
                    $bonus = 25;
                }
            }
        }

        api::award_xp($userid, $courseid, $bonus, 'quiz_attempt_submitted');
    }

    /**
     * @return \stdClass|null
     */
    protected static function first_certification(): ?\stdClass {
        global $DB;
        $record = $DB->get_record('certmaster_certifications', [], 'id ASC', '*', IGNORE_MULTIPLE);
        return $record ?: null;
    }
}
