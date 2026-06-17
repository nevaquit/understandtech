<?php
/**
 * Disable Moodle text filters for all SEC701 contexts.
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once(__DIR__ . '/lib/moodle-cert-course-filters.php');

$courseid = (int) (getenv('SEC701_COURSE_ID') ?: 0);
if ($courseid <= 0) {
    global $DB;
    $byshort = $DB->get_record('course', ['shortname' => 'SEC701']);
    $courseid = $byshort ? (int) $byshort->id : 3;
}

$course = get_course($courseid);
echo "=== disable filters SEC701 course={$courseid} ===\n";
$stats = ut_disable_cert_course_text_filters($course, true);
echo 'contexts=' . $stats['contexts'] . ' filter_disables=' . $stats['filter_disables'] . "\n";
echo "filter_cache_reset=1 course_cache_rebuilt=1\n";
echo "=== complete ===\n";
