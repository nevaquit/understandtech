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
     * @param int $certid Certification id for readiness (required for block display).
     * @return array{
     *     readiness: int,
     *     labs: array<int, array{title: string, url: string, date: string}>,
     *     assessments: array<int, array{title: string, url: string, date: string}>
     * }
     */
    public static function get_portfolio(int $userid, int $certid = 0): array {
        $labs = self::get_completed_labs($userid);
        $assessments = self::get_completed_assessments($userid);
        $readiness = 0;

        if ($certid > 0 && class_exists('\local_certmaster\api')) {
            $data = \local_certmaster\api::get_user_readiness($userid, $certid);
            $readiness = (int) ($data['overall_readiness'] ?? 0);
        }

        return [
            'readiness' => $readiness,
            'labs' => $labs,
            'assessments' => $assessments,
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

    /**
     * Latest finished quiz attempts for the learner (one row per quiz).
     *
     * @param int $userid
     * @return array<int, array{title: string, url: string, date: string}>
     */
    protected static function get_completed_assessments(int $userid): array {
        global $DB;

        $sql = "SELECT q.id AS quizid, q.name, cm.id AS cmid, qa.timefinish
                  FROM {quiz_attempts} qa
                  JOIN {quiz} q ON q.id = qa.quiz
                  JOIN {course_modules} cm ON cm.instance = q.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
                 WHERE qa.userid = :userid
                   AND qa.state = :state
                   AND qa.timefinish > 0
              ORDER BY qa.timefinish DESC";

        $rows = $DB->get_records_sql($sql, [
            'userid' => $userid,
            'state' => 'finished',
        ], 0, 50);

        $items = [];
        $seen = [];
        foreach ($rows as $row) {
            $quizid = (int) $row->quizid;
            if (isset($seen[$quizid])) {
                continue;
            }
            $seen[$quizid] = true;
            $items[] = [
                'title' => format_string($row->name),
                'url' => (new \moodle_url('/mod/quiz/view.php', ['id' => $row->cmid]))->out(false),
                'date' => userdate((int) $row->timefinish),
            ];
            if (count($items) >= 10) {
                break;
            }
        }

        return $items;
    }
}
