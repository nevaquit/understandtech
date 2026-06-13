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

        if (get_config('local_aitutor', 'enablesidebar') === '0') {
            return null;
        }

        if (!isloggedin() || isguestuser()) {
            return null;
        }

        // Marketing site home — never inject sidebar (breaks authenticated /?redirect=0 health gate).
        if ($PAGE->pagelayout === 'frontpage') {
            return null;
        }

        $context = $PAGE->context;
        if (!in_array($context->contextlevel, [CONTEXT_COURSE, CONTEXT_MODULE], true)) {
            return null;
        }

        $coursecontext = $context->contextlevel === CONTEXT_MODULE
            ? $context->get_course_context()
            : $context;

        // Site course is not a certification context; skip sidebar on SITEID.
        if ((int) $coursecontext->instanceid === SITEID) {
            return null;
        }

        if (!has_capability('local/aitutor:use', $coursecontext)) {
            return null;
        }

        return [
            'courseid' => (int) $coursecontext->instanceid,
            'cmid' => $context->contextlevel === CONTEXT_MODULE ? (int) $context->instanceid : 0,
        ];
    }

    /**
     * Register sidebar stylesheet while the page head is still being built.
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

        $PAGE->add_body_class('ut-has-aitutor-sidebar');
        $PAGE->requires->css('/local/aitutor/styles.css');
        // Footer hooks cannot queue PAGE->requires; register AMD here (before the lock).
        // js_call_amd emits M.util.js_pending before core/first defines M — use js_amd_inline.
        // initFromDom reads data-courseid from footer-injected markup.
        $PAGE->requires->js_amd_inline(
            "require(['local_aitutor/tutor_sidebar'], function(Tutor) { Tutor.initFromDom(); });",
        );
    }

    /**
     * Inject sidebar before the end-of-body script placeholder in the theme footer.
     *
     * @param \core\hook\output\before_standard_footer_html_generation $hook
     * @return void
     */
    public static function before_standard_footer(
        \core\hook\output\before_standard_footer_html_generation $hook,
    ): void {
        global $OUTPUT;

        $sidebar = self::get_sidebar_context();
        if ($sidebar === null) {
            return;
        }

        $html = $OUTPUT->render_from_template('local_aitutor/sidebar', [
            'title' => get_string('sidebar_title', 'local_aitutor'),
            'courseid' => $sidebar['courseid'],
            'cmid' => $sidebar['cmid'],
        ]);
        $hook->add_html($html);
    }
}
