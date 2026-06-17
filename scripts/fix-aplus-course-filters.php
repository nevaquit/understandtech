<?php
/**
 * Disable Moodle text filters for all APLUS contexts.
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once(__DIR__ . '/lib/moodle-cert-course-filters.php');

echo "=== disable filters APLUS ===\n";
$stats = ut_disable_cert_course_text_filters_by_shortname('APLUS', true);
echo 'contexts=' . $stats['contexts'] . ' filter_disables=' . $stats['filter_disables'] . "\n";
echo "filter_cache_reset=1 course_cache_rebuilt=1\n";
echo "=== complete ===\n";
