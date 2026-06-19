<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_certmaster;

defined('MOODLE_INTERNAL') || die();

/**
 * Adaptive study plan builder from weakest blueprint-weighted objectives.
 */
class study_plan {

    /**
     * @param int $userid
     * @param int $certificationid
     * @return void
     */
    public static function generate_for_user(int $userid, int $certificationid): void {
        global $DB;

        if (!$DB->get_manager()->table_exists('certmaster_study_plans')) {
            return;
        }

        $weak = self::get_weakest_objectives($userid, $certificationid, 5);
        if ($weak === []) {
            return;
        }

        $misconceptions = self::misconception_map($userid, $certificationid);
        $activities = [];
        foreach ($weak as $objective) {
            $shortname = $objective['shortname'];
            $reason = $misconceptions[$shortname]
                ?? self::reason_for_score((float) $objective['score']);
            $activities[] = [
                'objective' => $shortname,
                'title' => $objective['fullname'],
                'type' => 'lesson_review',
                'minutes' => 25,
                'reason' => $reason,
                'url' => course_link::lesson_url_for_objective($certificationid, $shortname),
                'mastery_score' => round((float) $objective['score'], 1),
            ];
        }

        $summary = get_string('studyplansummary', 'local_certmaster');
        $activities = self::enrich_with_llm($userid, $certificationid, $weak, $misconceptions, $activities, $summary);

        $planjson = json_encode([
            'generated_at' => time(),
            'activities' => $activities,
            'summary' => $summary,
        ]);

        $existing = $DB->get_record('certmaster_study_plans', [
            'userid' => $userid,
            'certificationid' => $certificationid,
        ]);

        $now = time();
        if ($existing) {
            $existing->weakobjectives = json_encode($weak);
            $existing->planjson = $planjson;
            $existing->timemodified = $now;
            $DB->update_record('certmaster_study_plans', $existing);
        } else {
            $DB->insert_record('certmaster_study_plans', (object) [
                'userid' => $userid,
                'certificationid' => $certificationid,
                'weakobjectives' => json_encode($weak),
                'planjson' => $planjson,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
    }

    /**
     * @param int $userid
     * @param int $certificationid
     * @return array<string, string> objective shortname => reason
     */
    protected static function misconception_map(int $userid, int $certificationid): array {
        $map = [];
        foreach (api::get_dangerous_misconceptions($userid, $certificationid, 20) as $row) {
            $obj = $row->objective ?? '';
            if ($obj !== '' && !isset($map[$obj])) {
                $map[$obj] = get_string('studyplanreasonmisconception', 'local_certmaster');
            }
        }
        return $map;
    }

    /**
     * @param float $score
     * @return string
     */
    protected static function reason_for_score(float $score): string {
        if ($score < 40) {
            return get_string('studyplanreasonlow', 'local_certmaster');
        }
        if ($score < 60) {
            return get_string('studyplanreasonbuilding', 'local_certmaster');
        }
        return get_string('studyplanreasonmaintain', 'local_certmaster');
    }

    /**
     * @param int $userid
     * @param int $certificationid
     * @param int $limit
     * @return array<int, array{shortname: string, fullname: string, score: float}>
     */
    protected static function get_weakest_objectives(int $userid, int $certificationid, int $limit): array {
        global $DB;

        $sql = "SELECT o.shortname, o.fullname, COALESCE(m.mastery_score, 0) AS score, d.blueprint_weight
                  FROM {certmaster_objectives} o
                  JOIN {certmaster_domains} d ON d.id = o.domainid
             LEFT JOIN {certmaster_mastery} m ON m.objectiveid = o.id AND m.userid = :userid
                 WHERE d.certificationid = :certid
              ORDER BY (COALESCE(m.mastery_score, 0) * d.blueprint_weight) ASC, d.blueprint_weight DESC";

        $rows = $DB->get_records_sql($sql, ['userid' => $userid, 'certid' => $certificationid], 0, $limit);
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'shortname' => $row->shortname,
                'fullname' => $row->fullname,
                'score' => (float) $row->score,
            ];
        }
        return $out;
    }

    /**
     * Merge LLM enrichment from Worker when configured; fallback to deterministic plan on failure.
     *
     * @param int $userid
     * @param int $certificationid
     * @param array $weak
     * @param array<string, string> $misconceptions
     * @param array $activities Deterministic activities with URLs.
     * @param string $summary Summary string (updated by reference).
     * @return array Enriched activities.
     */
    protected static function enrich_with_llm(
        int $userid,
        int $certificationid,
        array $weak,
        array $misconceptions,
        array $activities,
        string &$summary
    ): array {
        if (!class_exists('\local_aitutor\worker_client')
            || !\local_aitutor\worker_client::is_configured()) {
            return $activities;
        }

        global $DB;

        $cert = $DB->get_record('certmaster_certifications', ['id' => $certificationid], 'shortname', IGNORE_MISSING);
        if (!$cert) {
            return $activities;
        }

        $courseshort = course_link::course_shortname_for_cert($cert->shortname);
        if ($courseshort === null) {
            return $activities;
        }

        $courseid = (int) $DB->get_field('course', 'id', ['shortname' => $courseshort], IGNORE_MISSING);
        if ($courseid <= 0) {
            return $activities;
        }

        try {
            $context = \context_course::instance($courseid);
            $skeleton = array_map(static function (array $activity): array {
                return [
                    'objective' => $activity['objective'],
                    'title' => $activity['title'],
                    'type' => $activity['type'],
                    'minutes' => $activity['minutes'],
                    'reason' => $activity['reason'],
                    'mastery_score' => $activity['mastery_score'],
                ];
            }, $activities);

            $response = \local_aitutor\worker_client::study_plan(
                $userid,
                $context,
                $weak,
                $misconceptions,
                $skeleton
            );

            if (!empty($response->summary)) {
                $summary = (string) $response->summary;
            }

            if (empty($response->activities) || !is_array($response->activities)) {
                return $activities;
            }

            $byobjective = [];
            foreach ($activities as $activity) {
                $byobjective[$activity['objective']] = $activity;
            }

            $merged = [];
            foreach ($response->activities as $llmactivity) {
                $obj = is_object($llmactivity) ? (array) $llmactivity : $llmactivity;
                $objective = (string) ($obj['objective'] ?? '');
                if ($objective === '' || !isset($byobjective[$objective])) {
                    continue;
                }
                $base = $byobjective[$objective];
                $merged[] = [
                    'objective' => $objective,
                    'title' => (string) ($obj['title'] ?? $base['title']),
                    'type' => (string) ($obj['type'] ?? $base['type']),
                    'minutes' => (int) ($obj['minutes'] ?? $base['minutes']),
                    'reason' => (string) ($obj['reason'] ?? $base['reason']),
                    'url' => $base['url'],
                    'mastery_score' => $base['mastery_score'],
                ];
            }

            return $merged !== [] ? $merged : $activities;
        } catch (\Throwable $e) {
            debugging('Study plan LLM enrichment skipped: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return $activities;
        }
    }
}
