<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aigrading\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

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
            'submissiontext' => 'privacy:metadata:submissiontext',
            'reviewerid' => 'privacy:metadata:reviewerid',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:aigrading_recommendations');

        $collection->add_database_table('aigrading_audit_log', [
            'reviewerid' => 'privacy:metadata:aigrading_audit_log',
            'action' => 'privacy:metadata:auditaction',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:aigrading_audit_log');

        return $collection;
    }

    /**
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {aigrading_recommendations} r ON r.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :courselevel
                   AND (r.userid = :userid OR r.reviewerid = :userid2)";

        $contextlist->add_from_sql($sql, [
            'courselevel' => CONTEXT_COURSE,
            'userid' => $userid,
            'userid2' => $userid,
        ]);

        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {aigrading_recommendations} r ON r.courseid = ctx.instanceid
                  JOIN {aigrading_audit_log} a ON a.recommendationid = r.id
                 WHERE ctx.contextlevel = :courselevel
                   AND a.reviewerid = :userid";

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
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->count() === 0) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }

            $courseid = $context->instanceid;
            $export = [];

            $learnerrecs = $DB->get_records('aigrading_recommendations', [
                'userid' => $userid,
                'courseid' => $courseid,
            ], 'timecreated ASC');

            foreach ($learnerrecs as $rec) {
                $export[] = (object) [
                    'role' => 'learner',
                    'assignid' => $rec->assignid,
                    'status' => $rec->status,
                    'submissiontext' => $rec->submissiontext,
                    'ai_score' => $rec->ai_score,
                    'ai_feedback' => $rec->ai_feedback,
                    'instructor_score' => $rec->instructor_score,
                    'instructor_feedback' => $rec->instructor_feedback,
                    'timecreated' => transform::datetime($rec->timecreated),
                    'timemodified' => transform::datetime($rec->timemodified),
                ];
            }

            $reviewrecs = $DB->get_records_sql(
                "SELECT r.*
                   FROM {aigrading_recommendations} r
                  WHERE r.courseid = :courseid
                    AND (r.reviewerid = :userid OR r.id IN (
                          SELECT recommendationid FROM {aigrading_audit_log} WHERE reviewerid = :userid2
                    ))",
                ['courseid' => $courseid, 'userid' => $userid, 'userid2' => $userid]
            );

            foreach ($reviewrecs as $rec) {
                if ((int) $rec->userid === $userid) {
                    continue;
                }
                $audit = $DB->get_records('aigrading_audit_log', [
                    'recommendationid' => $rec->id,
                    'reviewerid' => $userid,
                ], 'timecreated ASC');

                $export[] = (object) [
                    'role' => 'reviewer',
                    'learnerid' => $rec->userid,
                    'assignid' => $rec->assignid,
                    'status' => $rec->status,
                    'audit' => array_values(array_map(static function($row) {
                        return (object) [
                            'action' => $row->action,
                            'detail' => $row->detail,
                            'timecreated' => transform::datetime($row->timecreated),
                        ];
                    }, $audit)),
                ];
            }

            if ($export !== []) {
                writer::with_context($context)->export_data([], (object) ['recommendations' => $export]);
            }
        }
    }

    /**
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $recs = $DB->get_records('aigrading_recommendations', ['courseid' => $context->instanceid]);
        foreach ($recs as $rec) {
            $DB->delete_records('aigrading_audit_log', ['recommendationid' => $rec->id]);
        }
        $DB->delete_records('aigrading_recommendations', ['courseid' => $context->instanceid]);
    }

    /**
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->count() === 0) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }

            $courseid = $context->instanceid;

            $learnerrecs = $DB->get_records('aigrading_recommendations', [
                'userid' => $userid,
                'courseid' => $courseid,
            ]);
            foreach ($learnerrecs as $rec) {
                $DB->delete_records('aigrading_audit_log', ['recommendationid' => $rec->id]);
                $DB->delete_records('aigrading_recommendations', ['id' => $rec->id]);
            }

            $reviewrecs = $DB->get_records_sql(
                "SELECT r.id
                   FROM {aigrading_recommendations} r
                  WHERE r.courseid = :courseid
                    AND r.reviewerid = :userid",
                ['courseid' => $courseid, 'userid' => $userid]
            );
            foreach ($reviewrecs as $rec) {
                $DB->set_field('aigrading_recommendations', 'reviewerid', null, ['id' => $rec->id]);
            }

            $DB->delete_records_select(
                'aigrading_audit_log',
                'reviewerid = :userid AND recommendationid IN (
                    SELECT id FROM {aigrading_recommendations} WHERE courseid = :courseid
                )',
                ['userid' => $userid, 'courseid' => $courseid]
            );
        }
    }
}
