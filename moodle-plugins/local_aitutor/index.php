<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/aitutor:use', $context);

$PAGE->set_url(new moodle_url('/local/aitutor/index.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('index_heading', 'local_aitutor'));
$PAGE->set_heading(get_string('index_heading', 'local_aitutor'));

$courses = enrol_get_users_courses($USER->id, true, 'id, fullname, shortname, visible', 'fullname ASC');
$cards = [];
foreach ($courses as $course) {
    if ((int) $course->id === SITEID) {
        continue;
    }
    $coursecontext = context_course::instance($course->id);
    if (!has_capability('local/aitutor:use', $coursecontext)) {
        continue;
    }
    $cards[] = [
        'name' => format_string($course->fullname, true, ['context' => $coursecontext]),
        'shortname' => format_string($course->shortname, true, ['context' => $coursecontext]),
        'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('index_heading', 'local_aitutor'));
echo html_writer::tag('p', get_string('index_intro', 'local_aitutor'), ['class' => 'lead']);

if ($cards === []) {
    echo $OUTPUT->notification(get_string('nocourses', 'local_aitutor'), 'info');
} else {
    echo html_writer::start_div('row row-cols-1 row-cols-md-2 g-3 local-aitutor-course-list');
    foreach ($cards as $card) {
        echo html_writer::start_div('col');
        echo html_writer::start_div('card h-100');
        echo html_writer::div($card['name'], 'card-body');
        echo html_writer::start_div('card-footer');
        echo html_writer::link(
            $card['url'],
            get_string('index_opencourse', 'local_aitutor'),
            ['class' => 'btn btn-primary btn-sm']
        );
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
