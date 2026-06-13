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
        global $CFG, $PAGE;

        require_once($CFG->dirroot . '/course/lib.php');

        require_once($CFG->libdir . '/enrollib.php');

        $course = get_course($courseid);
        if (!can_access_course($course)) {
            return '';
        }

        try {
            $modinfo = get_fast_modinfo($course);
        } catch (\Throwable $e) {
            return '';
        }
        $currentcmid = ($PAGE->cm && (int) $PAGE->course->id === $courseid) ? (int) $PAGE->cm->id : 0;

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
                $isactive = $currentcmid > 0 && (int) $cm->id === $currentcmid;
                $itemclasses = 'courseindex-item' . ($isactive ? ' active' : '');
                $linkclasses = 'courseindex-link' . ($isactive ? ' active' : '');
                $arialabel = $isactive ? ' aria-current="page"' : '';
                $itemshtml .= '<li class="' . $itemclasses . '" data-for="cm" data-id="' . (int) $cm->id . '">'
                    . '<a class="' . $linkclasses . '" href="' . s($url) . '"' . $arialabel . '>'
                    . s($name) . '</a></li>';
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

        return '<div class="courseindex" role="navigation" aria-label="'
            . s(get_string('courseindex', 'core')) . '">' . $sectionshtml . '</div>';
    }

    /**
     * Replace skeleton placeholders in drawer HTML with server-rendered sections.
     *
     * @param string $drawerhtml HTML from core_course_drawer().
     * @param string $prerender Sections HTML from render_html().
     * @return string Updated drawer HTML.
     */
    public static function embed_in_drawer(string $drawerhtml, string $prerender): string {
        if ($prerender === '' || $drawerhtml === '') {
            return $drawerhtml;
        }

        if (strpos($drawerhtml, 'courseindex-section') !== false) {
            return $drawerhtml;
        }

        $placeholderpattern = '/<div[^>]*id="course-index-placeholder"[^>]*>.*?<\/div>\s*/s';
        if (preg_match($placeholderpattern, $drawerhtml)) {
            return (string) preg_replace($placeholderpattern, $prerender, $drawerhtml, 1);
        }

        $contentpattern = '/(<div[^>]*id="courseindex-content"[^>]*>)/';
        if (preg_match($contentpattern, $drawerhtml)) {
            return (string) preg_replace($contentpattern, '$1' . $prerender, $drawerhtml, 1);
        }

        return $drawerhtml . $prerender;
    }
}
