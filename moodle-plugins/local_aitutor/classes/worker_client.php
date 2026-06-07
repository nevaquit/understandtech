<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * HTTP client for Cloudflare AI Gateway Worker (tutor + grade).
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
        $url = self::grade_url();
        $jwt = api::generate_tutor_jwt($userid, $context, $cmid);

        $payload = json_encode([
            'submission' => $submission,
            'rubric' => $rubric,
            'context' => [
                'courseid' => $context->instanceid,
                'activityid' => $cmid,
            ],
        ]);

        $response = self::post_json($url, $payload, $jwt);
        if (empty($response->score) && !isset($response->score)) {
            if (!empty($response->error)) {
                throw new \moodle_exception('workererror', 'local_aitutor', '', $response->error);
            }
        }

        return $response;
    }

    /**
     * @return string Grade endpoint URL.
     */
    public static function grade_url(): string {
        $base = (string) get_config('local_aitutor', 'workerurl');
        if ($base === '') {
            $base = 'https://ai.understandtech.app/tutor';
        }
        return preg_replace('#/tutor/?$#', '/grade', rtrim($base, '/')) ?: 'https://ai.understandtech.app/grade';
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
