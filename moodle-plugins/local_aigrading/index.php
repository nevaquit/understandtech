<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

require_login();

$courseid = optional_param('courseid', 0, PARAM_INT);
$context = $courseid ? context_course::instance($courseid) : context_system::instance();
require_capability('local/aigrading:viewqueue', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/aigrading/index.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('reviewqueue', 'local_aigrading'));
$PAGE->set_heading(get_string('reviewqueue', 'local_aigrading'));

global $DB;

if ($courseid) {
    $pending = \local_aigrading\api::get_pending_for_course($courseid);
} else {
    $pending = array_values($DB->get_records('aigrading_recommendations', ['status' => 'pending'], 'timecreated ASC', '*', 0, 50));
}

$rows = [];
foreach ($pending as $rec) {
    $user = \core_user::get_user($rec->userid);
    $rows[] = (object) [
        'learner' => fullname($user),
        'cmid' => $rec->cmid,
        'score' => $rec->ai_score . ' / ' . $rec->ai_maxscore,
        'reviewurl' => (new moodle_url('/local/aigrading/review.php', ['cmid' => $rec->cmid, 'userid' => $rec->userid]))->out(false),
    ];
}

echo $OUTPUT->header();

if ($rows === []) {
    echo $OUTPUT->notification(get_string('noqueue', 'local_aigrading'), 'info');
} else {
    $table = new html_table();
    $table->head = [get_string('learner', 'local_aigrading'), get_string('score', 'local_aigrading'), ''];
    $table->data = [];
    foreach ($rows as $row) {
        $link = html_writer::link($row->reviewurl, get_string('reviewtitle', 'local_aigrading'));
        $table->data[] = [$row->learner, $row->score, $link];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
