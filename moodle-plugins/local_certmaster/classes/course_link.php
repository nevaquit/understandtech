<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_certmaster;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolve Moodle course module URLs for certification objectives.
 */
class course_link {

    /** @var array<string, string> */
    private const CERT_TO_COURSE = [
        'security_plus_sy0_701' => 'SEC701',
        'network_plus_n10_009' => 'NET009',
        'comptia_a_plus' => 'APLUS',
    ];

    /**
     * Build candidate page title prefixes for an objective shortname.
     *
     * @param string $objectiveshortname
     * @return string[]
     */
    public static function page_name_patterns(string $objectiveshortname): array {
        $patterns = [];

        if (preg_match('/^sy701_(\d+)_(\d+)$/', $objectiveshortname, $m)) {
            $dot = $m[1] . '.' . $m[2];
            $patterns[] = 'SY0-701 ' . $dot . ':%';
            $patterns[] = 'SY701.' . $dot . ':%';
        } else if (preg_match('/^n10009_(\d+)_(\d+)$/', $objectiveshortname, $m)) {
            $dot = $m[1] . '.' . $m[2];
            $patterns[] = 'N10-009 ' . $dot . ':%';
            $patterns[] = 'N10009.' . $dot . ':%';
        } else if (preg_match('/^ap1101_(\d+)_(\d+)$/', $objectiveshortname, $m)) {
            $dot = $m[1] . '.' . $m[2];
            $patterns[] = '220-1101 ' . $dot . ':%';
            $patterns[] = 'AP1101.' . $dot . ':%';
        } else if (preg_match('/^ap1102_(\d+)_(\d+)$/', $objectiveshortname, $m)) {
            $dot = $m[1] . '.' . $m[2];
            $patterns[] = '220-1102 ' . $dot . ':%';
            $patterns[] = 'AP1102.' . $dot . ':%';
        }

        return $patterns;
    }

    /**
     * @param int $certificationid
     * @param string $objectiveshortname
     * @return string|null Moodle mod/page view URL
     */
    public static function lesson_url_for_objective(int $certificationid, string $objectiveshortname): ?string {
        global $DB;

        $cert = $DB->get_record('certmaster_certifications', ['id' => $certificationid], 'shortname', IGNORE_MISSING);
        if (!$cert) {
            return null;
        }

        $courseshort = self::CERT_TO_COURSE[$cert->shortname] ?? null;
        if ($courseshort === null) {
            return null;
        }

        $courseid = (int) $DB->get_field('course', 'id', ['shortname' => $courseshort], IGNORE_MISSING);
        if ($courseid <= 0) {
            return null;
        }

        foreach (self::page_name_patterns($objectiveshortname) as $like) {
            $cmid = (int) $DB->get_field_sql(
                'SELECT cm.id FROM {course_modules} cm
                   JOIN {modules} m ON m.id = cm.module AND m.name = ?
                   JOIN {page} p ON p.id = cm.instance
                  WHERE cm.course = ? AND cm.deletioninprogress = 0 AND p.name LIKE ?
                  ORDER BY cm.id ASC',
                ['page', $courseid, $like],
                IGNORE_MISSING
            );
            if ($cmid > 0) {
                return (new \moodle_url('/mod/page/view.php', ['id' => $cmid]))->out(false);
            }
        }

        return (new \moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
    }
}
