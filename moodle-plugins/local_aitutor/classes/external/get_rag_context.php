<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function for RAG chunk retrieval (Worker or AJAX).
 */
class get_rag_context extends \external_api {

    /**
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course id'),
            'query' => new \external_value(PARAM_TEXT, 'Learner question'),
            'limit' => new \external_value(PARAM_INT, 'Max chunks', VALUE_DEFAULT, 5),
        ]);
    }

    /**
     * @param int $courseid
     * @param string $query
     * @param int $limit
     * @return array
     */
    public static function execute(int $courseid, string $query, int $limit = 5): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'query' => $query,
            'limit' => $limit,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/aitutor:use', $context);

        $chunks = \local_aitutor\rag_context::retrieve(
            $params['courseid'],
            $params['query'],
            min(10, max(1, $params['limit']))
        );

        return ['chunks' => $chunks];
    }

    /**
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'chunks' => new \external_multiple_structure(
                new \external_single_structure([
                    'content' => new \external_value(PARAM_TEXT, 'Chunk text'),
                    'source_type' => new \external_value(PARAM_TEXT, 'Source module type'),
                ])
            ),
        ]);
    }
}
