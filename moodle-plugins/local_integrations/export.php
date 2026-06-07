<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

require_login();

$context = context_user::instance($USER->id);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/integrations/export.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('gdprexport', 'local_integrations'));

$download = optional_param('download', 0, PARAM_INT);

if ($download) {
    require_sesskey();
    $bundle = \local_integrations\privacy\exporter::export_user($USER->id);
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="understandtech-export-' . $USER->id . '.json"');
    echo json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('gdprexport', 'local_integrations'));
echo html_writer::tag('p', get_string('gdprexport_desc', 'local_integrations'));
$url = new moodle_url('/local/integrations/export.php', ['download' => 1, 'sesskey' => sesskey()]);
echo $OUTPUT->single_button($url, get_string('download'), 'get');
echo $OUTPUT->footer();
