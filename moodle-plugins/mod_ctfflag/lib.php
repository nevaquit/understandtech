<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Feature support for mod_ctfflag (stub — full implementation pending Phase 3).
 *
 * @param string $feature FEATURE_* constant
 * @return bool|null
 */
function ctfflag_supports(string $feature): ?bool {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return false;
        default:
            return null;
    }
}
