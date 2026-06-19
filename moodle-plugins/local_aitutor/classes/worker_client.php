<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * HTTP client for Cloudflare AI Gateway Worker (tutor + grade + study-plan + content).
 */
class worker_client {

    /**
     * Call Worker /grade with JWT auth.
     *
     * @param int $userid Caller user id for JWT sub claim.
     * @param \context $context Moodle context (course).
     * @param string $submission Learner submission text.
     * @param string $rubric Grading rubric text.
     * @param int|null $cmid Course module id.
     * @return \stdClass Decoded grade response.
     */
    public static function grade(
        int $userid,
        \context $context,
        string $submission,
        string $rubric,
        ?int $cmid = null
    ): \stdClass {
        $payload = json_encode([
            'submission' => $submission,
            'rubric' => $rubric,
            'context' => [
                'courseid' => $context->instanceid,
                'activityid' => $cmid,
            ],
        ]);

        $response = self::post_json_to_endpoint('grade', $userid, $context, $payload, $cmid);
        if (empty($response->score) && !isset($response->score)) {
            if (!empty($response->error)) {
                throw new \moodle_exception('workererror', 'local_aitutor', '', $response->error);
            }
        }

        return $response;
    }

    /**
     * Call Worker /study-plan with JWT auth.
     *
     * @param int $userid Caller user id.
     * @param \context $context Course context for JWT.
     * @param array $weakobjectives Weak objective rows.
     * @param array $misconceptions Objective shortname => reason map.
     * @param array $activities Deterministic activity skeleton.
     * @param int|null $cmid Optional course module id.
     * @return \stdClass Decoded study plan response.
     */
    public static function study_plan(
        int $userid,
        \context $context,
        array $weakobjectives,
        array $misconceptions,
        array $activities,
        ?int $cmid = null
    ): \stdClass {
        $payload = json_encode([
            'weak_objectives' => $weakobjectives,
            'misconceptions' => (object) $misconceptions,
            'activities' => $activities,
            'context' => [
                'courseid' => $context->instanceid,
                'activityid' => $cmid,
            ],
        ]);

        $response = self::post_json_to_endpoint('study-plan', $userid, $context, $payload, $cmid);
        if (!empty($response->error)) {
            throw new \moodle_exception('workererror', 'local_aitutor', '', $response->error);
        }

        return $response;
    }

    /**
     * Call Worker /content with JWT auth.
     *
     * @param int $userid Caller user id.
     * @param \context $context Course context.
     * @param string $drafttype Draft type slug.
     * @param string $sourceexcerpt Source lesson excerpt.
     * @param int|null $cmid Optional course module id.
     * @return \stdClass Decoded content generation response.
     */
    public static function content_generate(
        int $userid,
        \context $context,
        string $drafttype,
        string $sourceexcerpt,
        ?int $cmid = null
    ): \stdClass {
        $payload = json_encode([
            'draft_type' => $drafttype,
            'source_excerpt' => $sourceexcerpt,
            'context' => [
                'courseid' => $context->instanceid,
                'activityid' => $cmid,
            ],
        ]);

        $response = self::post_json_to_endpoint('content', $userid, $context, $payload, $cmid);
        if (!empty($response->error)) {
            throw new \moodle_exception('workererror', 'local_aitutor', '', $response->error);
        }

        return $response;
    }

    /**
     * @return string Grade endpoint URL.
     */
    public static function grade_url(): string {
        return self::endpoint_url('grade');
    }

    /**
     * @return string Study plan endpoint URL.
     */
    public static function study_plan_url(): string {
        return self::endpoint_url('study-plan');
    }

    /**
     * @return string Content generation endpoint URL.
     */
    public static function content_url(): string {
        return self::endpoint_url('content');
    }

    /**
     * @return bool True when worker base URL is configured.
     */
    public static function is_configured(): bool {
        $base = (string) get_config('local_aitutor', 'workerurl');
        return $base !== '' || api::get_worker_secret() !== '';
    }

    /**
     * POST JSON to a Worker endpoint with JWT auth.
     *
     * @param string $endpoint Route suffix (grade, study-plan, content).
     * @param int $userid Caller user id.
     * @param \context $context Moodle context.
     * @param string $body JSON body.
     * @param int|null $cmid Course module id.
     * @return \stdClass
     */
    public static function post_json_to_endpoint(
        string $endpoint,
        int $userid,
        \context $context,
        string $body,
        ?int $cmid = null
    ): \stdClass {
        $url = self::endpoint_url($endpoint);
        $jwt = api::generate_tutor_jwt($userid, $context, $cmid);
        return self::post_json($url, $body, $jwt);
    }

    /**
     * @param string $endpoint Route suffix.
     * @return string Full endpoint URL.
     */
    protected static function endpoint_url(string $endpoint): string {
        $base = (string) get_config('local_aitutor', 'workerurl');
        if ($base === '') {
            $base = 'https://ai.understandtech.app/tutor';
        }
        $root = preg_replace('#/(tutor|grade|study-plan|content)/?$#', '', rtrim($base, '/'));
        if ($root === '') {
            $root = 'https://ai.understandtech.app';
        }
        return $root . '/' . ltrim($endpoint, '/');
    }

    /**
     * @param string $url Endpoint URL.
     * @param string $body JSON body.
     * @param string $jwt Bearer token.
     * @return \stdClass
     */
    protected static function post_json(string $url, string $body, string $jwt): \stdClass {
        $curl = new \curl();
        $curl->setHeader(['Content-Type: application/json', 'Authorization: Bearer ' . $jwt]);

        $raw = $curl->post($url, $body);
        if ($curl->get_errno()) {
            throw new \moodle_exception('workererror', 'local_aitutor', '', $curl->error);
        }

        $decoded = json_decode($raw ?: '{}');
        if (!$decoded) {
            throw new \moodle_exception('workererror', 'local_aitutor', '', 'Invalid JSON from worker');
        }

        return $decoded;
    }
}
