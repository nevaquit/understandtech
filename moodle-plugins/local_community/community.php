<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/community/community.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('community', 'local_community'));
$PAGE->set_heading(get_string('community', 'local_community'));

$feed = \local_community\api::get_community_feed($USER->id);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_community/feed', [
    'entries' => $feed,
    'hasentries' => $feed !== [],
]);
echo $OUTPUT->footer();
