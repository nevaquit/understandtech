<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Feature support for mod_ctfflag (stub — full implementation pending Phase 3).
 *
 * @param string $feature FEATURE_* constant
 * @return bool|null
 */
function ctfflag_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return false;
        default:
            return null;
    }
}

/**
 * Add a ctfflag instance (stub).
 *
 * @param stdClass $data
 * @param moodleform|null $mform
 * @return int|false
 */
function ctfflag_add_instance(stdClass $data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    return $DB->insert_record('ctfflag', $data);
}

/**
 * Update a ctfflag instance (stub).
 *
 * @param stdClass $data
 * @param moodleform|null $mform
 * @return bool
 */
function ctfflag_update_instance(stdClass $data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    return $DB->update_record('ctfflag', $data);
}

/**
 * Delete a ctfflag instance (stub).
 *
 * @param int $id
 * @return bool
 */
function ctfflag_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('ctfflag', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('ctfflag', ['id' => $id]);
    return true;
}

/**
 * Notify readiness pipeline that a learner captured the lab flag (stub).
 *
 * Call from the future flag submission handler when validation succeeds.
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
