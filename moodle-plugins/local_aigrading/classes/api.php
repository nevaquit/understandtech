<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_aigrading;

defined('MOODLE_INTERNAL') || die();

/**
 * AI grading persistence and gradebook integration.
 */
class api {

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_MODIFIED = 'modified';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Request AI grade from Worker and persist recommendation.
     *
     * @param int $assignid Assignment instance id.
     * @param int $cmid Course module id.
     * @param int $courseid Course id.
     * @param int $learnerid Learner user id.
     * @param string $submissiontext Submission body.
     * @param string $rubric Rubric text.
     * @return int Recommendation id.
     */
    public static function create_recommendation(
        int $assignid,
        int $cmid,
        int $courseid,
        int $learnerid,
        string $submissiontext,
        string $rubric
    ): int {
        global $DB;

        if ($DB->record_exists('aigrading_recommendations', ['assignid' => $assignid, 'userid' => $learnerid])) {
            return (int) $DB->get_field('aigrading_recommendations', 'id', [
                'assignid' => $assignid,
                'userid' => $learnerid,
            ]);
        }

        $context = \context_course::instance($courseid);
        $response = \local_aitutor\worker_client::grade(
            $learnerid,
            $context,
            $submissiontext,
            $rubric,
            $cmid
        );

        $now = time();
        $record = (object) [
            'assignid' => $assignid,
            'cmid' => $cmid,
            'courseid' => $courseid,
            'userid' => $learnerid,
            'submissiontext' => $submissiontext,
            'rubric' => $rubric,
            'ai_score' => (float) ($response->score ?? 0),
            'ai_maxscore' => (float) ($response->max_score ?? 100),
            'ai_feedback' => (string) ($response->feedback ?? ''),
            'ai_breakdown' => json_encode($response->rubric_breakdown ?? []),
            'provider' => (string) ($response->provider ?? ''),
            'prompt_version' => (string) ($response->prompt_version ?? ''),
            'status' => self::STATUS_PENDING,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $id = $DB->insert_record('aigrading_recommendations', $record);
        self::log_action((int) $id, $learnerid, 'created', $record);

        return (int) $id;
    }

    /**
     * List pending recommendations for a course.
     *
     * @param int $courseid
     * @return array
     */
    public static function get_pending_for_course(int $courseid): array {
        global $DB;

        return array_values($DB->get_records('aigrading_recommendations', [
            'courseid' => $courseid,
            'status' => self::STATUS_PENDING,
        ], 'timecreated ASC'));
    }

    /**
     * Apply instructor decision and post grade to assign module.
     *
     * @param int $recommendationid
     * @param int $reviewerid
     * @param string $action accepted|modified|rejected
     * @param float|null $score Final score (modified flow).
     * @param string|null $feedback Final feedback.
     * @return bool
     */
    public static function apply_decision(
        int $recommendationid,
        int $reviewerid,
        string $action,
        ?float $score = null,
        ?string $feedback = null
    ): bool {
        global $DB;

        $rec = $DB->get_record('aigrading_recommendations', ['id' => $recommendationid], '*', MUST_EXIST);
        $now = time();

        if ($action === self::STATUS_REJECTED) {
            $rec->status = self::STATUS_REJECTED;
            $rec->reviewerid = $reviewerid;
            $rec->timemodified = $now;
            $DB->update_record('aigrading_recommendations', $rec);
            self::log_action($recommendationid, $reviewerid, self::STATUS_REJECTED, $rec);
            return true;
        }

        $finalscore = $action === self::STATUS_ACCEPTED ? (float) $rec->ai_score : (float) $score;
        $finalfeedback = $action === self::STATUS_ACCEPTED ? (string) $rec->ai_feedback : (string) $feedback;

        self::post_assign_grade($rec, $finalscore, $finalfeedback);

        $rec->status = $action === self::STATUS_ACCEPTED ? self::STATUS_ACCEPTED : self::STATUS_MODIFIED;
        $rec->instructor_score = $finalscore;
        $rec->instructor_feedback = $finalfeedback;
        $rec->reviewerid = $reviewerid;
        $rec->timemodified = $now;
        $DB->update_record('aigrading_recommendations', $rec);
        self::log_action($recommendationid, $reviewerid, $rec->status, $rec);

        return true;
    }

    /**
     * @param \stdClass $rec Recommendation record.
     * @param float $score
     * @param string $feedback
     * @return void
     */
    protected static function post_assign_grade(\stdClass $rec, float $score, string $feedback): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $cm = get_coursemodule_from_id('assign', $rec->cmid, 0, false, MUST_EXIST);
        $course = get_course($rec->courseid);
        $context = \context_module::instance($cm->id);
        $assign = new \assign($context, $cm, $course);

        $gradedata = new \stdClass();
        $gradedata->userid = $rec->userid;
        $gradedata->grade = $score;
        $gradedata->attemptnumber = -1;
        $gradedata->timemodified = time();
        $gradedata->grader = $rec->reviewerid ?? 0;

        $assign->save_grade($rec->userid, $gradedata);

        if ($feedback !== '') {
            $assign->save_feedback($rec->userid, $feedback);
        }
    }

    /**
     * Append immutable audit log entry.
     *
     * @param int $recommendationid
     * @param int $reviewerid
     * @param string $action
     * @param \stdClass $snapshot
     * @return void
     */
    public static function log_action(int $recommendationid, int $reviewerid, string $action, \stdClass $snapshot): void {
        global $DB;

        $DB->insert_record('aigrading_audit_log', (object) [
            'recommendationid' => $recommendationid,
            'reviewerid' => $reviewerid,
            'action' => $action,
            'detail' => json_encode($snapshot),
            'timecreated' => time(),
        ]);
    }
}
