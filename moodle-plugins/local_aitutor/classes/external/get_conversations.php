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
            'limit' => new \external_value(PARAM_INT, 'Max records', VALUE_DEFAULT, 20),
        ]);
    }

    /**
     * @param int $limit
     * @return array
     */
    public static function execute(int $limit = 20): array {
        global $USER;

        self::validate_parameters(self::execute_parameters(), ['limit' => $limit]);
        require_login();
        require_capability('local/aitutor:use', \context_system::instance());

        $records = \local_aitutor\api::get_user_conversations($USER->id, $limit);
        $out = [];
        foreach ($records as $record) {
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
