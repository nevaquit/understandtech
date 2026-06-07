<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Adhoc task to index a single course for RAG.
 */
class ingest_course_task extends \core\task\adhoc_task {

    /**
     * @return void
     */
    public function execute(): void {
        $data = $this->get_custom_data();
        $courseid = (int) ($data->courseid ?? 0);
        if ($courseid <= 0) {
            return;
        }

        \local_aitutor\ingest::index_course($courseid);
    }
}
