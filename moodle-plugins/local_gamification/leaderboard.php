<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gamification/leaderboard.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('leaderboard', 'local_gamification'));
$PAGE->set_heading(get_string('leaderboard', 'local_gamification'));

$entries = \local_gamification\api::get_leaderboard(25);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_gamification/leaderboard', [
    'entries' => $entries,
    'hasentries' => $entries !== [],
]);

echo $OUTPUT->footer();
