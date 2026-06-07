<?php
// This file is part of Moodle - http://moodle.org/

namespace local_certmaster\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Hourly adaptive study plan generation for active learners.
 */
class generate_study_plans_task extends \core\task\scheduled_task {

    #[\Override]
    public function get_name(): string {
        return get_string('task_generate_study_plans', 'local_certmaster');
    }

    #[\Override]
    public function execute(): void {
        global $DB;

        $certs = $DB->get_records('certmaster_certifications', null, 'id ASC', 'id', 0, 5);
        $since = time() - HOURSECS;

        $activeusers = $DB->get_records_sql(
            "SELECT DISTINCT userid FROM {certmaster_attempt_confidence} WHERE timecreated >= :since",
            ['since' => $since]
        );

        foreach ($activeusers as $row) {
            $userid = (int) $row->userid;
            foreach ($certs as $cert) {
                \local_certmaster\study_plan::generate_for_user($userid, (int) $cert->id);
            }
        }
    }
}
