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
 * Extends theme_boost's renderer for custom logos, login branding, and font injection.
 *
 * @package   theme_understandtech
 * @copyright 2026 UnderstandTech
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_understandtech\output;

defined('MOODLE_INTERNAL') || die();

use context_course;
use core_auth\output\login;
use moodle_url;

/**
 * Extends theme_boost's core_renderer with UnderstandTech-specific rendering.
 */
class core_renderer extends \theme_boost\output\core_renderer {

    /**
     * Return the admin-uploaded theme logo URL, if configured.
     *
     * @return string|null Protocol-relative pluginfile URL or null when unset.
     */
    public function get_custom_logo_url(): ?string {
        return $this->page->theme->setting_file_url('custom_logo', 'custom_logo');
    }

    /**
     * Export logo context variables consumed by core/loginform.mustache.
     *
     * @return array{logourl: string|null, hascustomlogo: bool}
     */
    public function export_login_logo_context(): array {
        $logourl = $this->get_custom_logo_url();

        return [
            'logourl' => $logourl,
            'hascustomlogo' => !empty($logourl),
        ];
    }

    /**
     * Prefer the theme custom logo, then fall back to the site admin logo.
     *
     * @param int|null $maxwidth Maximum width.
     * @param int $maxheight Maximum height.
     * @return moodle_url|false
     */
    public function get_logo_url($maxwidth = null, $maxheight = 200) {
        $custom = $this->get_custom_logo_url();
        if (!empty($custom)) {
            return new moodle_url($custom);
        }

        return parent::get_logo_url($maxwidth, $maxheight);
    }

    /**
     * Prefer the theme custom logo for compact navbar branding.
     *
     * @param int $maxwidth Maximum width.
     * @param int $maxheight Maximum height.
     * @return moodle_url|false
     */
    public function get_compact_logo_url($maxwidth = 300, $maxheight = 300) {
        $custom = $this->get_custom_logo_url();
        if (!empty($custom)) {
            return new moodle_url($custom);
        }

        return parent::get_compact_logo_url($maxwidth, $maxheight);
    }

    /**
     * Render the login form with UnderstandTech logo context.
     *
     * @param login $form The renderable login form.
     * @return string Rendered HTML.
     */
    public function render_login(login $form): string {
        global $SITE;

        $context = $form->export_for_template($this);
        $context->errorformatted = $this->error_text($context->error);

        $logocontext = $this->export_login_logo_context();
        $context->logourl = $logocontext['logourl'];
        $context->hascustomlogo = $logocontext['hascustomlogo'];

        if (empty($context->logourl)) {
            $url = parent::get_logo_url();
            if ($url) {
                $context->logourl = $url->out(false);
            }
        }

        $context->sitename = format_string(
            $SITE->fullname,
            true,
            ['context' => context_course::instance(SITEID), 'escape' => false]
        );

        return $this->render_from_template('core/loginform', $context);
    }

    /**
     * Inject frontpage hero markup on the site home layout.
     *
     * @return string HTML.
     */
    public function standard_top_of_body_html(): string {
        $html = parent::standard_top_of_body_html();
        if ($this->page->pagelayout === 'frontpage') {
            $html .= $this->render_frontpage_hero();
        }
        return $html;
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
     * Override standard_head_html to inject Google Fonts preconnect/preload.
     *
     * @return string HTML to inject into <head>.
     */
    public function standard_head_html(): string {
        $output = parent::standard_head_html();

        if (strpos($output, 'fonts.googleapis.com') === false) {
            $fonturl = 'https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700'
                . '&family=Source+Serif+4:ital,wght@0,400;0,600;1,400'
                . '&family=Share+Tech+Mono&display=swap';
            $fonthtml  = '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
            $fonthtml .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
            $fonthtml .= '<link rel="stylesheet" href="' . $fonturl . '">' . "\n";

            $output = str_replace('</head>', $fonthtml . '</head>', $output);
            if (strpos($output, $fonthtml) === false) {
                $output = $fonthtml . $output;
            }
        }

        return $output;
    }
}
