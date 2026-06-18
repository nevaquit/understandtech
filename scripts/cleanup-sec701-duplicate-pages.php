<?php
/**
 * Remove duplicate lesson Page activities in SEC701 (course id=3).
 *
 * Keeps the oldest course_module per objective (sy701_X_Y pattern in title).
 * Idempotent: safe to re-run.
 *
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/cleanup-sec701-duplicate-pages.php
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Extract objective shortname key from a lesson page title.
 *
 * @param string $name Page activity name
 * @return string|null e.g. sy701_1_1
 */
function sec701_page_objective_key(string $name): ?string {
    if (preg_match('/\b(sy701_\d+_\d+)\b/i', $name, $m)) {
        return strtolower($m[1]);
    }
    if (preg_match('/(?:SY0-701\s+)?(?:SY701\.)?(\d+)\.(\d+)/i', $name, $m)) {
        return 'sy701_' . $m[1] . '_' . $m[2];
    }
    return null;
}

$courseid = (int) (getenv('SEC701_COURSE_ID') ?: 3);
echo "=== SEC701 duplicate page cleanup course={$courseid} ===\n";

$course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
if (!$course) {
    echo "error=course_missing id={$courseid}\n";
    exit(1);
}

$pages = $DB->get_records_sql(
    "SELECT p.id, p.name, cm.id AS cmid, cm.added
       FROM {page} p
       JOIN {course_modules} cm ON cm.instance = p.id
       JOIN {modules} m ON m.id = cm.module AND m.name = 'page'
      WHERE cm.course = :courseid
   ORDER BY cm.added ASC, cm.id ASC",
    ['courseid' => $courseid]
);

$groups = [];
$unmatched = 0;
foreach ($pages as $page) {
    $key = sec701_page_objective_key($page->name);
    if ($key === null) {
        $unmatched++;
        echo "page_unmatched cmid={$page->cmid} name={$page->name}\n";
        continue;
    }
    $groups[$key][] = $page;
}

$deleted = 0;
foreach ($groups as $key => $members) {
    if (count($members) <= 1) {
        continue;
    }
    array_shift($members);
    foreach ($members as $dup) {
        course_delete_module($dup->cmid);
        echo "page_deleted key={$key} cmid={$dup->cmid} name={$dup->name}\n";
        $deleted++;
    }
}

$wwwroot = (string) ($CFG->wwwroot ?? '');
if (strpos($wwwroot, 'staging') === false) {
    rebuild_course_cache($courseid, true);
} else {
    echo "rebuild_course_cache_skipped reason=staging\n";
}

$remaining = count($pages) - $deleted;
echo "pages_before=" . count($pages) . "\n";
echo "pages_deleted={$deleted}\n";
echo "pages_remaining={$remaining}\n";
echo "pages_unmatched={$unmatched}\n";
echo "=== cleanup complete ===\n";
