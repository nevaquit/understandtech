<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Nightly scheduled task — queue RAG reindex for active courses.
 */
class reindex_courses_task extends \core\task\scheduled_task {

    /**
     * @return string
     */
    public function get_name(): string {
        return get_string('task_reindex_courses', 'local_aitutor');
    }

    /**
     * @return void
     */
    public function execute(): void {
        global $DB;

        $courses = $DB->get_records_select('course', 'id > 1', null, 'id ASC', 'id', 0, 20);
        foreach ($courses as $course) {
            $task = new ingest_course_task();
            $task->set_custom_data((object) ['courseid' => $course->id]);
            \core\task\manager::queue_adhoc_task($task);
        }
    }
}
