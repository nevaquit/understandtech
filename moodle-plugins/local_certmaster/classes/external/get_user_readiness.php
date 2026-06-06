<?php
// This file is part of Moodle - http://moodle.org/

namespace local_certmaster\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function returning user certification readiness for live radar refresh.
 */
class get_user_readiness extends \external_api {

    /**
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'certificationid' => new \external_value(PARAM_INT, 'Certification record id'),
        ]);
    }

    /**
     * @param int $certificationid
     * @return array
     */
    public static function execute(int $certificationid): array {
        global $USER;

        self::validate_parameters(self::execute_parameters(), [
            'certificationid' => $certificationid,
        ]);

        $usercontext = \context_user::instance($USER->id);
        self::validate_context($usercontext);
        require_capability('local/certmaster:viewmastery', $usercontext);

        if (!\local_certmaster\api::get_certification($certificationid)) {
            throw new \moodle_exception('invalidcertification', 'local_certmaster');
        }

        $data = \local_certmaster\api::get_user_readiness($USER->id, $certificationid);

        $radar = array_map(static function (array $domain): array {
            return [
                'domain' => $domain['domain'],
                'label' => $domain['label'],
                'score' => $domain['score'],
                'weight' => $domain['weight'],
            ];
        }, $data['radar']);

        return [
            'overall_readiness' => $data['overall_readiness'],
            'radar' => $radar,
        ];
    }

    /**
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'overall_readiness' => new \external_value(PARAM_FLOAT, 'Weighted readiness percentage'),
            'radar' => new \external_multiple_structure(
                new \external_single_structure([
                    'domain' => new \external_value(PARAM_ALPHANUMEXT, 'Domain short name'),
                    'label' => new \external_value(PARAM_TEXT, 'Domain label'),
                    'score' => new \external_value(PARAM_FLOAT, 'Domain mastery score'),
                    'weight' => new \external_value(PARAM_FLOAT, 'Blueprint weight'),
                ]),
                'Per-domain radar telemetry',
                VALUE_OPTIONAL
            ),
        ]);
    }
}
