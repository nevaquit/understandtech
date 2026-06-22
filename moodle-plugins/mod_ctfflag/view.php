<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/ctfflag/lib.php');
require_once($CFG->dirroot . '/mod/ctfflag/classes/form/submit_form.php');

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

$success = ctfflag_user_has_success((int) $instance->id, (int) $USER->id);

$formurl = new moodle_url('/mod/ctfflag/view.php', ['id' => $cm->id]);
$mform = new mod_ctfflag_submit_form($formurl, ['readonly' => $success]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
}

if ($data = $mform->get_data()) {
    require_capability('mod/ctfflag:submit', $context);
    require_sesskey();

    $result = ctfflag_process_flag_submission($cm, $instance, (int) $USER->id, $data->flagvalue);

    if ($result['status'] === 'ratelimited') {
        redirect($PAGE->url, $result['message'], null, \core\output\notification::NOTIFY_ERROR);
    }

    if ($result['success']) {
        $type = $result['status'] === 'alreadycompleted'
            ? \core\output\notification::NOTIFY_INFO
            : \core\output\notification::NOTIFY_SUCCESS;
        redirect($PAGE->url, $result['message'], null, $type);
    }

    redirect($PAGE->url, $result['message'], null, \core\output\notification::NOTIFY_ERROR);
}

echo $OUTPUT->header();

echo html_writer::start_div('ut-lab-shell');
echo html_writer::start_div('ut-lab-header');
echo html_writer::tag('h1', format_string($instance->name), ['class' => 'ut-lab-title']);
echo html_writer::tag(
    'p',
    get_string('labworkspacehint', 'mod_ctfflag'),
    ['class' => 'ut-lab-subtitle']
);
echo html_writer::end_div();

echo html_writer::start_div('ut-lab-grid');

echo html_writer::start_div('ut-lab-instructions', ['aria-label' => get_string('labinstructions', 'mod_ctfflag')]);
if (trim($instance->intro)) {
    echo $OUTPUT->box(format_module_intro('ctfflag', $instance, $cm->id), 'generalbox ut-ctfflag-intro');
}
echo html_writer::end_div();

echo html_writer::start_div('ut-lab-workspace' . ($success ? ' ut-lab-workspace--complete' : ''));
echo html_writer::start_div('ut-lab-flag-panel card ut-ctfflag-form');

if ($success) {
    echo $OUTPUT->notification(get_string('flagsuccess', 'mod_ctfflag'), 'success');
    echo html_writer::tag('p', get_string('alreadycompleted', 'mod_ctfflag'), ['class' => 'ut-ctfflag-complete']);
} else if (has_capability('mod/ctfflag:submit', $context)) {
    echo html_writer::tag('h2', get_string('submitflag', 'mod_ctfflag'), ['class' => 'ut-lab-panel-title']);
    echo html_writer::tag(
        'div',
        '',
        [
            'id' => 'ut-lab-feedback',
            'class' => 'ut-lab-feedback',
            'role' => 'status',
            'aria-live' => 'polite',
        ]
    );
    $mform->display();
} else {
    echo $OUTPUT->notification(get_string('submitnotallowed', 'mod_ctfflag'), 'info');
}

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

$PAGE->requires->js_call_amd('mod_ctfflag/lab_interactives', 'init');

if (!$success && has_capability('mod/ctfflag:submit', $context)) {
    $PAGE->requires->js_call_amd('mod_ctfflag/lab_workspace', 'init', [
        'cmid' => $cm->id,
        'completed' => false,
        'formatInvalid' => get_string('flagformatinvalid', 'mod_ctfflag'),
        'submitting' => get_string('flagsubmitting', 'mod_ctfflag'),
    ]);
}

echo $OUTPUT->footer();
