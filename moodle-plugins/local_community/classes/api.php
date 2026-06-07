<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_community;

defined('MOODLE_INTERNAL') || die();

/**
 * Skool-equivalent community data aggregation.
 */
class api {

    /**
     * Recent forum discussions across enrolled courses.
     *
     * @param int $userid
     * @param int $limit
     * @return array<int, array{subject: string, author: string, url: string, date: string}>
     */
    public static function get_community_feed(int $userid, int $limit = 15): array {
        global $DB;

        $courses = enrol_get_users_courses($userid, true, 'id, fullname', 'fullname ASC');
        if (!$courses) {
            return [];
        }

        $courseids = array_keys($courses);
        list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $params['lim'] = $limit;

        $sql = "SELECT d.id, d.name AS subject, d.course, d.timemodified, u.firstname, u.lastname
                  FROM {forum_discussions} d
                  JOIN {forum} f ON f.id = d.forum
                  JOIN {user} u ON u.id = d.userid
                 WHERE f.course $insql
              ORDER BY d.timemodified DESC";

        $rows = $DB->get_records_sql($sql, $params, 0, $limit);
        $feed = [];
        foreach ($rows as $row) {
            $feed[] = [
                'subject' => format_string($row->subject),
                'author' => fullname($row),
                'url' => (new \moodle_url('/mod/forum/discuss.php', ['d' => $row->id]))->out(false),
                'date' => userdate((int) $row->timemodified),
            ];
        }

        return $feed;
    }

    /**
     * Certification track cards for classroom carousel.
     *
     * @return array<int, array{title: string, examcode: string, url: string}>
     */
    public static function get_classroom_tracks(): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('certmaster_certifications')) {
            return [];
        }

        $certs = $DB->get_records('certmaster_certifications', null, 'fullname ASC');
        $tracks = [];
        foreach ($certs as $cert) {
            $tracks[] = [
                'title' => format_string($cert->fullname),
                'examcode' => $cert->exam_code,
                'url' => (new \moodle_url('/local/certmaster/index.php', ['certid' => $cert->id]))->out(false),
            ];
        }

        return $tracks;
    }

    /**
     * Site members with optional readiness score.
     *
     * @param int $limit
     * @return array<int, array{name: string, readiness: int, profileurl: string}>
     */
    public static function get_members(int $limit = 30): array {
        global $DB;

        $users = $DB->get_records_sql(
            "SELECT id, firstname, lastname FROM {user}
              WHERE deleted = 0 AND suspended = 0 AND id > 2
           ORDER BY lastaccess DESC",
            [],
            0,
            $limit
        );

        $cert = null;
        if (class_exists('\local_certmaster\api') && $DB->get_manager()->table_exists('certmaster_certifications')) {
            $cert = $DB->get_record('certmaster_certifications', [], 'id ASC', '*', IGNORE_MULTIPLE);
        }

        $members = [];
        foreach ($users as $user) {
            $readiness = 0;
            if ($cert) {
                $data = \local_certmaster\api::get_user_readiness((int) $user->id, (int) $cert->id);
                $readiness = (int) ($data['overall_readiness'] ?? 0);
            }
            $members[] = [
                'name' => fullname($user),
                'readiness' => $readiness,
                'profileurl' => (new \moodle_url('/user/profile.php', ['id' => $user->id]))->out(false),
            ];
        }

        return $members;
    }
}
