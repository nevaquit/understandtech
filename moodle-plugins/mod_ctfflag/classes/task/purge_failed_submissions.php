<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_ctfflag\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Purge old failed flag attempts to limit table growth.
 */
class purge_failed_submissions extends \core\task\scheduled_task {

    /**
     * @return string
     */
    public function get_name(): string {
        return get_string('taskpurgefailed', 'mod_ctfflag');
    }

    /**
     * Delete failed submissions older than 30 days.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        $cutoff = time() - (30 * DAYSECS);
        $DB->delete_records_select(
            'ctfflag_submissions',
            'success = 0 AND timecreated < :cutoff',
            ['cutoff' => $cutoff]
        );
    }
}
