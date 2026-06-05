<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callbacks for AI tutor sidebar injection.
 */
class hook_callbacks {

    /**
     * @param \core\hook\output\before_footer_html_generation $hook
     * @return void
     */
    public static function before_footer(\core\hook\output\before_footer_html_generation $hook): void {
        global $PAGE, $OUTPUT;

        if (!isloggedin() || isguestuser()) {
            return;
        }

        $context = $PAGE->context;
        if (!in_array($context->contextlevel, [CONTEXT_COURSE, CONTEXT_MODULE], true)) {
            return;
        }

        $coursecontext = $context->contextlevel === CONTEXT_MODULE
            ? $context->get_course_context()
            : $context;

        if (!has_capability('local/aitutor:use', $coursecontext)) {
            return;
        }

        $courseid = $coursecontext->instanceid;
        $cmid = $context->contextlevel === CONTEXT_MODULE ? $context->instanceid : 0;

        $PAGE->requires->css('/local/aitutor/styles.css');
        $PAGE->requires->js_call_amd('local_aitutor/tutor_sidebar', 'init', [$courseid, $cmid]);

        $html = $OUTPUT->render_from_template('local_aitutor/sidebar', [
            'title' => get_string('sidebar_title', 'local_aitutor'),
        ]);
        $hook->add_html($html);
    }
}
