<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy provider for AI tutor conversations.
 */
class provider implements \core_privacy\local\metadata\provider,
                            \core_privacy\local\request\plugin\provider {

    /**
     * @param \core_privacy\local\metadata\collection $collection
     * @return \core_privacy\local\metadata\collection
     */
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): \core_privacy\local\metadata\collection {
        $collection->add_database_table('aitutor_conversations', [
            'userid' => 'privacy:metadata:conversations:userid',
            'courseid' => 'privacy:metadata:conversations:courseid',
            'timecreated' => 'privacy:metadata:conversations:timecreated',
            'timemodified' => 'privacy:metadata:conversations:timemodified',
        ], 'privacy:metadata:conversations');

        $collection->add_database_table('aitutor_messages', [
            'role' => 'privacy:metadata:messages:role',
            'content' => 'privacy:metadata:messages:content',
            'timecreated' => 'privacy:metadata:messages:timecreated',
        ], 'privacy:metadata:messages');

        return $collection;
    }

    /**
     * @param int $userid
     * @return \contextlist
     */
    public static function get_contexts_for_userid(int $userid): \contextlist {
        $contextlist = new \contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {aitutor_conversations} c ON c.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :courselevel
                   AND c.userid = :userid";
        $contextlist->add_from_sql($sql, [
            'courselevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->count() === 0) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }

            $conversations = $DB->get_records('aitutor_conversations', [
                'userid' => $userid,
                'courseid' => $context->instanceid,
            ], 'timemodified DESC');

            $export = [];
            foreach ($conversations as $conversation) {
                $messages = $DB->get_records('aitutor_messages', ['conversationid' => $conversation->id], 'timecreated ASC');
                $export[] = [
                    'conversationuuid' => $conversation->conversationuuid,
                    'messages' => array_values(array_map(static function($m) {
                        return [
                            'role' => $m->role,
                            'content' => $m->content,
                            'timecreated' => transform::datetime($m->timecreated),
                        ];
                    }, $messages)),
                ];
            }

            if ($export !== []) {
                writer::with_context($context)->export_data([], (object) ['conversations' => $export]);
            }
        }
    }

    /**
     * @param context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $conversations = $DB->get_records('aitutor_conversations', ['courseid' => $context->instanceid]);
        foreach ($conversations as $conversation) {
            $DB->delete_records('aitutor_messages', ['conversationid' => $conversation->id]);
        }
        $DB->delete_records('aitutor_conversations', ['courseid' => $context->instanceid]);
    }

    /**
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->count() === 0) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }

            $conversations = $DB->get_records('aitutor_conversations', [
                'userid' => $userid,
                'courseid' => $context->instanceid,
            ]);
            foreach ($conversations as $conversation) {
                $DB->delete_records('aitutor_messages', ['conversationid' => $conversation->id]);
                $DB->delete_records('aitutor_conversations', ['id' => $conversation->id]);
            }
        }
    }
}
