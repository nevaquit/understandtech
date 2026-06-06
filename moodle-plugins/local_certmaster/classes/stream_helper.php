<?php
// This file is part of Moodle - http://moodle.org/

namespace local_certmaster;

defined('MOODLE_INTERNAL') || die();

/**
 * Cloudflare Stream signed URL helper — RS256 JWT, 60-second expiry.
 *
 * Signing PEM is read from /etc/moodle/cf-stream-signing-key.pem (deployed from Key Vault)
 * or CF_STREAM_SIGNING_KEY env (single-line PEM with literal \\n sequences).
 * Never expose raw Stream video IDs in learner-facing HTML; always sign server-side.
 */
class stream_helper {

    /** @var int Maximum JWT lifetime in seconds (platform policy). */
    public const JWT_EXPIRY_SECONDS = 60;

    /** @var string Default PEM path on production VM. */
    public const DEFAULT_PEM_PATH = '/etc/moodle/cf-stream-signing-key.pem';

    /**
     * Build a signed HLS manifest URL for a Stream video UID.
     *
     * @param string $videoid Cloudflare Stream video UID (stored server-side only).
     * @return string Signed manifest URL (expires in 60 seconds).
     */
    public static function sign_manifest_url(string $videoid): string {
        $videoid = trim($videoid);
        if ($videoid === '' || !preg_match('/^[a-zA-Z0-9_-]+$/', $videoid)) {
            throw new \invalid_parameter_exception('Invalid Stream video id');
        }

        $subdomain = self::get_customer_subdomain();
        $token = self::sign_jwt($videoid);

        return "https://{$subdomain}.cloudflarestream.com/{$token}/manifest/video.m3u8";
    }

    /**
     * Sign a Stream playback JWT (RS256).
     *
     * @param string $videoid Cloudflare Stream video UID.
     * @param int|null $exp Unix expiry timestamp (default: now + 60s).
     * @return string Compact JWT string.
     */
    public static function sign_jwt(string $videoid, ?int $exp = null): string {
        $kid = self::get_signing_kid();
        $pem = self::get_signing_pem();
        $exp = $exp ?? (time() + self::JWT_EXPIRY_SECONDS);

        $header = ['alg' => 'RS256', 'kid' => $kid];
        $payload = [
            'sub' => $videoid,
            'kid' => $kid,
            'exp' => $exp,
        ];

        return self::encode_rs256($header, $payload, $pem);
    }

    /**
     * @return string Signing key id from Stream dashboard.
     */
    public static function get_signing_kid(): string {
        $kid = trim((string) get_config('local_certmaster', 'streamsigningkid'));
        if ($kid === '') {
            throw new \moodle_exception('streamnotconfigured', 'local_certmaster');
        }
        return $kid;
    }

    /**
     * @return string Customer subdomain (e.g. customer-abc123).
     */
    public static function get_customer_subdomain(): string {
        $subdomain = trim((string) get_config('local_certmaster', 'streamcustomersubdomain'));
        if ($subdomain === '' || !preg_match('/^customer-[a-zA-Z0-9]+$/', $subdomain)) {
            throw new \moodle_exception('streamnotconfigured', 'local_certmaster');
        }
        return $subdomain;
    }

    /**
     * Load Stream signing PEM private key.
     *
     * @return string PEM contents.
     */
    public static function get_signing_pem(): string {
        $envpem = getenv('CF_STREAM_SIGNING_KEY');
        if (is_string($envpem) && $envpem !== '') {
            return self::normalise_pem($envpem);
        }

        $path = self::DEFAULT_PEM_PATH;
        if (is_readable($path)) {
            $contents = file_get_contents($path);
            if ($contents !== false && trim($contents) !== '') {
                return self::normalise_pem($contents);
            }
        }

        throw new \moodle_exception('streamsigningkeymissing', 'local_certmaster');
    }

    /**
     * @param array $header JWT header.
     * @param array $payload JWT payload.
     * @param string $pem PEM private key.
     * @return string
     */
    protected static function encode_rs256(array $header, array $payload, string $pem): string {
        $segments = [
            self::urlsafe_b64(json_encode($header, JSON_UNESCAPED_SLASHES)),
            self::urlsafe_b64(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];
        $signinginput = implode('.', $segments);

        $key = openssl_pkey_get_private($pem);
        if ($key === false) {
            throw new \moodle_exception('streamsigningkeymissing', 'local_certmaster');
        }

        $signature = '';
        if (!openssl_sign($signinginput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new \moodle_exception('streamsignfailed', 'local_certmaster');
        }

        $segments[] = self::urlsafe_b64($signature);
        return implode('.', $segments);
    }

    /**
     * @param string $pem Raw or escaped PEM text.
     * @return string
     */
    protected static function normalise_pem(string $pem): string {
        $pem = str_replace('\\n', "\n", trim($pem));
        if (!str_contains($pem, 'BEGIN')) {
            throw new \moodle_exception('streamsigningkeymissing', 'local_certmaster');
        }
        return $pem;
    }

    /**
     * @param string $data
     * @return string
     */
    protected static function urlsafe_b64(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
