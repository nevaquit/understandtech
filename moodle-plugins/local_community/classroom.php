<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/community/classroom.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('classroom', 'local_community'));
$PAGE->set_heading(get_string('classroom', 'local_community'));

$tracks = \local_community\api::get_classroom_tracks();

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_community/classroom', [
    'tracks' => $tracks,
    'hastracks' => $tracks !== [],
]);
echo $OUTPUT->footer();
