<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Exam readiness dashboard block.
 */
class block_examreadiness extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_examreadiness');
    }

    #[\Override]
    public function applicable_formats(): array {
        return ['my' => true, 'course-view' => true];
    }

    #[\Override]
    public function get_content() {
        global $USER, $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $certid = (int) ($this->config->certificationid ?? 0);

        if (!$certid) {
            $this->content->text = get_string('noconfig', 'block_examreadiness');
            return $this->content;
        }

        $data = \local_certmaster\api::get_user_readiness($USER->id, $certid);
        $misconceptions = array_slice($data['dangerous_misconceptions'], 0, 3);

        if (!empty($data['radar'])) {
            $PAGE->requires->js_call_amd('local_certmaster/radar_chart', 'init', ['.block-examreadiness-radar']);
        }

        $this->content->text = $OUTPUT->render_from_template('block_examreadiness/main', [
            'readiness' => $data['overall_readiness'],
            'radar' => json_encode($data['radar']),
            'misconceptions' => $misconceptions,
            'empty' => empty($data['radar']),
        ]);

        return $this->content;
    }

    #[\Override]
    public function specialization(): void {
        $this->title = format_string($this->config->title ?? get_string('pluginname', 'block_examreadiness'));
    }
}
