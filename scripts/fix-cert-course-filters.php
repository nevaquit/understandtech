<?php
/**
 * Disable Moodle text filters for a certification course (SEC701, NET009, APLUS).
 *
 * Usage: sudo -u www-data php fix-cert-course-filters.php NET009
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

$shortname = $argv[1] ?? '';
if ($shortname === '') {
    fwrite(STDERR, "Usage: php fix-cert-course-filters.php <course-shortname>\n");
    exit(1);
}

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once(__DIR__ . '/lib/moodle-cert-course-filters.php');

echo "=== disable filters {$shortname} ===\n";

try {
    $stats = ut_disable_cert_course_text_filters_by_shortname($shortname, true);
} catch (Throwable $e) {
    echo 'course_missing shortname=' . $shortname . ' err=' . $e->getMessage() . "\n";
    exit(1);
}

echo 'courseid=' . $stats['courseid'] . ' contexts=' . $stats['contexts']
    . ' filter_disables=' . $stats['filter_disables'] . "\n";
echo "filter_cache_reset=1 course_cache_rebuilt=1\n";
echo "=== complete ===\n";
