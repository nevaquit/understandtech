<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds learner context for the AI tutor (white paper §3.1).
 *
 * Summarizes current activity, exam readiness, and recent quiz performance without
 * exposing assessment answers, flags, or hidden question data.
 */
class context_builder {

    /**
     * Build a JSON-serializable learner context block for the Worker prompt.
     *
     * @param int $userid Learner user id.
     * @param int $courseid Course id.
     * @param int|null $cmid Current course-module id when on an activity page.
     * @return array<string, mixed>
     */
    public static function build(int $userid, int $courseid, ?int $cmid = null): array {
        $context = [
            'courseid' => $courseid,
            'activity' => self::describe_activity($courseid, $cmid),
            'readiness' => self::describe_readiness($userid, $courseid),
            'quiz_summary' => self::describe_quiz_performance($userid, $courseid),
        ];

        return $context;
    }

    /**
     * @param int $courseid
     * @param int|null $cmid
     * @return array<string, mixed>|null
     */
    protected static function describe_activity(int $courseid, ?int $cmid): ?array {
        if (!$cmid) {
            return null;
        }

        try {
            $cm = get_coursemodule_from_id(null, $cmid, $courseid, false, MUST_EXIST);
        } catch (\Throwable $e) {
            return null;
        }

        $modcontext = \context_module::instance((int) $cm->id);

        return [
            'modname' => $cm->modname,
            'name' => format_string($cm->name, true, ['context' => $modcontext]),
            'section' => (int) $cm->sectionnum,
        ];
    }

    /**
     * @param int $userid
     * @param int $courseid
     * @return array<string, mixed>|null
     */
    protected static function describe_readiness(int $userid, int $courseid): ?array {
        if (!class_exists('\local_certmaster\api')) {
            return null;
        }

        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid], 'shortname', IGNORE_MISSING);
        $cert = null;
        if ($course && !empty($course->shortname)) {
            $cert = $DB->get_record(
                'certmaster_certifications',
                ['shortname' => $course->shortname],
                'id, fullname, exam_code',
                IGNORE_MISSING
            );
        }
        if (!$cert) {
            $cert = $DB->get_record('certmaster_certifications', [], 'id ASC', 'id, fullname, exam_code', IGNORE_MULTIPLE);
        }

        if (!$cert) {
            return null;
        }

        $readiness = \local_certmaster\api::get_user_readiness($userid, (int) $cert->id);
        $misconceptions = array_slice($readiness['dangerous_misconceptions'] ?? [], 0, 3);

        return [
            'certification' => $cert->fullname,
            'exam_code' => $cert->exam_code ?? '',
            'overall_readiness_percent' => (int) round($readiness['overall_readiness'] ?? 0),
            'focus_areas' => array_map(static function(array $item): string {
                return (string) ($item['label'] ?? $item['name'] ?? '');
            }, $misconceptions),
        ];
    }

    /**
     * @param int $userid
     * @param int $courseid
     * @return array<string, mixed>|null
     */
    protected static function describe_quiz_performance(int $userid, int $courseid): ?array {
        global $DB;

        $sql = "SELECT q.name, qa.sumgrades, q.sumgrades AS maxgrade, qa.timefinish
                  FROM {quiz_attempts} qa
                  JOIN {quiz} q ON q.id = qa.quiz
                  JOIN {course_modules} cm ON cm.instance = q.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
                 WHERE qa.userid = :userid
                   AND q.course = :courseid
                   AND qa.state = 'finished'
                   AND qa.preview = 0
              ORDER BY qa.timefinish DESC";

        $attempts = $DB->get_records_sql($sql, ['userid' => $userid, 'courseid' => $courseid], 0, 5);
        if (!$attempts) {
            return null;
        }

        $rows = [];
        foreach ($attempts as $attempt) {
            $max = (float) ($attempt->maxgrade ?: 0);
            $score = (float) ($attempt->sumgrades ?: 0);
            $percent = $max > 0 ? (int) round(($score / $max) * 100) : null;
            $rows[] = [
                'quiz' => format_string($attempt->name),
                'score_percent' => $percent,
                'finished_at' => (int) $attempt->timefinish,
            ];
        }

        return [
            'recent_attempts' => $rows,
        ];
    }
}
