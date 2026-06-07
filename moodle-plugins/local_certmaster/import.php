<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/form/import_form.php');

require_login();
$context = context_system::instance();
require_capability('local/certmaster:manageframework', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/certmaster/import.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('importobjectives', 'local_certmaster'));
$PAGE->set_heading(get_string('importobjectives', 'local_certmaster'));

$form = new \local_certmaster\form\import_form();

if ($data = $form->get_data()) {
    $fs = get_file_storage();
    $draftcontext = \context_user::instance($USER->id);
    file_save_draft_area_files($data->csvfile, $draftcontext->id, 'user', 'draft', 0);
    $files = $fs->get_area_files($draftcontext->id, 'user', 'draft', $data->csvfile, 'id', false);
    $content = '';
    foreach ($files as $file) {
        $content = $file->get_content();
        break;
    }
    $imported = \local_certmaster\csv_importer::import_from_csv($content);
    redirect($PAGE->url, get_string('importsuccess', 'local_certmaster', $imported), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
