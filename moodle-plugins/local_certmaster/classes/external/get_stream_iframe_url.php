<?php
// This file is part of Moodle - http://moodle.org/

namespace local_certmaster\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function to refresh a signed Stream iframe URL (60-second JWT).
 */
class get_stream_iframe_url extends \external_api {

    /**
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'videoid' => new \external_value(PARAM_ALPHANUMEXT, 'Cloudflare Stream video UID'),
        ]);
    }

    /**
     * @param string $videoid
     * @return array
     */
    public static function execute(string $videoid): array {
        self::validate_parameters(self::execute_parameters(), [
            'videoid' => $videoid,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/certmaster:viewstream', $context);

        $url = \local_certmaster\stream_helper::sign_iframe_url($videoid);

        return [
            'iframesrc' => $url,
            'expiresat' => time() + \local_certmaster\stream_helper::JWT_EXPIRY_SECONDS,
        ];
    }

    /**
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'iframesrc' => new \external_value(PARAM_URL, 'Signed iframe URL'),
            'expiresat' => new \external_value(PARAM_INT, 'Unix expiry timestamp'),
        ]);
    }
}
