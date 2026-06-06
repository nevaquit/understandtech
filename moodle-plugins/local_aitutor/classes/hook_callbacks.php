<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callbacks for AI tutor sidebar injection.
 */
class hook_callbacks {

    /**
     * Whether the AI tutor sidebar should render on the current page.
     *
     * @return array{courseid: int, cmid: int}|null Sidebar context or null when not applicable.
     */
    private static function get_sidebar_context(): ?array {
        global $PAGE;

        if (!isloggedin() || isguestuser()) {
            return null;
        }

        $context = $PAGE->context;
        if (!in_array($context->contextlevel, [CONTEXT_COURSE, CONTEXT_MODULE], true)) {
            return null;
        }

        $coursecontext = $context->contextlevel === CONTEXT_MODULE
            ? $context->get_course_context()
            : $context;

        if (!has_capability('local/aitutor:use', $coursecontext)) {
            return null;
        }

        return [
            'courseid' => (int) $coursecontext->instanceid,
            'cmid' => $context->contextlevel === CONTEXT_MODULE ? (int) $context->instanceid : 0,
        ];
    }

    /**
     * Register CSS/AMD while the page head is still being built.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     * @return void
     */
    public static function before_standard_head(\core\hook\output\before_standard_head_html_generation $hook): void {
        global $PAGE;

        $sidebar = self::get_sidebar_context();
        if ($sidebar === null) {
            return;
        }

        $PAGE->requires->css('/local/aitutor/styles.css');
        $PAGE->requires->js_call_amd(
            'local_aitutor/tutor_sidebar',
            'init',
            [$sidebar['courseid'], $sidebar['cmid']],
        );
    }

    /**
     * Inject sidebar markup before the page footer is rendered.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     * @return void
     */
    public static function before_footer(\core\hook\output\before_footer_html_generation $hook): void {
        global $OUTPUT;

        if (self::get_sidebar_context() === null) {
            return;
        }

        $html = $OUTPUT->render_from_template('local_aitutor/sidebar', [
            'title' => get_string('sidebar_title', 'local_aitutor'),
        ]);
        $hook->add_html($html);
    }
}
