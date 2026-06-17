<?php
/**
 * Disable Moodle text filters for all NET009 contexts (course + every module).
 *
 * Prevents filter MUC DB errors on large lesson HTML (mod/page view).
 *
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/fix-net009-course-filters.php
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->libdir . '/filterlib.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;

$byshort = $DB->get_record('course', ['shortname' => 'NET009']);
if (!$byshort) {
    echo "course_missing shortname=NET009\n";
    exit(1);
}

$courseid = (int) $byshort->id;
$course = get_course($courseid);
$modinfo = get_fast_modinfo($course);

echo "=== disable filters NET009 course={$courseid} ===\n";

/** @var context[] $contexts */
$contexts = [context_course::instance($courseid)];
foreach ($modinfo->get_cms() as $cm) {
    if ($cm->deletioninprogress) {
        continue;
    }
    $contexts[] = context_module::instance($cm->id);
}

$filternames = [];
foreach ($DB->get_records('filter_active') as $row) {
    if ((int) $row->active === TEXTFILTER_DISABLED) {
        continue;
    }
    $filternames[$row->filter] = true;
}
foreach ($contexts as $context) {
    foreach (array_keys(filter_get_active_in_context($context)) as $filtername) {
        $filternames[$filtername] = true;
    }
}

$disabled = 0;
foreach ($contexts as $context) {
    foreach (array_keys($filternames) as $filtername) {
        filter_set_local_state($filtername, $context->id, TEXTFILTER_OFF);
        $disabled++;
    }
}

filter_manager::reset_caches();
rebuild_course_cache($courseid, true);
purge_all_caches();

echo 'contexts=' . count($contexts) . " filter_disables={$disabled}\n";
echo "filter_cache_reset=1 course_cache_rebuilt=1\n";
echo "=== complete ===\n";
