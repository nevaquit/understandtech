<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/community:viewmembers', $context);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/community/members.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('members', 'local_community'));
$PAGE->set_heading(get_string('members', 'local_community'));

$members = \local_community\api::get_members();

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_community/members', [
    'members' => $members,
]);
echo $OUTPUT->footer();
