<?php
// This file is part of Moodle - http://moodle.org/

namespace local_certmaster\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Hourly mastery recalculation for recently active learners.
 */
class recalculate_mastery_task extends \core\task\scheduled_task {

    #[\Override]
    public function get_name(): string {
        return get_string('task_recalculate_mastery', 'local_certmaster');
    }

    #[\Override]
    public function execute(): void {
        global $DB;

        $since = time() - HOURSECS;
        $pairs = $DB->get_records_sql(
            "SELECT DISTINCT qa.userid, qo.objectiveid
               FROM {certmaster_attempt_confidence} c
               JOIN {quiz_attempts} qa ON qa.id = c.attemptid
               JOIN {question_attempts} qatt ON qatt.questionusageid = qa.uniqueid AND qatt.slot = c.slot
               JOIN {certmaster_question_objective} qo ON qo.questionid = qatt.questionid
              WHERE c.timecreated >= :since",
            ['since' => $since]
        );

        foreach ($pairs as $pair) {
            \local_certmaster\api::recalculate_mastery((int) $pair->userid, (int) $pair->objectiveid);
        }
    }
}
