<?php
/**
 * Disable Moodle text filters for all APLUS contexts (course + every module).
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->libdir . '/filterlib.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;

$byshort = $DB->get_record('course', ['shortname' => 'APLUS']);
if (!$byshort) {
    echo "course_missing shortname=APLUS\n";
    exit(1);
}

$courseid = (int) $byshort->id;
$course = get_course($courseid);
$modinfo = get_fast_modinfo($course);

echo "=== disable filters APLUS course={$courseid} ===\n";

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

echo "contexts=" . count($contexts) . " filter_disables={$disabled}\n";
echo "=== complete ===\n";
