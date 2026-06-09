<?php
/**
 * Disable Moodle text filters for all SEC701 contexts (course + every module).
 *
 * Prevents filter MUC DB errors on course view and large lesson HTML.
 *
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/fix-sec701-course-filters.php
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->libdir . '/filterlib.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;

$courseid = (int) (getenv('SEC701_COURSE_ID') ?: 0);
if ($courseid <= 0) {
    $byshort = $DB->get_record('course', ['shortname' => 'SEC701']);
    $courseid = $byshort ? (int) $byshort->id : 3;
}
$course = get_course($courseid);
$modinfo = get_fast_modinfo($course);

echo "=== disable filters SEC701 course={$courseid} ===\n";

$contexts = [context_course::instance($courseid)];
foreach ($modinfo->get_cms() as $cm) {
    if ($cm->deletioninprogress) {
        continue;
    }
    $contexts[] = context_module::instance($cm->id);
}

$disabled = 0;
foreach ($contexts as $context) {
    foreach (array_keys(filter_get_active_in_context($context)) as $filtername) {
        filter_set_local_state($filtername, $context->id, TEXTFILTER_OFF);
        $disabled++;
    }
}

filter_manager::reset_caches();
rebuild_course_cache($courseid, true);
purge_all_caches();

echo "contexts=" . count($contexts) . " filter_disables={$disabled}\n";
echo "filter_cache_reset=1 course_cache_rebuilt=1\n";
echo "=== complete ===\n";
