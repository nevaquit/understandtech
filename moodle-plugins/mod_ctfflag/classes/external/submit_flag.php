<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_ctfflag\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * AJAX flag submission for instant lab grading without full page reload.
 */
class submit_flag extends \external_api {

    /**
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'Course module id'),
            'flagvalue' => new \external_value(PARAM_RAW_TRIMMED, 'Submitted flag value'),
        ]);
    }

    /**
     * @param int $cmid
     * @param string $flagvalue
     * @return array
     */
    public static function execute(int $cmid, string $flagvalue): array {
        global $DB, $USER;

        self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'flagvalue' => $flagvalue,
        ]);

        $cm = get_coursemodule_from_id('ctfflag', $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/ctfflag:submit', $context);

        $instance = $DB->get_record('ctfflag', ['id' => $cm->instance], '*', MUST_EXIST);
        $result = ctfflag_process_flag_submission($cm, $instance, (int) $USER->id, $flagvalue);

        return [
            'status' => $result['status'],
            'success' => $result['success'],
            'message' => $result['message'],
        ];
    }

    /**
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'status' => new \external_value(PARAM_ALPHA, 'Outcome status token'),
            'success' => new \external_value(PARAM_BOOL, 'Whether the flag was correct'),
            'message' => new \external_value(PARAM_TEXT, 'User-facing message'),
        ]);
    }
}
