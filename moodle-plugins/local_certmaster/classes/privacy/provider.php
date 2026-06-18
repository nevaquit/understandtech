<?php
// This file is part of Moodle - http://moodle.org/

namespace local_certmaster\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy provider for certification mastery data.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    #[\Override]
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): \core_privacy\local\metadata\collection {
        $collection->add_database_table('certmaster_attempt_confidence', [
            'attemptid' => 'privacy:metadata:attemptid',
            'confidence' => 'privacy:metadata:confidence',
            'iscorrect' => 'privacy:metadata:iscorrect',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:attemptconfidence');

        $collection->add_database_table('certmaster_mastery', [
            'userid' => 'privacy:metadata:userid',
            'mastery_score' => 'privacy:metadata:masteryscore',
            'attempts_count' => 'privacy:metadata:attemptscount',
            'last_updated' => 'privacy:metadata:lastupdated',
        ], 'privacy:metadata:mastery');

        $collection->add_database_table('certmaster_study_plans', [
            'userid' => 'privacy:metadata:userid',
            'planjson' => 'privacy:metadata:planjson',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:studyplans');

        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $contextlist->add_user_context($userid);

        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {quiz_attempts} qa ON qa.userid = :userid
                  JOIN {quiz} q ON q.id = qa.quiz AND q.course = ctx.instanceid
                  JOIN {certmaster_attempt_confidence} c ON c.attemptid = qa.id
                 WHERE ctx.contextlevel = :courselevel";

        $contextlist->add_from_sql($sql, [
            'userid' => $userid,
            'courselevel' => CONTEXT_COURSE,
        ]);

        return $contextlist;
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->count() === 0) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_USER && (int) $context->instanceid === $userid) {
                self::export_user_context_data($context, $userid);
                continue;
            }

            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }

            $confidence = [];
            $attempts = $DB->get_records_sql(
                "SELECT qa.id AS attemptid, qa.quiz, c.confidence, c.iscorrect, c.timecreated
                   FROM {quiz_attempts} qa
                   JOIN {quiz} q ON q.id = qa.quiz AND q.course = :courseid
                   JOIN {certmaster_attempt_confidence} c ON c.attemptid = qa.id
                  WHERE qa.userid = :userid
               ORDER BY c.timecreated ASC",
                ['courseid' => $context->instanceid, 'userid' => $userid]
            );

            foreach ($attempts as $row) {
                $confidence[] = (object) [
                    'attemptid' => $row->attemptid,
                    'quizid' => $row->quiz,
                    'confidence' => $row->confidence,
                    'iscorrect' => (bool) $row->iscorrect,
                    'timecreated' => transform::datetime($row->timecreated),
                ];
            }

            if ($confidence !== []) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:metadata:attemptconfidence', 'local_certmaster')],
                    (object) ['confidence_ratings' => $confidence]
                );
            }
        }
    }

    /**
     * Export mastery and study-plan data stored in the user context.
     *
     * @param \context $context User context.
     * @param int $userid User id.
     * @return void
     */
    protected static function export_user_context_data(\context $context, int $userid): void {
        global $DB;

        $mastery = [];
        $records = $DB->get_records('certmaster_mastery', ['userid' => $userid], 'last_updated DESC');
        foreach ($records as $record) {
            $mastery[] = (object) [
                'objectiveid' => $record->objectiveid,
                'mastery_score' => $record->mastery_score,
                'attempts_count' => $record->attempts_count,
                'last_updated' => transform::datetime($record->last_updated),
            ];
        }

        if ($mastery !== []) {
            writer::with_context($context)->export_data(
                [get_string('privacy:metadata:mastery', 'local_certmaster')],
                (object) ['mastery' => $mastery]
            );
        }

        if ($DB->get_manager()->table_exists('certmaster_study_plans')) {
            $plans = [];
            $planrecords = $DB->get_records('certmaster_study_plans', ['userid' => $userid], 'timemodified DESC');
            foreach ($planrecords as $plan) {
                $plans[] = (object) [
                    'certificationid' => $plan->certificationid,
                    'weakobjectives' => $plan->weakobjectives,
                    'planjson' => $plan->planjson,
                    'timecreated' => transform::datetime($plan->timecreated),
                    'timemodified' => transform::datetime($plan->timemodified),
                ];
            }

            if ($plans !== []) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:metadata:studyplans', 'local_certmaster')],
                    (object) ['study_plans' => $plans]
                );
            }
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel === CONTEXT_COURSE) {
            $attemptids = $DB->get_fieldset_sql(
                "SELECT c.attemptid
                   FROM {certmaster_attempt_confidence} c
                   JOIN {quiz_attempts} qa ON qa.id = c.attemptid
                   JOIN {quiz} q ON q.id = qa.quiz
                  WHERE q.course = :courseid",
                ['courseid' => $context->instanceid]
            );
            if ($attemptids !== []) {
                list($insql, $params) = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED);
                $DB->delete_records_select('certmaster_attempt_confidence', "attemptid $insql", $params);
            }
            return;
        }

        if ($context->contextlevel === CONTEXT_USER) {
            $DB->delete_records('certmaster_mastery', ['userid' => $context->instanceid]);
            if ($DB->get_manager()->table_exists('certmaster_study_plans')) {
                $DB->delete_records('certmaster_study_plans', ['userid' => $context->instanceid]);
            }
        }
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->count() === 0) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_USER && (int) $context->instanceid === $userid) {
                $DB->delete_records('certmaster_mastery', ['userid' => $userid]);
                if ($DB->get_manager()->table_exists('certmaster_study_plans')) {
                    $DB->delete_records('certmaster_study_plans', ['userid' => $userid]);
                }
                continue;
            }

            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }

            $attemptids = $DB->get_fieldset_sql(
                "SELECT c.attemptid
                   FROM {certmaster_attempt_confidence} c
                   JOIN {quiz_attempts} qa ON qa.id = c.attemptid AND qa.userid = :userid
                   JOIN {quiz} q ON q.id = qa.quiz
                  WHERE q.course = :courseid",
                ['userid' => $userid, 'courseid' => $context->instanceid]
            );
            if ($attemptids !== []) {
                list($insql, $params) = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED);
                $DB->delete_records_select('certmaster_attempt_confidence', "attemptid $insql", $params);
            }
        }
    }
}
