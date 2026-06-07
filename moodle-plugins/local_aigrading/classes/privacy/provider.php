<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aigrading\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for local_aigrading.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * @param \core_privacy\local\metadata\collection $collection
     * @return \core_privacy\local\metadata\collection
     */
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): \core_privacy\local\metadata\collection {
        $collection->add_database_table('aigrading_recommendations', [
            'userid' => 'privacy:metadata:aigrading_recommendations',
        ], 'privacy:metadata:aigrading_recommendations');
        $collection->add_database_table('aigrading_audit_log', [
            'reviewerid' => 'privacy:metadata:aigrading_audit_log',
        ], 'privacy:metadata:aigrading_audit_log');
        return $collection;
    }

    /**
     * @param \core_privacy\local\request\contextlist $contextlist
     * @return void
     */
    public static function get_contexts_for_userid(\core_privacy\local\request\contextlist $contextlist, int $userid): void {
        // Grading data is course-scoped; export handled via linked user records.
    }

    /**
     * @param \core_privacy\local\request\approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist): void {
    }

    /**
     * @param \core_privacy\local\request\approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
    }

    /**
     * @param \core_privacy\local\request\approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist): void {
    }
}
