<?php
// This file is part of Moodle - http://moodle.org/

namespace local_certmaster;

defined('MOODLE_INTERNAL') || die();

/**
 * Public API for certification readiness data.
 */
class api {
    public const CONFIDENCE_GUESSING = 'guessing';
    public const CONFIDENCE_UNSURE = 'unsure';
    public const CONFIDENCE_CONFIDENT = 'confident';
    public const CONFIDENCE_CERTAIN = 'certain';

    public const DEFAULT_MASTERY = 50.0;

    /**
     * Return certification options for admin select elements (id => label).
     *
     * @return array<int, string>
     */
    public static function get_certification_options(): array {
        global $DB;

        $records = $DB->get_records('certmaster_certifications', null, 'fullname ASC', 'id, fullname, exam_code');
        $options = [];
        foreach ($records as $cert) {
            $label = $cert->fullname;
            if (!empty($cert->exam_code)) {
                $label .= ' (' . $cert->exam_code . ')';
            }
            $options[(int) $cert->id] = $label;
        }

        return $options;
    }

    /**
     * Return certification with domains and objectives.
     *
     * @param int $id Certification id.
     * @return \stdClass|null
     */
    public static function get_certification(int $id): ?\stdClass {
        global $DB;

        $cert = $DB->get_record('certmaster_certifications', ['id' => $id]);
        if (!$cert) {
            return null;
        }

        $cert->domains = $DB->get_records('certmaster_domains', ['certificationid' => $id], 'sortorder ASC');
        foreach ($cert->domains as $domain) {
            $domain->objectives = $DB->get_records('certmaster_objectives', ['domainid' => $domain->id], 'sortorder ASC');
        }

        return $cert;
    }

    /**
     * Compute user readiness for a certification.
     *
     * @param int $userid User id.
     * @param int $certificationid Certification id.
     * @return array{overall_readiness: float, radar: array, domains: array, dangerous_misconceptions: array}
     */
    public static function get_user_readiness(int $userid, int $certificationid): array {
        global $DB;

        $domains = $DB->get_records('certmaster_domains', ['certificationid' => $certificationid], 'sortorder ASC');
        $radar = [];
        $domainresults = [];
        $weighttotal = 0.0;
        $weightedsum = 0.0;

        foreach ($domains as $domain) {
            $objectives = $DB->get_records('certmaster_objectives', ['domainid' => $domain->id]);
            $scores = [];
            foreach ($objectives as $objective) {
                $mastery = $DB->get_record('certmaster_mastery', [
                    'userid' => $userid,
                    'objectiveid' => $objective->id,
                ]);
                $scores[] = $mastery ? (float) $mastery->mastery_score : self::DEFAULT_MASTERY;
            }

            $domainscore = empty($scores) ? self::DEFAULT_MASTERY : array_sum($scores) / count($scores);
            $weight = (float) $domain->blueprint_weight;
            $weighttotal += $weight;
            $weightedsum += $domainscore * $weight;

            $radar[] = [
                'domain' => $domain->shortname,
                'label' => $domain->fullname,
                'score' => round($domainscore, 2),
                'weight' => $weight,
            ];
            $domainresults[] = [
                'domainid' => $domain->id,
                'score' => round($domainscore, 2),
            ];
        }

        $overall = $weighttotal > 0 ? $weightedsum / $weighttotal : self::DEFAULT_MASTERY;

        return [
            'overall_readiness' => round($overall, 2),
            'radar' => $radar,
            'domains' => $domainresults,
            'dangerous_misconceptions' => self::get_dangerous_misconceptions($userid, $certificationid),
        ];
    }

    /**
     * Record confidence for a quiz attempt slot.
     *
     * @param int $attemptid Quiz attempt id.
     * @param int $slot Question slot.
     * @param string $confidence Confidence level.
     * @param bool $iscorrect Whether the answer was correct.
     * @return int Record id.
     */
    public static function record_confidence(int $attemptid, int $slot, string $confidence, bool $iscorrect): int {
        global $DB;

        self::validate_confidence($confidence);

        $existing = $DB->get_record('certmaster_attempt_confidence', [
            'attemptid' => $attemptid,
            'slot' => $slot,
        ]);

        $record = (object) [
            'attemptid' => $attemptid,
            'slot' => $slot,
            'confidence' => $confidence,
            'iscorrect' => $iscorrect ? 1 : 0,
            'timecreated' => time(),
        ];

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('certmaster_attempt_confidence', $record);
            return (int) $existing->id;
        }

        return (int) $DB->insert_record('certmaster_attempt_confidence', $record);
    }

    /**
     * Recalculate mastery for a user/objective pair.
     *
     * @param int $userid User id.
     * @param int $objectiveid Objective id.
     * @return float Updated mastery score.
     */
    public static function recalculate_mastery(int $userid, int $objectiveid): float {
        global $DB;

        $records = $DB->get_records_sql(
            "SELECT c.confidence, c.iscorrect
               FROM {certmaster_attempt_confidence} c
               JOIN {quiz_attempts} qa ON qa.id = c.attemptid
               JOIN {question_attempts} qatt ON qatt.questionusageid = qa.uniqueid AND qatt.slot = c.slot
               JOIN {certmaster_question_objective} qo ON qo.questionid = qatt.questionid
              WHERE qa.userid = :userid AND qo.objectiveid = :objectiveid",
            ['userid' => $userid, 'objectiveid' => $objectiveid]
        );

        $score = self::DEFAULT_MASTERY;
        foreach ($records as $record) {
            $score = self::apply_confidence_delta($score, $record->confidence, (bool) $record->iscorrect);
        }

        $now = time();
        $existing = $DB->get_record('certmaster_mastery', [
            'userid' => $userid,
            'objectiveid' => $objectiveid,
        ]);

        $mastery = (object) [
            'userid' => $userid,
            'objectiveid' => $objectiveid,
            'mastery_score' => $score,
            'attempts_count' => count($records),
            'last_updated' => $now,
        ];

        if ($existing) {
            $mastery->id = $existing->id;
            $DB->update_record('certmaster_mastery', $mastery);
        } else {
            $DB->insert_record('certmaster_mastery', $mastery);
        }

        return $score;
    }

    /**
     * Return confidently incorrect items for remediation.
     *
     * @param int $userid User id.
     * @param int $certificationid Certification id.
     * @param int $limit Max rows.
     * @return array
     */
    public static function get_dangerous_misconceptions(int $userid, int $certificationid, int $limit = 10): array {
        global $DB;

        $sql = "SELECT c.id, c.attemptid, c.slot, c.confidence, c.timecreated, o.shortname AS objective
                  FROM {certmaster_attempt_confidence} c
                  JOIN {quiz_attempts} qa ON qa.id = c.attemptid
                  JOIN {question_attempts} qatt ON qatt.questionusageid = qa.uniqueid AND qatt.slot = c.slot
                  JOIN {certmaster_question_objective} qo ON qo.questionid = qatt.questionid
                  JOIN {certmaster_objectives} o ON o.id = qo.objectiveid
                  JOIN {certmaster_domains} d ON d.id = o.domainid
                 WHERE qa.userid = :userid
                   AND d.certificationid = :certificationid
                   AND c.iscorrect = 0
                   AND c.confidence IN ('confident', 'certain')
              ORDER BY c.timecreated DESC";

        return array_values($DB->get_records_sql($sql, [
            'userid' => $userid,
            'certificationid' => $certificationid,
        ], 0, $limit));
    }

    /**
     * Apply mastery delta for a single confidence response.
     *
     * @param float $current Current mastery.
     * @param string $confidence Confidence level.
     * @param bool $iscorrect Whether answer was correct.
     * @return float Clamped mastery score.
     */
    public static function apply_confidence_delta(float $current, string $confidence, bool $iscorrect): float {
        self::validate_confidence($confidence);

        $deltas = [
            self::CONFIDENCE_CERTAIN => ['correct' => 12, 'incorrect' => -15],
            self::CONFIDENCE_CONFIDENT => ['correct' => 8, 'incorrect' => -10],
            self::CONFIDENCE_UNSURE => ['correct' => 4, 'incorrect' => -5],
            self::CONFIDENCE_GUESSING => ['correct' => 1, 'incorrect' => -2],
        ];

        $key = $iscorrect ? 'correct' : 'incorrect';
        $next = $current + $deltas[$confidence][$key];
        return max(0.0, min(100.0, $next));
    }

    /**
     * @param string $confidence
     * @return void
     */
    protected static function validate_confidence(string $confidence): void {
        $allowed = [
            self::CONFIDENCE_GUESSING,
            self::CONFIDENCE_UNSURE,
            self::CONFIDENCE_CONFIDENT,
            self::CONFIDENCE_CERTAIN,
        ];
        if (!in_array($confidence, $allowed, true)) {
            throw new \invalid_parameter_exception('Invalid confidence value');
        }
    }
}
