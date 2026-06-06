<?php
// This file is part of Moodle - http://moodle.org/

namespace local_certmaster;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for Stream JWT signing.
 *
 * @covers \local_certmaster\stream_helper
 */
final class stream_helper_test extends \advanced_testcase {

    /** @var string Test RSA private key PEM. */
    private string $testpem = '';

    /** @var string Original stream signing kid config. */
    private string $origkid = '';

    /** @var string Original customer subdomain config. */
    private string $origsubdomain = '';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($key);
        openssl_pkey_export($key, $this->testpem);
        $this->assertNotSame('', $this->testpem);

        $this->origkid = (string) get_config('local_certmaster', 'streamsigningkid');
        $this->origsubdomain = (string) get_config('local_certmaster', 'streamcustomersubdomain');

        set_config('streamsigningkid', 'test-kid-123', 'local_certmaster');
        set_config('streamcustomersubdomain', 'customer-testsubdomain', 'local_certmaster');
        putenv('CF_STREAM_SIGNING_KEY=' . str_replace("\n", '\\n', $this->testpem));
    }

    protected function tearDown(): void {
        putenv('CF_STREAM_SIGNING_KEY');
        set_config('streamsigningkid', $this->origkid, 'local_certmaster');
        set_config('streamcustomersubdomain', $this->origsubdomain, 'local_certmaster');
        parent::tearDown();
    }

    public function test_sign_jwt_has_three_segments_and_expiry(): void {
        $before = time();
        $token = stream_helper::sign_jwt('abc123video');
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $this->assertSame('abc123video', $payload['sub']);
        $this->assertSame('test-kid-123', $payload['kid']);
        $this->assertGreaterThanOrEqual($before + stream_helper::JWT_EXPIRY_SECONDS, $payload['exp']);
        $this->assertLessThanOrEqual($before + stream_helper::JWT_EXPIRY_SECONDS + 2, $payload['exp']);
    }

    public function test_sign_manifest_url_format(): void {
        $url = stream_helper::sign_manifest_url('lessonvid01');
        $this->assertStringStartsWith('https://customer-testsubdomain.cloudflarestream.com/', $url);
        $this->assertStringEndsWith('/manifest/video.m3u8', $url);
    }

    public function test_rejects_invalid_video_id(): void {
        $this->expectException(\invalid_parameter_exception::class);
        stream_helper::sign_manifest_url('../etc/passwd');
    }
}
