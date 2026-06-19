<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Generate instructor-reviewed content draft via AI Worker.
 */
class generate_content extends \external_api {

    /**
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course id'),
            'cmid' => new \external_value(PARAM_INT, 'Course module id', VALUE_DEFAULT, 0),
            'drafttype' => new \external_value(PARAM_ALPHANUMEXT, 'Draft type'),
            'sourceexcerpt' => new \external_value(PARAM_RAW, 'Source lesson excerpt'),
        ]);
    }

    /**
     * @param int $courseid
     * @param int $cmid
     * @param string $drafttype
     * @param string $sourceexcerpt
     * @return array
     */
    public static function execute(int $courseid, int $cmid = 0, string $drafttype = '', string $sourceexcerpt = ''): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'drafttype' => $drafttype,
            'sourceexcerpt' => $sourceexcerpt,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/aitutor:managecontent', $context);

        if (trim($params['sourceexcerpt']) === '') {
            throw new \invalid_parameter_exception('sourceexcerpt is required');
        }

        $allowed = ['lesson_summary', 'quiz_draft', 'flashcards', 'scenario_variant'];
        if (!in_array($params['drafttype'], $allowed, true)) {
            throw new \invalid_parameter_exception('Invalid draft type');
        }

        $cmid = $params['cmid'] ?: null;
        $response = \local_aitutor\worker_client::content_generate(
            $USER->id,
            $context,
            $params['drafttype'],
            $params['sourceexcerpt'],
            $cmid
        );

        $draftjson = (array) ($response->draft ?? $response);
        $draftid = \local_aitutor\content_draft::create(
            $params['courseid'],
            $cmid,
            $USER->id,
            $params['drafttype'],
            $params['sourceexcerpt'],
            $draftjson,
            (string) ($response->provider ?? ''),
            (string) ($response->prompt_version ?? '')
        );

        return [
            'draftid' => $draftid,
            'draftjson' => json_encode($draftjson, JSON_UNESCAPED_UNICODE),
            'provider' => (string) ($response->provider ?? ''),
            'promptversion' => (string) ($response->prompt_version ?? ''),
        ];
    }

    /**
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'draftid' => new \external_value(PARAM_INT, 'Draft record id'),
            'draftjson' => new \external_value(PARAM_RAW, 'Generated draft JSON'),
            'provider' => new \external_value(PARAM_TEXT, 'LLM provider'),
            'promptversion' => new \external_value(PARAM_TEXT, 'Prompt version'),
        ]);
    }
}
