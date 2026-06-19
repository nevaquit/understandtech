<?php
// This file is part of Moodle - http://moodle.org/

require('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$draftid = optional_param('draftid', 0, PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/aitutor:managecontent', $context);

$PAGE->set_url(new moodle_url('/local/aitutor/drafts.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('draftsheading', 'local_aitutor'));
$PAGE->set_heading(format_string($course->fullname));

if ($action && $draftid && confirm_sesskey()) {
    $draft = \local_aitutor\content_draft::get($draftid);
    if ($draft && (int) $draft->courseid === $courseid) {
        if ($action === 'publish') {
            \local_aitutor\content_draft::update_status($draftid, \local_aitutor\content_draft::STATUS_PUBLISHED);
            \core\notification::success(get_string('draftpublished', 'local_aitutor'));
        } else if ($action === 'reject') {
            \local_aitutor\content_draft::update_status($draftid, \local_aitutor\content_draft::STATUS_REJECTED);
            \core\notification::info(get_string('draftrejected', 'local_aitutor'));
        } else if ($action === 'delete') {
            \local_aitutor\content_draft::delete($draftid);
            \core\notification::success(get_string('draftdeleted', 'local_aitutor'));
        }
    }
    redirect(new moodle_url('/local/aitutor/drafts.php', ['courseid' => $courseid]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('draftsheading', 'local_aitutor'));
echo html_writer::tag('p', get_string('draftsintro', 'local_aitutor'));

$drafts = \local_aitutor\content_draft::list_for_course($courseid, \local_aitutor\content_draft::STATUS_DRAFT);

if ($drafts === []) {
    echo $OUTPUT->notification(get_string('nodrafts', 'local_aitutor'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('drafttype', 'local_aitutor'),
    get_string('draftstatus', 'local_aitutor'),
    get_string('draftauthor', 'local_aitutor'),
    get_string('draftmodified', 'local_aitutor'),
    get_string('draftactions', 'local_aitutor'),
];
$table->attributes['class'] = 'generaltable';

foreach ($drafts as $draft) {
    $user = core_user::get_user($draft->userid, '*', MUST_EXIST);
    $publishurl = new moodle_url('/local/aitutor/drafts.php', [
        'courseid' => $courseid,
        'action' => 'publish',
        'draftid' => $draft->id,
        'sesskey' => sesskey(),
    ]);
    $rejecturl = new moodle_url('/local/aitutor/drafts.php', [
        'courseid' => $courseid,
        'action' => 'reject',
        'draftid' => $draft->id,
        'sesskey' => sesskey(),
    ]);
    $deleteurl = new moodle_url('/local/aitutor/drafts.php', [
        'courseid' => $courseid,
        'action' => 'delete',
        'draftid' => $draft->id,
        'sesskey' => sesskey(),
    ]);

    $actions = html_writer::link($publishurl, get_string('draftpublish', 'local_aitutor'), ['class' => 'btn btn-sm btn-success me-1']);
    $actions .= html_writer::link($rejecturl, get_string('draftreject', 'local_aitutor'), ['class' => 'btn btn-sm btn-outline-secondary me-1']);
    $actions .= html_writer::link($deleteurl, get_string('draftdelete', 'local_aitutor'), ['class' => 'btn btn-sm btn-outline-danger']);

    $table->data[] = [
        s($draft->draft_type),
        s($draft->status),
        fullname($user),
        userdate($draft->timemodified),
        $actions,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
