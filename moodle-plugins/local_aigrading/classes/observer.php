<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_aigrading;

defined('MOODLE_INTERNAL') || die();

/**
 * Assignment submission observer for AI grading.
 */
class observer {

    /**
     * Queue AI grading when an assessable assignment is submitted.
     *
     * @param \mod_assign\event\assessable_submitted $event
     * @return void
     */
    public static function assessable_submitted(\mod_assign\event\assessable_submitted $event): void {
        global $DB;

        if (!get_config('local_aigrading', 'enabled')) {
            return;
        }

        $cmid = (int) $event->contextinstanceid;
        $cm = get_coursemodule_from_id('assign', $cmid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }

        $assign = $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);
        $learnerid = (int) $event->relateduserid;
        if (!$learnerid) {
            $learnerid = (int) $event->userid;
        }

        $submissiontext = self::get_onlinetext_submission((int) $assign->id, $learnerid);
        if ($submissiontext === '') {
            return;
        }

        $rubric = trim((string) $assign->intro) ?: (string) get_config('local_aigrading', 'defaultrubric');
        if ($rubric === '') {
            $rubric = 'Grade on clarity, accuracy, completeness, and professional tone. Scale 0-100.';
        }

        try {
            api::create_recommendation(
                (int) $assign->id,
                $cmid,
                (int) $cm->course,
                $learnerid,
                $submissiontext,
                $rubric
            );
        } catch (\Throwable $e) {
            debugging('local_aigrading worker call failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * @param int $assignid
     * @param int $userid
     * @return string
     */
    protected static function get_onlinetext_submission(int $assignid, int $userid): string {
        global $DB;

        $submission = $DB->get_record('assign_submission', [
            'assignment' => $assignid,
            'userid' => $userid,
            'latest' => 1,
            'status' => 'submitted',
        ]);

        if (!$submission) {
            return '';
        }

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('assignsubmission_onlinetext')) {
            return '';
        }

        $text = $DB->get_record('assignsubmission_onlinetext', ['submission' => $submission->id]);
        if (!$text || empty($text->onlinetext)) {
            return '';
        }

        return trim(strip_tags($text->onlinetext));
    }
}
