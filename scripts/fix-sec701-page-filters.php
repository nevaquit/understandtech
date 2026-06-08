<?php
/**
 * Disable Moodle text filters for SEC701 lesson page modules to prevent filter MUC DB errors.
 *
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/fix-sec701-page-filters.php
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->libdir . '/filterlib.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;

$courseid = 3;
$course = get_course($courseid);
$coursecontext = context_course::instance($courseid);
$modinfo = get_fast_modinfo($course);
$pages = $modinfo->get_instances_of('page');

echo "=== disable filters on SEC701 lesson pages course={$courseid} pages=" . count($pages) . " ===\n";

foreach ($pages as $cm) {
    $context = context_module::instance($cm->id);
    if ($context->contextlevel !== CONTEXT_MODULE) {
        echo "skip_invalid_context cmid={$cm->id} level={$context->contextlevel}\n";
        continue;
    }
    foreach (array_keys(filter_get_active_in_context($context)) as $filtername) {
        filter_set_local_state($filtername, $context->id, TEXTFILTER_OFF);
    }
    echo "filters_disabled cmid={$cm->id} ctx={$context->id} name={$cm->name}\n";
}

filter_manager::reset_caches();
rebuild_course_cache($courseid, true);
echo "filter_cache_reset=1\n";
echo "=== complete ===\n";
