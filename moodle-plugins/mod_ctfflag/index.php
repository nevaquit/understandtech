<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');

global $DB, $PAGE, $OUTPUT;

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_login($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/ctfflag/index.php', ['id' => $course->id]);
$PAGE->set_title(get_string('modulenameplural', 'mod_ctfflag'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_ctfflag'));

if (!$instances = get_all_instances_in_course('ctfflag', $course)) {
    notice(get_string('noinstances', 'mod_ctfflag'), new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->head = [get_string('name'), get_string('description')];
$table->attributes['class'] = 'generaltable mod_index';

foreach ($instances as $instance) {
    $link = html_writer::link(
        new moodle_url('/mod/ctfflag/view.php', ['id' => $instance->coursemodule]),
        format_string($instance->name)
    );
    $table->data[] = [$link, format_module_intro('ctfflag', $instance, $instance->coursemodule, false)];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
