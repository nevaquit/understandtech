<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Feature support for mod_ctfflag.
 *
 * @param string $feature FEATURE_* constant
 * @return bool|null
 */
function ctfflag_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return false;
        default:
            return null;
    }
}

/**
 * Add a ctfflag instance.
 *
 * @param stdClass $data Form data.
 * @param moodleform|null $mform Form instance.
 * @return int|false New instance id.
 */
function ctfflag_add_instance(stdClass $data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    if (empty($data->expected_flag_regex)) {
        $data->expected_flag_regex = 'UT\\{[A-Za-z0-9_\\-]+\\}';
    }

    $id = $DB->insert_record('ctfflag', $data);
    return $id ? (int) $id : false;
}

/**
 * Update a ctfflag instance.
 *
 * @param stdClass $data Form data.
 * @param moodleform|null $mform Form instance.
 * @return bool
 */
function ctfflag_update_instance(stdClass $data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    return $DB->update_record('ctfflag', $data);
}

/**
 * Delete a ctfflag instance.
 *
 * @param int $id Instance id.
 * @return bool
 */
function ctfflag_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('ctfflag', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('ctfflag_submissions', ['ctfflagid' => $id]);
    $DB->delete_records('ctfflag', ['id' => $id]);
    return true;
}

/**
 * Mark activity completion when a flag is captured.
 *
 * @param stdClass $cm Course module.
 * @param stdClass $instance Activity instance.
 * @param int $userid User id.
 * @param bool $success Whether the flag matched.
 * @return void
 */
function ctfflag_update_completion(stdClass $cm, stdClass $instance, int $userid, bool $success): void {
    if (!$success || empty($instance->completion_required)) {
        return;
    }

    $completion = new completion_info($cm->course);
    if ($completion->is_enabled($cm) != COMPLETION_TRACKING_NONE) {
        $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
    }
}

/**
 * Update gradebook entry for a user.
 *
 * @param stdClass $instance Activity instance.
 * @param int $userid User id.
 * @param float $grade Raw grade (0.0 or 1.0).
 * @return void
 */
function ctfflag_update_grades(stdClass $instance, int $userid, float $grade): void {
    ctfflag_grade_item_update($instance, $userid);
    $grades = (object) [
        'userid' => $userid,
        'rawgrade' => $grade,
    ];
    ctfflag_grade_item_update($instance, $grades);
}

/**
 * Create or update grade item.
 *
 * @param stdClass $instance Activity instance.
 * @param mixed $grades Optional grade object or user id.
 * @return int GRADE_UPDATE_* constant.
 */
function ctfflag_grade_item_update(stdClass $instance, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = [
        'itemname' => $instance->name,
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => 1,
        'grademin' => 0,
    ];

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/ctfflag', $instance->course, 'mod', 'ctfflag', $instance->id, 0, $grades, $params);
}

/**
 * Notify readiness pipeline that a learner captured the lab flag.
 *
 * @param stdClass $cm Course-module record.
 * @param stdClass $instance ctfflag instance record.
 * @return void
 */
function ctfflag_notify_flag_success(stdClass $cm, stdClass $instance): void {
    $event = \mod_ctfflag\event\flag_submitted::create([
        'objectid' => $instance->id,
        'context' => context_module::instance($cm->id),
        'relateduserid' => null,
        'other' => [
            'cmid' => $cm->id,
        ],
    ]);
    $event->trigger();
}
