<?php
// This file is part of Moodle - http://moodle.org/

namespace local_certmaster;

defined('MOODLE_INTERNAL') || die();

/**
 * Authorize Stream JWT signing — bind videoids to course content or admin preview.
 */
class stream_access {

    /**
     * Ensure the user may receive a signed URL for this video.
     *
     * @param int $userid Requesting user id.
     * @param string $videoid Cloudflare Stream video UID.
     * @param int $courseid Course id (0 = admin preview player only).
     * @return void
     */
    public static function assert_user_can_sign(int $userid, string $videoid, int $courseid = 0): void {
        self::validate_video_id($videoid);

        if ($courseid <= 0) {
            self::assert_preview_access($userid, $videoid);
            return;
        }

        $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$coursecontext) {
            throw new \moodle_exception('invalidcourseid', 'error');
        }

        if (!is_enrolled($coursecontext, $userid)
            && !has_capability('moodle/course:view', $coursecontext, $userid)) {
            throw new \required_capability_exception(
                $coursecontext,
                'moodle/course:view',
                'nopermissions',
                ''
            );
        }

        require_capability('local/certmaster:viewstream', \context_system::instance(), $userid);

        if (!self::video_referenced_in_course($videoid, $courseid)) {
            throw new \moodle_exception('streamvideonotauthorized', 'local_certmaster');
        }
    }

    /**
     * @param int $userid
     * @param string $videoid
     * @return void
     */
    protected static function assert_preview_access(int $userid, string $videoid): void {
        $testid = trim((string) get_config('local_certmaster', 'streamtestvideoid'));
        if ($testid === '' || $videoid !== $testid) {
            throw new \moodle_exception('streamvideonotauthorized', 'local_certmaster');
        }

        require_capability('local/certmaster:viewstream', \context_system::instance(), $userid);
    }

    /**
     * @param string $videoid
     * @return void
     */
    protected static function validate_video_id(string $videoid): void {
        $videoid = trim($videoid);
        if ($videoid === '' || !preg_match('/^[a-zA-Z0-9_-]+$/', $videoid)) {
            throw new \invalid_parameter_exception('Invalid Stream video id');
        }
    }

    /**
     * Whether a Stream UID is referenced in page module content for a course.
     *
     * @param string $videoid Stream UID.
     * @param int $courseid Course id.
     * @return bool
     */
    public static function video_referenced_in_course(string $videoid, int $courseid): bool {
        global $DB;

        $videoid = trim($videoid);
        $pattern = '%' . $DB->sql_like_escape($videoid) . '%';
        $contentlike = $DB->sql_like('p.content', ':pattern', false);

        $sql = "SELECT 1
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'page'
                  JOIN {page} p ON p.id = cm.instance
                 WHERE cm.course = :courseid
                   AND cm.deletioninprogress = 0
                   AND $contentlike";

        return $DB->record_exists_sql($sql, [
            'courseid' => $courseid,
            'pattern' => $pattern,
        ]);
    }
}
