<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Fetch transcript messages for a tutor conversation.
 */
class get_messages extends \external_api {

    /**
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'conversationuuid' => new \external_value(PARAM_TEXT, 'Conversation uuid'),
            'limit' => new \external_value(PARAM_INT, 'Max messages', VALUE_DEFAULT, 50),
        ]);
    }

    /**
     * @param string $conversationuuid
     * @param int $limit
     * @return array
     */
    public static function execute(string $conversationuuid, int $limit = 50): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'conversationuuid' => $conversationuuid,
            'limit' => $limit,
        ]);

        require_login();

        $conversation = \local_aitutor\api::get_user_conversations($USER->id, 100);
        $courseid = 0;
        foreach ($conversation as $record) {
            if ($record->conversationuuid === $params['conversationuuid']) {
                $courseid = (int) $record->courseid;
                break;
            }
        }

        if ($courseid > 0) {
            $context = \context_course::instance($courseid);
            self::validate_context($context);
            require_capability('local/aitutor:use', $context);
        }

        $messages = \local_aitutor\api::get_conversation_messages(
            $USER->id,
            $params['conversationuuid'],
            min(100, max(1, $params['limit']))
        );

        return ['messages' => $messages];
    }

    /**
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'messages' => new \external_multiple_structure(
                new \external_single_structure([
                    'role' => new \external_value(PARAM_TEXT, 'Role'),
                    'content' => new \external_value(PARAM_RAW, 'Content'),
                    'timecreated' => new \external_value(PARAM_INT, 'Created'),
                ])
            ),
        ]);
    }
}
