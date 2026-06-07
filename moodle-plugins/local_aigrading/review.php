<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/form/review_form.php');

$cmid = required_param('cmid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course);
$context = context_module::instance($cm->id);
require_capability('local/aigrading:review', $context);

global $DB;

$assign = $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);
$rec = $DB->get_record('aigrading_recommendations', [
    'assignid' => $assign->id,
    'userid' => $userid,
], '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/aigrading/review.php', ['cmid' => $cmid, 'userid' => $userid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('reviewtitle', 'local_aigrading'));
$PAGE->set_heading(format_string($assign->name));

$form = new \local_aigrading\form\review_form(null, ['recommendation' => $rec]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/aigrading/index.php', ['courseid' => $course->id]));
}

if ($data = $form->get_data()) {
    $reviewerid = (int) $USER->id;
    if (!empty($data->accept)) {
        \local_aigrading\api::apply_decision((int) $rec->id, $reviewerid, \local_aigrading\api::STATUS_ACCEPTED);
    } else if (!empty($data->modify)) {
        \local_aigrading\api::apply_decision(
            (int) $rec->id,
            $reviewerid,
            \local_aigrading\api::STATUS_MODIFIED,
            (float) $data->instructor_score,
            (string) $data->instructor_feedback
        );
    } else if (!empty($data->reject)) {
        \local_aigrading\api::apply_decision((int) $rec->id, $reviewerid, \local_aigrading\api::STATUS_REJECTED);
    }
    redirect(new moodle_url('/local/aigrading/index.php', ['courseid' => $course->id]));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
