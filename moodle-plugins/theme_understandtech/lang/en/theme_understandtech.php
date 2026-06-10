<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * UnderstandTech theme language strings (English).
 *
 * @package   theme_understandtech
 * @copyright 2026 UnderstandTech
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// ── Plugin metadata ───────────────────────────────────────────────────────────
$string['pluginname']          = 'UnderstandTech';
$string['choosereadme']        = 'UnderstandTech is a world-class Moodle theme built for AI-powered, mastery-based learning. It features a navy/gold/teal design system, adaptive AI tutor integration, blockchain-verified certifications, and gamified progress tracking.';
$string['configtitle']         = 'UnderstandTech Theme Settings';
$string['settings_tab_brand']    = 'Brand colours';
$string['settings_tab_layout']   = 'Layout & logo';
$string['settings_tab_advanced'] = 'Advanced';

// ── Accessibility ─────────────────────────────────────────────────────────────
$string['skiptomain']          = 'Skip to main content';

// ── Login ─────────────────────────────────────────────────────────────────────
$string['loginwelcomemessage'] = 'Sign in to continue your learning journey.';

// ── Brand colour settings ─────────────────────────────────────────────────────
$string['brand_navy']          = 'Brand Navy';
$string['brand_navy_desc']     = 'Primary background and navigation colour. Default: #0B1F3A.';
$string['brand_gold']          = 'Brand Gold';
$string['brand_gold_desc']     = 'Accent, CTA, and highlight colour. Default: #C9A227.';
$string['brand_teal']          = 'Brand Teal';
$string['brand_teal_desc']     = 'Secondary accent and interactive element colour. Default: #1A8A7D.';

// ── Logo & layout settings ────────────────────────────────────────────────────
$string['custom_logo']         = 'Custom Logo';
$string['custom_logo_desc']    = 'Upload a custom logo (PNG, JPG, or SVG). Recommended size: 200×50px. Leave blank to use the default SVG logo.';
$string['enable_skool_layout'] = 'Enable Skool-style course layout';
$string['enable_skool_layout_desc'] = 'When enabled, course pages use the two-column lesson grid with a sidebar navigation panel, similar to Skool.com.';

// ── Advanced settings ─────────────────────────────────────────────────────────
$string['footnote']            = 'Footer footnote';
$string['footnote_desc']       = 'Optional text or HTML to display in the site footer. Leave blank to hide.';
$string['rawscss']             = 'Raw SCSS';
$string['rawscss_desc']        = 'Additional SCSS rules appended after the theme preset. Use this for quick overrides without editing theme files.';
$string['rawscsspre']          = 'Raw initial SCSS';
$string['rawscsspre_desc']     = 'SCSS injected before the theme preset. Use to override Boost/Bootstrap variables.';

// ── Privacy ───────────────────────────────────────────────────────────────────
$string['privacy:metadata']    = 'The UnderstandTech theme does not store any personal data.';

// ── Front page (members hub) ──────────────────────────────────────────────────
$string['frontpage_members_heading'] = 'Members area';
$string['frontpage_members_desc'] = 'Your home base after sign-in — community, classroom, calendar, and certification tools in one place.';
$string['frontpage_members_welcome'] = 'Welcome back, {$a}';
$string['frontpage_members_hero_title'] = 'Your certification command center';
$string['frontpage_members_hero_desc'] = 'Use the members area below to pick up lessons, join discussions, check the calendar, or review exam readiness.';
$string['frontpage_nav_community_desc'] = 'Community feed, forum highlights, and peer discussions.';
$string['frontpage_nav_classroom_desc'] = 'Certification tracks, enrolled courses, and lesson progress.';
$string['frontpage_nav_calendar_desc'] = 'Live cohort sessions, office hours, and scheduled events.';
$string['frontpage_nav_members_desc'] = 'Member directory, profiles, levels, and cohort peers.';
$string['frontpage_nav_leaderboard_title'] = 'Leaderboard';
$string['frontpage_nav_leaderboard_desc'] = 'XP rankings, streaks, badges, and weekly standings.';
$string['frontpage_nav_dashboard_desc'] = 'Your Moodle dashboard, timeline, and enrolled courses.';
$string['frontpage_nav_certmaster_title'] = 'Exam readiness';
$string['frontpage_nav_certmaster_desc'] = 'Domain readiness, confidence mastery, and study plans.';
$string['frontpage_nav_aitutor_desc'] = 'AI tutor sessions grounded in your course progress.';
$string['frontpage_nav_profile_title'] = 'My profile';
$string['frontpage_nav_profile_desc'] = 'Your public profile, achievements, and account settings.';
$string['frontpage_members_guest_heading'] = 'Members area (sign in required)';
$string['frontpage_members_guest_desc'] = 'After you sign in, Home becomes your hub for Community, Classroom, Calendar, Members, and more.';
