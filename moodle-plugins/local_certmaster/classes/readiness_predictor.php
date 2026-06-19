<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_certmaster;

defined('MOODLE_INTERNAL') || die();

/**
 * Predictive exam readiness from cohort outcomes.
 */
class readiness_predictor {

    public const MODEL_DETERMINISTIC = 'deterministic';
    public const MODEL_COHORT_ADJUSTED = 'cohort_adjusted';
    public const MIN_COHORT_SAMPLES = 20;

    /**
     * Record an exam outcome for cohort modeling.
     *
     * @param int $userid User id.
     * @param int $certid Certification id.
     * @param bool $passed Whether the learner passed.
     * @param float $readinessat exam Readiness score at exam time (0–100).
     * @param float $lessoncompletionpct Lesson completion percentage (0–100).
     * @return int Record id.
     */
    public static function record_outcome(
        int $userid,
        int $certid,
        bool $passed,
        float $readinessatexam,
        float $lessoncompletionpct
    ): int {
        global $DB;

        if (!$DB->get_manager()->table_exists('certmaster_exam_outcomes')) {
            return 0;
        }

        $now = time();
        return (int) $DB->insert_record('certmaster_exam_outcomes', (object) [
            'userid' => $userid,
            'certificationid' => $certid,
            'passed' => $passed ? 1 : 0,
            'readiness_at_exam' => round($readinessatexam, 2),
            'lesson_completion_pct' => round($lessoncompletionpct, 2),
            'timecreated' => $now,
        ]);
    }

    /**
     * Predict pass probability and cohort-adjusted readiness.
     *
     * @param int $userid User id.
     * @param int $certid Certification id.
     * @param float $deterministicreadiness Blueprint-weighted readiness (0–100).
     * @return array{predictive_readiness: float, pass_probability: float|null, model: string}
     */
    public static function predict(int $userid, int $certid, float $deterministicreadiness): array {
        global $DB;

        unset($userid);

        if (!$DB->get_manager()->table_exists('certmaster_exam_outcomes')) {
            return self::deterministic_result($deterministicreadiness);
        }

        $total = (int) $DB->count_records('certmaster_exam_outcomes', ['certificationid' => $certid]);
        if ($total < self::MIN_COHORT_SAMPLES) {
            return self::deterministic_result($deterministicreadiness);
        }

        $band = self::readiness_band($deterministicreadiness);
        $bandmin = $band;
        $bandmax = $band + 10;

        $stats = $DB->get_record_sql(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) AS passedcount,
                    AVG(readiness_at_exam) AS avground
               FROM {certmaster_exam_outcomes}
              WHERE certificationid = :certid
                AND readiness_at_exam >= :bandmin
                AND readiness_at_exam < :bandmax',
            [
                'certid' => $certid,
                'bandmin' => $bandmin,
                'bandmax' => $bandmax,
            ]
        );

        if (!$stats || (int) $stats->total === 0) {
            $stats = $DB->get_record_sql(
                'SELECT COUNT(*) AS total,
                        SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) AS passedcount,
                        AVG(readiness_at_exam) AS avground
                   FROM {certmaster_exam_outcomes}
                  WHERE certificationid = :certid',
                ['certid' => $certid]
            );
        }

        $passrate = ((int) ($stats->passedcount ?? 0)) / max(1, (int) ($stats->total ?? 1));
        $passprobability = round($passrate * 100, 1);

        $predictive = round(
            $deterministicreadiness * $passrate + (float) ($stats->avground ?? $deterministicreadiness) * (1 - $passrate),
            2
        );
        $predictive = max(0.0, min(100.0, $predictive));

        return [
            'predictive_readiness' => $predictive,
            'pass_probability' => $passprobability,
            'model' => self::MODEL_COHORT_ADJUSTED,
        ];
    }

    /**
     * @param float $readiness
     * @return array{predictive_readiness: float, pass_probability: float|null, model: string}
     */
    protected static function deterministic_result(float $readiness): array {
        return [
            'predictive_readiness' => round($readiness, 2),
            'pass_probability' => null,
            'model' => self::MODEL_DETERMINISTIC,
        ];
    }

    /**
     * @param float $readiness
     * @return float Lower bound of 10-point readiness band.
     */
    protected static function readiness_band(float $readiness): float {
        $clamped = max(0.0, min(99.9, $readiness));
        return floor($clamped / 10) * 10;
    }
}
