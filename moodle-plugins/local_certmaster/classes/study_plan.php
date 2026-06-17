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

        $planjson = json_encode([
            'generated_at' => time(),
            'activities' => $activities,
            'summary' => get_string('studyplansummary', 'local_certmaster'),
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
}
