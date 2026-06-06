<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_ctfflag\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_ctfflag submission records.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * @param \core_privacy\local\metadata\collection $collection Metadata collection.
     * @return \core_privacy\local\metadata\collection
     */
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): \core_privacy\local\metadata\collection {
        $collection->add_database_table('ctfflag_submissions', [
            'userid' => 'privacy:metadata:ctfflag_submissions:userid',
            'success' => 'privacy:metadata:ctfflag_submissions:success',
            'timecreated' => 'privacy:metadata:ctfflag_submissions:timecreated',
        ], 'privacy:metadata:ctfflag_submissions');

        return $collection;
    }

    /**
     * @param int $userid User id.
     * @return \core_privacy\local\request\contextlist
     */
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = 'SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :modulelevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {ctfflag_submissions} s ON s.ctfflagid = cm.instance
                 WHERE s.userid = :userid';

        $contextlist->add_from_sql($sql, [
            'modulelevel' => CONTEXT_MODULE,
            'modname' => 'ctfflag',
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * @param \core_privacy\local\request\approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->count() === 0) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }

            $instance = $DB->get_record('ctfflag', ['id' => $context->instanceid]);
            if (!$instance) {
                continue;
            }

            $records = $DB->get_records('ctfflag_submissions', [
                'ctfflagid' => $instance->id,
                'userid' => $userid,
            ], 'timecreated ASC');

            if (!$records) {
                continue;
            }

            $data = (object) [
                'submissions' => array_values(array_map(static function($record) {
                    return (object) [
                        'success' => (bool) $record->success,
                        'timecreated' => transform::datetime($record->timecreated),
                    ];
                }, $records)),
            ];

            writer::with_context($context)->export_data([], $data);
        }
    }

    /**
     * @param \core_privacy\local\request\context $context Module context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\core_privacy\local\request\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $DB->delete_records('ctfflag_submissions', ['ctfflagid' => $context->instanceid]);
    }

    /**
     * @param \core_privacy\local\request\approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }

            $DB->delete_records('ctfflag_submissions', [
                'ctfflagid' => $context->instanceid,
                'userid' => $userid,
            ]);
        }
    }
}
