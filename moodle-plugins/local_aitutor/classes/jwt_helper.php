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
     * Decode and validate HS256 JWT.
     *
     * @param string $jwt Token string.
     * @param string $secret Signing secret.
     * @return array|null Claims array or null when invalid/expired.
     */
    public static function decode(string $jwt, string $secret): ?array {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerb64, $payloadb64, $sigb64] = $parts;
        $signinginput = $headerb64 . '.' . $payloadb64;
        $expected = self::urlsafe_b64(hash_hmac('sha256', $signinginput, $secret, true));

        if (!hash_equals($expected, $sigb64)) {
            return null;
        }

        $payloadjson = base64_decode(strtr($payloadb64, '-_', '+/'), true);
        if ($payloadjson === false) {
            return null;
        }

        $claims = json_decode($payloadjson, true);
        if (!is_array($claims)) {
            return null;
        }

        $now = time();
        if (!empty($claims['exp']) && (int) $claims['exp'] < $now) {
            return null;
        }
        if (!empty($claims['iat']) && (int) $claims['iat'] > $now + 60) {
            return null;
        }

        return $claims;
    }

    /**
     * @param string $data
     * @return string
     */
    protected static function urlsafe_b64(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param string $data
     * @return string
     */
    protected static function urlsafe_b64_decode(string $data): string {
        $padding = 4 - (strlen($data) % 4);
        if ($padding < 4) {
            $data .= str_repeat('=', $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }
}
