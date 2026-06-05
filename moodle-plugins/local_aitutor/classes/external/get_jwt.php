<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function to fetch tutor JWT.
 */
class get_jwt extends \external_api {

    /**
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course id'),
            'cmid' => new \external_value(PARAM_INT, 'Course module id', VALUE_DEFAULT, 0),
            'conversationuuid' => new \external_value(PARAM_TEXT, 'Conversation uuid', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * @param int $courseid
     * @param int $cmid
     * @param string $conversationuuid
     * @return array
     */
    public static function execute(int $courseid, int $cmid = 0, string $conversationuuid = ''): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'conversationuuid' => $conversationuuid,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/aitutor:use', $context);

        $cmid = $params['cmid'] ?: null;
        $uuid = $params['conversationuuid'] ?: null;
        $token = \local_aitutor\api::generate_tutor_jwt($USER->id, $context, $cmid, $uuid);

        return [
            'token' => $token,
            'workerurl' => get_config('local_aitutor', 'workerurl') ?: 'https://ai.understandtech.app/tutor',
        ];
    }

    /**
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'token' => new \external_value(PARAM_TEXT, 'JWT'),
            'workerurl' => new \external_value(PARAM_URL, 'Worker URL'),
        ]);
    }
}
