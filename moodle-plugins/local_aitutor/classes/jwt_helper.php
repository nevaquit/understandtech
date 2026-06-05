<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Minimal HS256 JWT helper (no external dependencies).
 */
class jwt_helper {

    /**
     * Encode JWT payload with HS256.
     *
     * @param array $claims Payload claims.
     * @param string $secret Signing secret.
     * @return string
     */
    public static function encode(array $claims, string $secret): string {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [
            self::urlsafe_b64(json_encode($header)),
            self::urlsafe_b64(json_encode($claims)),
        ];
        $signinginput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signinginput, $secret, true);
        $segments[] = self::urlsafe_b64($signature);
        return implode('.', $segments);
    }

    /**
     * @param string $data
     * @return string
     */
    protected static function urlsafe_b64(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
