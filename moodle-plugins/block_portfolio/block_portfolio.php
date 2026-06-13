<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Learner portfolio block.
 */
class block_portfolio extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_portfolio');
    }

    #[\Override]
    public function applicable_formats(): array {
        return ['my' => true, 'course-view' => true];
    }

    #[\Override]
    public function get_content() {
        global $USER, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $certid = (int) ($this->config->certificationid ?? 0);

        if (!$certid) {
            $this->content->text = get_string('noconfig', 'block_portfolio');
            return $this->content;
        }

        $data = \block_portfolio\api::get_portfolio($USER->id, $certid);

        $this->content->text = $OUTPUT->render_from_template('block_portfolio/main', [
            'readiness' => $data['readiness'],
            'labs' => $data['labs'],
            'assessments' => $data['assessments'],
            'empty' => $data['labs'] === [] && $data['assessments'] === [] && $data['readiness'] === 0,
        ]);

        return $this->content;
    }

    #[\Override]
    public function specialization(): void {
        $this->title = format_string($this->config->title ?? get_string('pluginname', 'block_portfolio'));
    }
}
