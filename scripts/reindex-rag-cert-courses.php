<?php
/**
 * Reindex RAG embeddings for certification courses (SEC701, NET009, APLUS).
 *
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/reindex-rag-cert-courses.php
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');

global $DB;

$courseshortnames = ['SEC701', 'NET009', 'APLUS'];
$total = 0;

foreach ($courseshortnames as $shortname) {
    $courseid = (int) $DB->get_field('course', 'id', ['shortname' => $shortname], IGNORE_MISSING);
    if ($courseid <= 0) {
        mtrace("Skipping {$shortname}: course not found.");
        continue;
    }

    mtrace("Indexing {$shortname} (course id {$courseid})...");
    $count = \local_aitutor\ingest::index_course($courseid);
    mtrace("  Upserted {$count} chunks.");
    $total += $count;
}

mtrace("Done. Total chunks upserted: {$total}");
