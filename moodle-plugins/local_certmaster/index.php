<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

require_login();

$certid = optional_param('certid', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/certmaster/index.php', ['certid' => $certid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_certmaster'));
$PAGE->set_heading(get_string('pluginname', 'local_certmaster'));

global $DB;

$certs = $DB->get_records('certmaster_certifications', null, 'fullname ASC');
$cards = [];
foreach ($certs as $cert) {
    $readiness = \local_certmaster\api::get_user_readiness($USER->id, (int) $cert->id);
    $cards[] = [
        'id' => $cert->id,
        'title' => format_string($cert->fullname),
        'examcode' => $cert->exam_code,
        'readiness' => (int) ($readiness['overall_readiness'] ?? 0),
        'selected' => $certid === (int) $cert->id,
    ];
}

echo $OUTPUT->header();

if ($certid && isset($certs[$certid])) {
    $data = \local_certmaster\api::get_user_readiness($USER->id, $certid);
    echo $OUTPUT->heading(format_string($certs[$certid]->fullname));
    echo html_writer::tag('p', get_string('overallreadiness', 'local_certmaster', (int) $data['overall_readiness']));
    if (!empty($data['dangerous_misconceptions'])) {
        echo $OUTPUT->heading(get_string('dangerousmisconceptions', 'local_certmaster'), 4);
        echo html_writer::start_tag('ul');
        foreach (array_slice($data['dangerous_misconceptions'], 0, 5) as $item) {
            $label = is_array($item) ? ($item['objective'] ?? $item['label'] ?? '') : (string) $item;
            echo html_writer::tag('li', format_string($label));
        }
        echo html_writer::end_tag('ul');
    }
}

echo $OUTPUT->heading(get_string('certtracks', 'local_certmaster'), 3);
echo html_writer::start_tag('div', ['class' => 'd-flex flex-wrap gap-3']);
foreach ($cards as $card) {
    $url = new moodle_url('/local/certmaster/index.php', ['certid' => $card['id']]);
    echo html_writer::tag('div',
        html_writer::link($url, $card['title'] . ' (' . $card['examcode'] . ') — ' . $card['readiness'] . '%'),
        ['class' => 'card p-3']
    );
}
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
