<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Portfolio block (stub — full implementation pending Phase 3).
 */
class block_portfolio extends block_base {

    #[\Override]
    public function init(): void {
        $this->title = get_string('pluginname', 'block_portfolio');
    }

    #[\Override]
    public function applicable_formats(): array {
        return ['my' => true, 'course-view' => true];
    }

    #[\Override]
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = get_string('notimplemented', 'block_portfolio');
        return $this->content;
    }
}
