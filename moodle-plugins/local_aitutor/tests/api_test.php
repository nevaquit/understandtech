<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for JWT generation, webhook HMAC, and conversation persistence.
 *
 * @covers \local_aitutor\api
 * @covers \local_aitutor\jwt_helper
 */
class api_test extends \advanced_testcase {

    /** @var string */
    protected $secret = 'unit-test-shared-secret-32chars!!';

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('jwtsharedsecret', $this->secret, 'local_aitutor');
    }

    /**
     * JWT encodes expected claims and verifies with jwt_helper.
     *
     * @return void
     */
    public function test_generate_tutor_jwt(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = \context_course::instance($course->id);
        $token = api::generate_tutor_jwt($user->id, $context, null, '11111111-1111-4111-8111-111111111111');

        $claims = jwt_helper::decode($token, $this->secret);
        $this->assertNotNull($claims);
        $this->assertSame((string) $user->id, $claims['sub']);
        $this->assertSame('moodle', $claims['iss']);
        $this->assertSame('ai-worker', $claims['aud']);
        $this->assertSame($course->id, $claims['context']['courseid']);
        $this->assertSame('11111111-1111-4111-8111-111111111111', $claims['context']['conversation_id']);
    }

    /**
     * Webhook rejects invalid HMAC signatures.
     *
     * @return void
     */
    public function test_webhook_rejects_bad_signature(): void {
        $payload = json_encode([
            'conversation_id' => '22222222-2222-4222-8222-222222222222',
            'userid' => 2,
            'courseid' => 3,
            'messages' => [['role' => 'assistant', 'content' => 'Hello']],
        ]);

        $this->assertFalse(api::receive_transcript_webhook($payload, 'invalid-signature'));
    }

    /**
     * Webhook persists conversation messages with valid HMAC.
     *
     * @return void
     */
    public function test_webhook_persists_conversation(): void {
        global $DB;

        $payload = json_encode([
            'conversation_id' => '33333333-3333-4333-8333-333333333333',
            'userid' => 4,
            'courseid' => 5,
            'messages' => [
                ['role' => 'user', 'content' => 'Explain MFA'],
                ['role' => 'assistant', 'content' => 'What do you already know about factors?'],
            ],
        ]);
        $signature = hash_hmac('sha256', $payload, $this->secret);

        $this->assertTrue(api::receive_transcript_webhook($payload, $signature));

        $conversation = $DB->get_record('aitutor_conversations', [
            'conversationuuid' => '33333333-3333-4333-8333-333333333333',
        ]);
        $this->assertNotFalse($conversation);
        $this->assertEquals(2, $DB->count_records('aitutor_messages', ['conversationid' => $conversation->id]));
    }
}
