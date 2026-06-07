<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace block_portfolio;

defined('MOODLE_INTERNAL') || die();

/**
 * Portfolio aggregation from labs and certmaster readiness.
 */
class api {

    /**
     * Build portfolio items for a learner.
     *
     * @param int $userid
     * @param int $certid Certification id for readiness (0 = first available).
     * @return array{readiness: int, labs: array<int, array{title: string, url: string, date: string}>}
     */
    public static function get_portfolio(int $userid, int $certid = 0): array {
        global $DB;

        $labs = self::get_completed_labs($userid);
        $readiness = 0;

        if (class_exists('\local_certmaster\api')) {
            if ($certid <= 0) {
                $cert = $DB->get_record('certmaster_certifications', [], 'id ASC', '*', IGNORE_MULTIPLE);
                $certid = $cert ? (int) $cert->id : 0;
            }
            if ($certid > 0) {
                $data = \local_certmaster\api::get_user_readiness($userid, $certid);
                $readiness = (int) ($data['overall_readiness'] ?? 0);
            }
        }

        return [
            'readiness' => $readiness,
            'labs' => $labs,
        ];
    }

    /**
     * @param int $userid
     * @return array<int, array{title: string, url: string, date: string}>
     */
    protected static function get_completed_labs(int $userid): array {
        global $DB;

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('ctfflag_submissions')) {
            return [];
        }

        $sql = "SELECT c.name, c.id AS ctfflagid, cm.id AS cmid, s.timecreated
                  FROM {ctfflag_submissions} s
                  JOIN {ctfflag} c ON c.id = s.ctfflagid
                  JOIN {course_modules} cm ON cm.instance = c.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'ctfflag'
                 WHERE s.userid = :userid AND s.success = 1
              ORDER BY s.timecreated DESC";

        $rows = $DB->get_records_sql($sql, ['userid' => $userid], 0, 10);
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'title' => format_string($row->name),
                'url' => (new \moodle_url('/mod/ctfflag/view.php', ['id' => $row->cmid]))->out(false),
                'date' => userdate((int) $row->timecreated),
            ];
        }

        return $items;
    }
}
