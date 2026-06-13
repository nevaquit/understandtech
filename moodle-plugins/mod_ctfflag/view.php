<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/ctfflag/lib.php');
require_once($CFG->dirroot . '/mod/ctfflag/classes/form/submit_form.php');

use mod_ctfflag\local\flag_validator;

global $DB, $USER, $PAGE, $OUTPUT;

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('ctfflag', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('ctfflag', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/ctfflag:view', $context);

$PAGE->set_url('/mod/ctfflag/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_activity_record($instance);
$PAGE->add_body_class('ut-ctfflag-view');

$success = $DB->record_exists('ctfflag_submissions', [
    'ctfflagid' => $instance->id,
    'userid' => $USER->id,
    'success' => 1,
]);

$formurl = new moodle_url('/mod/ctfflag/view.php', ['id' => $cm->id]);
$mform = new mod_ctfflag_submit_form($formurl, ['readonly' => $success]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
}

if ($data = $mform->get_data()) {
    require_capability('mod/ctfflag:submit', $context);
    require_sesskey();

    if ($success) {
        redirect($PAGE->url, get_string('alreadycompleted', 'mod_ctfflag'), null, \core\output\notification::NOTIFY_INFO);
    }

    $matched = flag_validator::matches($data->flagvalue, $instance->expected_flag_regex);

    $submission = (object) [
        'ctfflagid' => $instance->id,
        'userid' => $USER->id,
        'success' => $matched ? 1 : 0,
        'timecreated' => time(),
    ];
    $DB->insert_record('ctfflag_submissions', $submission);

    if ($matched) {
        ctfflag_notify_flag_success($cm, $instance, (int) $USER->id);
        ctfflag_update_completion($cm, $instance, $USER->id, true);
        ctfflag_update_grades($instance, $USER->id, 1.0);
        redirect($PAGE->url, get_string('flagsuccess', 'mod_ctfflag'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    redirect($PAGE->url, get_string('flagincorrect', 'mod_ctfflag'), null, \core\output\notification::NOTIFY_ERROR);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($instance->name));

if (trim($instance->intro)) {
    echo $OUTPUT->box(format_module_intro('ctfflag', $instance, $cm->id), 'generalbox mod_introbox ut-ctfflag-intro');
}

if ($success) {
    echo $OUTPUT->notification(get_string('flagsuccess', 'mod_ctfflag'), 'success');
    echo html_writer::tag('p', get_string('alreadycompleted', 'mod_ctfflag'), ['class' => 'ut-ctfflag-complete']);
} else if (has_capability('mod/ctfflag:submit', $context)) {
    echo html_writer::start_div('ut-ctfflag-form card');
    $mform->display();
    echo html_writer::end_div();
} else {
    echo $OUTPUT->notification(get_string('submitnotallowed', 'mod_ctfflag'), 'info');
}

echo $OUTPUT->footer();
