<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_gamification;

defined('MOODLE_INTERNAL') || die();

/**
 * Leaderboard and XP helper API.
 */
class api {

    /**
     * Fetch site-wide leaderboard entries for display.
     *
     * @param int $limit Max rows.
     * @return array<int, array{rank: int, name: string, points: int, percent: int}>
     */
    public static function get_leaderboard(int $limit = 20): array {
        global $DB;

        $entries = self::get_xp_leaderboard($limit);
        if ($entries !== []) {
            return $entries;
        }

        return self::get_readiness_leaderboard($limit);
    }

    /**
     * Level Up XP points when block_xp is installed.
     *
     * @param int $limit
     * @return array<int, array{rank: int, name: string, points: int, percent: int}>
     */
    protected static function get_xp_leaderboard(int $limit): array {
        global $DB;

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('block_xp')) {
            return [];
        }

        $sql = "SELECT u.id, u.firstname, u.lastname, x.xp AS points
                  FROM {block_xp} x
                  JOIN {user} u ON u.id = x.userid
                 WHERE u.deleted = 0 AND u.suspended = 0 AND x.xp > 0
              ORDER BY x.xp DESC";

        $rows = $DB->get_records_sql($sql, [], 0, $limit);
        if (!$rows) {
            return [];
        }

        $max = max(array_map(static fn($r) => (int) $r->points, $rows)) ?: 1;
        $entries = [];
        $rank = 1;
        foreach ($rows as $row) {
            $points = (int) $row->points;
            $entries[] = [
                'rank' => $rank++,
                'name' => fullname($row),
                'points' => $points,
                'percent' => (int) round(($points / $max) * 100),
            ];
        }

        return $entries;
    }

    /**
     * Fallback leaderboard from certmaster overall readiness.
     *
     * @param int $limit
     * @return array<int, array{rank: int, name: string, points: int, percent: int}>
     */
    protected static function get_readiness_leaderboard(int $limit): array {
        global $DB;

        if (!class_exists('\local_certmaster\api')) {
            return [];
        }

        $cert = $DB->get_record('certmaster_certifications', [], 'id ASC', '*', IGNORE_MULTIPLE);
        if (!$cert) {
            return [];
        }

        $sql = "SELECT u.id, u.firstname, u.lastname, AVG(m.mastery_score) AS avgscore
                  FROM {certmaster_mastery} m
                  JOIN {user} u ON u.id = m.userid
                 WHERE u.deleted = 0 AND u.suspended = 0
              GROUP BY u.id, u.firstname, u.lastname
              ORDER BY avgscore DESC";

        $rows = $DB->get_records_sql($sql, [], 0, $limit);
        $entries = [];
        $rank = 1;
        foreach ($rows as $row) {
            $points = (int) round((float) $row->avgscore);
            $entries[] = [
                'rank' => $rank++,
                'name' => fullname($row),
                'points' => $points,
                'percent' => min(100, $points),
            ];
        }

        return $entries;
    }

    /**
     * Award XP points via Level Up XP when available.
     *
     * @param int $userid Target user.
     * @param int $courseid Course id (0 for site).
     * @param int $points Points to add.
     * @param string $reason Audit reason.
     * @return bool True when XP was granted.
     */
    public static function award_xp(int $userid, int $courseid, int $points, string $reason): bool {
        if ($points <= 0 || !class_exists('\block_xp\local\factory')) {
            return false;
        }

        try {
            $world = \block_xp\local\factory::get_world($courseid);
            $store = $world->get_store();
            $store->increase($userid, $points);
            return true;
        } catch (\Throwable $e) {
            debugging('local_gamification XP award failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }
}
