<?php
// This file is part of Moodle - http://moodle.org/

namespace theme_understandtech\output;

defined('MOODLE_INTERNAL') || die();

use context_course;
use moodle_page;
use moodle_url;

/**
 * Server-side course index prerender for drawer first paint (no JS required).
 *
 * @package   theme_understandtech
 * @copyright 2026 UnderstandTech
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_index_prerender {

    /**
     * Whether the current page should include a server-rendered course index.
     *
     * @param moodle_page $page Current page.
     * @return bool
     */
    public static function should_prerender(moodle_page $page): bool {
        global $SITE;

        if (!$page->course || (int) $page->course->id === (int) $SITE->id) {
            return false;
        }

        if (!in_array($page->pagelayout, ['course', 'incourse'], true)) {
            return false;
        }

        if (!isloggedin() || isguestuser()) {
            return false;
        }

        $format = course_get_format($page->course);
        return $format->uses_course_index();
    }

    /**
     * Build a minimal course index HTML tree from modinfo (works without AMD/YUI).
     *
     * @param int $courseid Course id.
     * @return string Rendered HTML or empty string on failure.
     */
    public static function render_html(int $courseid): string {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');

        require_once($CFG->libdir . '/enrollib.php');

        $course = get_course($courseid);
        if (!can_access_course($course)) {
            return '';
        }

        $modinfo = get_fast_modinfo($course);

        $sectionshtml = '';
        foreach ($modinfo->get_section_info_all() as $section) {
            if (!$section->uservisible || empty($modinfo->sections[$section->section])) {
                continue;
            }

            $title = get_section_name($course, $section);
            $itemshtml = '';

            foreach ($modinfo->sections[$section->section] as $cmid) {
                $cm = $modinfo->cms[$cmid] ?? null;
                if (!$cm || !$cm->uservisible || !$cm->url) {
                    continue;
                }

                $url = $cm->url instanceof moodle_url ? $cm->url->out(false) : '';
                $name = format_string($cm->name, true, ['context' => $cm->context]);
                $itemshtml .= '<li class="courseindex-item" data-for="cm" data-id="' . (int) $cm->id . '">'
                    . '<a class="courseindex-link" href="' . s($url) . '">' . s($name) . '</a></li>';
            }

            if ($itemshtml === '') {
                continue;
            }

            $sectionshtml .= '<div class="courseindex-section" data-for="section" data-id="' . (int) $section->id . '">'
                . '<div class="courseindex-section-title">' . s($title) . '</div>'
                . '<ul class="courseindex-section-items">' . $itemshtml . '</ul></div>';
        }

        if ($sectionshtml === '') {
            return '';
        }

        return $sectionshtml;
    }
}
