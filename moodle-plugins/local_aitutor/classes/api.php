<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * AI tutor API — JWT generation and transcript persistence.
 */
class api {

    /**
     * Generate short-lived JWT for AI Worker SSE connection.
     *
     * @param int $userid User id.
     * @param \context $context Moodle context.
     * @param int|null $cmid Course module id.
     * @param string|null $conversationuuid Existing conversation uuid.
     * @return string JWT
     */
    public static function generate_tutor_jwt(
        int $userid,
        \context $context,
        ?int $cmid = null,
        ?string $conversationuuid = null
    ): string {
        $secret = self::get_worker_secret();
        if ($secret === '') {
            throw new \moodle_exception('missingsecret', 'local_aitutor');
        }

        $expiry = (int) get_config('local_aitutor', 'tokenexpiry') ?: 300;
        $iat = time();
        $conversationuuid = $conversationuuid ?: self::uuid4();

        $claims = [
            'sub' => (string) $userid,
            'iss' => 'moodle',
            'aud' => 'ai-worker',
            'iat' => $iat,
            'exp' => $iat + $expiry,
            'context' => [
                'courseid' => $context->instanceid,
                'activityid' => $cmid,
                'conversation_id' => $conversationuuid,
            ],
        ];

        return jwt_helper::encode($claims, $secret);
    }

    /**
     * Validate webhook HMAC and persist transcript messages.
     *
     * @param string $rawbody Raw POST body.
     * @param string $signature Header signature value.
     * @return bool
     */
    public static function receive_transcript_webhook(string $rawbody, string $signature): bool {
        global $DB;

        $secret = self::get_worker_secret();
        if ($secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawbody, $secret);
        if (!hash_equals($expected, $signature)) {
            return false;
        }

        $payload = json_decode($rawbody);
        if (!$payload || empty($payload->conversation_id) || empty($payload->userid)) {
            return false;
        }

        $conversation = $DB->get_record('aitutor_conversations', [
            'conversationuuid' => $payload->conversation_id,
        ]);

        $now = time();
        if (!$conversation) {
            $conversation = (object) [
                'userid' => (int) $payload->userid,
                'courseid' => (int) ($payload->courseid ?? 0),
                'cmid' => $payload->cmid ?? null,
                'conversationuuid' => $payload->conversation_id,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $conversation->id = $DB->insert_record('aitutor_conversations', $conversation);
        } else {
            $conversation->timemodified = $now;
            $DB->update_record('aitutor_conversations', $conversation);
        }

        if (!empty($payload->messages) && is_array($payload->messages)) {
            foreach ($payload->messages as $message) {
                $DB->insert_record('aitutor_messages', (object) [
                    'conversationid' => $conversation->id,
                    'role' => $message->role ?? 'assistant',
                    'content' => $message->content ?? '',
                    'timecreated' => $now,
                ]);
            }
        }

        return true;
    }

    /**
     * @param int $userid
     * @param int $limit
     * @return array
     */
    public static function get_user_conversations(int $userid, int $limit = 20): array {
        global $DB;
        return array_values($DB->get_records('aitutor_conversations', ['userid' => $userid], 'timemodified DESC', '*', 0, $limit));
    }

    /**
     * @return string
     */
    public static function get_worker_secret(): string {
        $env = getenv('AITUTOR_WORKER_SHARED_SECRET');
        if ($env) {
            return $env;
        }
        return (string) get_config('local_aitutor', 'jwtsharedsecret');
    }

    /**
     * @return string
     */
    protected static function uuid4(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
