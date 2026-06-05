<?php
// This file is part of Moodle - http://moodle.org/

namespace theme_understandtech\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Core renderer extensions for UnderstandTech branding.
 */
class core_renderer extends \theme_boost\output\core_renderer {

    #[\Override]
    public function navbar(): string {
        $html = parent::navbar();
        if (!empty($html)) {
            $html = str_replace('class="navbar', 'class="navbar ut-primary-nav', $html);
        }
        return $html;
    }

    #[\Override]
    public function body_attributes($additionalclasses = []) {
        $classes = is_array($additionalclasses) ? $additionalclasses : [];
        if (!empty(get_config('theme_understandtech', 'enable_skool_layout'))) {
            $classes[] = 'ut-skool-enabled';
        }
        return parent::body_attributes($classes);
    }
}
