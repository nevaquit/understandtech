<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * List recent conversations for current user.
 */
class get_conversations extends \external_api {

    /**
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Filter by course id', VALUE_DEFAULT, 0),
            'limit' => new \external_value(PARAM_INT, 'Max records', VALUE_DEFAULT, 20),
        ]);
    }

    /**
     * @param int $limit
     * @return array
     */
    public static function execute(int $courseid = 0, int $limit = 20): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'limit' => $limit,
        ]);

        require_login();

        if ($params['courseid'] > 0) {
            $context = \context_course::instance($params['courseid']);
            self::validate_context($context);
            require_capability('local/aitutor:use', $context);
        }

        $records = \local_aitutor\api::get_user_conversations($USER->id, $params['limit']);
        $out = [];
        foreach ($records as $record) {
            if ($params['courseid'] > 0 && (int) $record->courseid !== $params['courseid']) {
                continue;
            }
            $out[] = [
                'id' => $record->id,
                'conversationuuid' => $record->conversationuuid,
                'courseid' => $record->courseid,
                'timemodified' => $record->timemodified,
            ];
        }
        return ['conversations' => $out];
    }

    /**
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'conversations' => new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(PARAM_INT, 'Id'),
                    'conversationuuid' => new \external_value(PARAM_TEXT, 'UUID'),
                    'courseid' => new \external_value(PARAM_INT, 'Course'),
                    'timemodified' => new \external_value(PARAM_INT, 'Modified'),
                ])
            ),
        ]);
    }
}
