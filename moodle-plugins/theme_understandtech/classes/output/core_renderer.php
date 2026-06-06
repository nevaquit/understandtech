<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * UnderstandTech core renderer.
 *
 * Overrides theme_boost's renderer to inject the world-class navbar,
 * frontpage hero, and footer templates.
 *
 * @package   theme_understandtech
 * @copyright 2026 UnderstandTech
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_understandtech\output;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/**
 * Extends theme_boost's core_renderer to add UnderstandTech-specific rendering.
 */
class core_renderer extends \theme_boost\output\core_renderer {

    /**
     * Render the global navigation bar using the theme's navbar.mustache template.
     *
     * @return string Rendered HTML.
     */
    public function render_navbar(): string {
        global $CFG, $USER;

        $isloggedin = isloggedin() && !isguestuser();

        $context = [
            'wwwroot'      => $CFG->wwwroot,
            'sitename'     => format_string(get_site()->fullname),
            'isloggedin'   => $isloggedin,
            'loginurl'     => (new moodle_url('/login/index.php'))->out(false),
            'dashboardurl' => (new moodle_url('/my/'))->out(false),
            'profileurl'   => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false),
            'logouturl'    => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false),
            'username'     => $isloggedin ? fullname($USER) : '',
            'userpictureurl' => $isloggedin ? $this->get_user_picture_url($USER) : '',
            'hasnavdrawer' => false,
        ];

        return $this->render_from_template('theme_understandtech/navbar', $context);
    }

    /**
     * Render the frontpage hero section.
     *
     * @return string Rendered HTML.
     */
    public function render_frontpage_hero(): string {
        global $CFG;

        $isloggedin = isloggedin() && !isguestuser();

        $context = [
            'wwwroot'      => $CFG->wwwroot,
            'sitename'     => format_string(get_site()->fullname),
            'isloggedin'   => $isloggedin,
            'loginurl'     => (new moodle_url('/login/index.php'))->out(false),
            'dashboardurl' => (new moodle_url('/my/'))->out(false),
        ];

        return $this->render_from_template('theme_understandtech/frontpage_hero', $context);
    }

    /**
     * Render the global footer using the theme's footer.mustache template.
     *
     * @return string Rendered HTML.
     */
    public function render_footer(): string {
        global $CFG, $OUTPUT;

        $context = [
            'wwwroot'       => $CFG->wwwroot,
            'sitename'      => format_string(get_site()->fullname),
            'currentyear'   => date('Y'),
            'footnote'      => $this->page->theme->settings->footnote ?? '',
            'moodledocslink' => $this->moodle_docs_link(),
            'logininfo'     => $this->login_info(),
            'homelink'      => $this->home_link(),
        ];

        return $this->render_from_template('theme_understandtech/footer', $context);
    }

    /**
     * Override standard_head_html to inject Google Fonts preconnect/preload.
     * This fixes the render-blocking font loading deficiency from the audit.
     *
     * @return string HTML to inject into <head>.
     */
    public function standard_head_html(): string {
        $output = parent::standard_head_html();

        // Inject font preconnect + preload only if not already present.
        if (strpos($output, 'fonts.googleapis.com') === false) {
            $fonthtml  = '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
            $fonthtml .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
            $fonthtml .= '<link rel="preload" as="style" '
                . 'href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700'
                . '&amp;family=Source+Serif+4:ital,wght@0,400;0,600;1,400'
                . '&amp;family=Share+Tech+Mono&amp;display=swap" '
                . 'onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
            $fonthtml .= '<noscript><link rel="stylesheet" '
                . 'href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700'
                . '&amp;family=Source+Serif+4:ital,wght@0,400;0,600;1,400'
                . '&amp;family=Share+Tech+Mono&amp;display=swap"></noscript>' . "\n";

            // Insert before </head> or at the end of the head HTML.
            $output = str_replace('</head>', $fonthtml . '</head>', $output);
            if (strpos($output, $fonthtml) === false) {
                $output = $fonthtml . $output;
            }
        }

        return $output;
    }

    /**
     * Get the user's profile picture URL as a string.
     *
     * @param \stdClass $user The user object.
     * @return string URL string or empty string.
     */
    protected function get_user_picture_url(\stdClass $user): string {
        $userpicture = new \user_picture($user);
        $userpicture->size = 64;
        return $userpicture->get_url($this->page)->out(false);
    }
}
