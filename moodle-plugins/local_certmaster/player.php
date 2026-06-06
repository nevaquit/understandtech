<?php
// This file is part of Moodle - http://moodle.org/
//
// Signed Stream preview page — video UID from admin setting only (never from query string).

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/certmaster/lib.php');

require_login();

$context = context_system::instance();
require_capability('local/certmaster:viewstream', $context);

$videoid = trim((string) get_config('local_certmaster', 'streamtestvideoid'));
if ($videoid === '') {
    throw new moodle_exception('streamtestvideomissing', 'local_certmaster');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/certmaster/player.php'));
$PAGE->set_title(get_string('streamplayer_title', 'local_certmaster'));
$PAGE->set_heading(get_string('streamplayer_title', 'local_certmaster'));

echo $OUTPUT->header();
echo local_certmaster_render_stream_player($videoid);
echo $OUTPUT->footer();
