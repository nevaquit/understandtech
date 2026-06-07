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
        global $DB;

        $userid = (int) $event->userid;
        $objectives = $DB->get_records_sql(
            "SELECT DISTINCT m.objectiveid
               FROM {certmaster_mastery} m
              WHERE m.userid = :userid",
            ['userid' => $userid],
            0,
            10
        );

        foreach ($objectives as $row) {
            api::recalculate_mastery($userid, (int) $row->objectiveid);
        }

        study_plan::generate_for_user($userid, self::default_certification_id());
    }

    /**
     * @return int
     */
    protected static function default_certification_id(): int {
        global $DB;
        $cert = $DB->get_record('certmaster_certifications', [], 'id ASC', 'id', IGNORE_MULTIPLE);
        return $cert ? (int) $cert->id : 0;
    }
}
