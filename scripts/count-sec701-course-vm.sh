#!/usr/bin/env bash
sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
chdir('/var/www/moodle');
require '/var/www/moodle/config.php';
global $DB;
$courseid = 3;
$pages = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {page} p
      JOIN {course_modules} cm ON cm.instance = p.id
      JOIN {modules} m ON m.id = cm.module AND m.name = 'page'
     WHERE cm.course = ?",
    [$courseid]
);
$quizzes = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {quiz} q
      JOIN {course_modules} cm ON cm.instance = q.id
      JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
     WHERE cm.course = ?",
    [$courseid]
);
echo "pages={$pages}\n";
echo "quizzes={$quizzes}\n";
echo 'objectives=' . $DB->count_records('certmaster_objectives') . "\n";
echo 'q_obj_links=' . $DB->count_records('certmaster_question_objective') . "\n";
PHP
