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
     * Member-area navigation cards for the logged-in LMS home page.
     *
     * @param string $profileurl Logged-in user profile URL.
     * @return list<array{title: string, description: string, url: string, icon: string}>
     */
    protected function export_frontpage_member_nav(string $profileurl): array {
        return [
            [
                'title'       => get_string('community', 'local_community'),
                'description' => get_string('frontpage_nav_community_desc', 'theme_understandtech'),
                'url'         => (new moodle_url('/local/community/community.php'))->out(false),
                'icon'        => 'community',
            ],
            [
                'title'       => get_string('classroom', 'local_community'),
                'description' => get_string('frontpage_nav_classroom_desc', 'theme_understandtech'),
                'url'         => (new moodle_url('/local/community/classroom.php'))->out(false),
                'icon'        => 'classroom',
            ],
            [
                'title'       => get_string('calendar', 'local_community'),
                'description' => get_string('frontpage_nav_calendar_desc', 'theme_understandtech'),
                'url'         => (new moodle_url('/local/community/calendar.php'))->out(false),
                'icon'        => 'calendar',
            ],
            [
                'title'       => get_string('members', 'local_community'),
                'description' => get_string('frontpage_nav_members_desc', 'theme_understandtech'),
                'url'         => (new moodle_url('/local/community/members.php'))->out(false),
                'icon'        => 'members',
            ],
            [
                'title'       => get_string('frontpage_nav_leaderboard_title', 'theme_understandtech'),
                'description' => get_string('frontpage_nav_leaderboard_desc', 'theme_understandtech'),
                'url'         => (new moodle_url('/local/gamification/leaderboard.php'))->out(false),
                'icon'        => 'leaderboard',
            ],
            [
                'title'       => get_string('myhome', 'moodle'),
                'description' => get_string('frontpage_nav_dashboard_desc', 'theme_understandtech'),
                'url'         => (new moodle_url('/my/'))->out(false),
                'icon'        => 'dashboard',
            ],
            [
                'title'       => get_string('frontpage_nav_certmaster_title', 'theme_understandtech'),
                'description' => get_string('frontpage_nav_certmaster_desc', 'theme_understandtech'),
                'url'         => (new moodle_url('/local/certmaster/index.php'))->out(false),
                'icon'        => 'readiness',
            ],
            [
                'title'       => get_string('pluginname', 'local_aitutor'),
                'description' => get_string('frontpage_nav_aitutor_desc', 'theme_understandtech'),
                'url'         => (new moodle_url('/local/aitutor/index.php'))->out(false),
                'icon'        => 'tutor',
            ],
            [
                'title'       => get_string('frontpage_nav_profile_title', 'theme_understandtech'),
                'description' => get_string('frontpage_nav_profile_desc', 'theme_understandtech'),
                'url'         => $profileurl,
                'icon'        => 'profile',
            ],
        ];
    }

    /**
     * Export Mustache context for the LMS home (frontpage) marketing layout.
     *
     * @return array<string, mixed>
     */
    protected function export_frontpage_context(): array {
        global $CFG, $DB, $USER;

        $isloggedin = isloggedin() && !isguestuser();
        $sec701 = $DB->get_record('course', ['shortname' => 'SEC701'], 'id', IGNORE_MISSING);
        $hassec701 = !empty($sec701);
        $sec701url = $hassec701
            ? (new moodle_url('/course/view.php', ['id' => $sec701->id]))->out(false)
            : '';

        $aplus = $DB->get_record('course', ['shortname' => 'APLUS'], 'id', IGNORE_MISSING);
        $hasaplus = !empty($aplus);
        $aplusurl = $hasaplus
            ? (new moodle_url('/course/view.php', ['id' => $aplus->id]))->out(false)
            : '';

        $profileurl = $isloggedin
            ? (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false)
            : '';

        return [
            'wwwroot'          => $CFG->wwwroot,
            'sitename'         => format_string(get_site()->fullname),
            'isloggedin'       => $isloggedin,
            'hassec701'        => $hassec701,
            'hasaplus'         => $hasaplus,
            'username'         => $isloggedin ? fullname($USER) : '',
            'loginurl'         => (new moodle_url('/login/index.php'))->out(false),
            'dashboardurl'     => (new moodle_url('/my/'))->out(false),
            'profileurl'       => $profileurl,
            'sec701url'        => $sec701url,
            'aplusurl'         => $aplusurl,
            'coursesurl'       => (new moodle_url('/course/index.php'))->out(false),
            'communityurl'     => (new moodle_url('/local/community/community.php'))->out(false),
            'classroomurl'     => (new moodle_url('/local/community/classroom.php'))->out(false),
            'calendarurl'      => (new moodle_url('/local/community/calendar.php'))->out(false),
            'membersurl'       => (new moodle_url('/local/community/members.php'))->out(false),
            'certmasterurl'    => (new moodle_url('/local/certmaster/index.php'))->out(false),
            'aitutorurl'       => (new moodle_url('/local/aitutor/index.php'))->out(false),
            'leaderboardurl'   => (new moodle_url('/local/gamification/leaderboard.php'))->out(false),
            'membersheading'   => get_string('frontpage_members_heading', 'theme_understandtech'),
            'membersdesc'      => get_string('frontpage_members_desc', 'theme_understandtech'),
            'memberswelcome'   => $isloggedin
                ? get_string('frontpage_members_welcome', 'theme_understandtech', fullname($USER))
                : '',
            'membersherotitle' => get_string('frontpage_members_hero_title', 'theme_understandtech'),
            'membersherodesc'  => get_string('frontpage_members_hero_desc', 'theme_understandtech'),
            'membersguestheading' => get_string('frontpage_members_guest_heading', 'theme_understandtech'),
            'membersguestdesc'   => get_string('frontpage_members_guest_desc', 'theme_understandtech'),
            'membernav'        => $isloggedin ? $this->export_frontpage_member_nav($profileurl) : [],
        ];
    }

    /**
     * Render the frontpage hero section.
     *
     * @return string Rendered HTML.
     */
    public function render_frontpage_hero(): string {
        return $this->render_from_template(
            'theme_understandtech/frontpage_hero',
            $this->export_frontpage_context(),
        );
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
