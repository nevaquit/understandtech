<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_ctfflag\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Validates submitted CTF flags against teacher-configured regex patterns.
 */
class flag_validator {

    /**
     * Validate a flag value against the activity regex.
     *
     * @param string $submitted Raw learner submission.
     * @param string $pattern PCRE pattern (without delimiters).
     * @return bool True when the submission matches.
     */
    public static function matches(string $submitted, string $pattern): bool {
        $submitted = trim($submitted);
        if ($submitted === '' || $pattern === '') {
            return false;
        }

        $regex = self::wrap_pattern($pattern);
        $result = @preg_match($regex, $submitted);
        if ($result === false) {
            debugging('Invalid CTF flag regex configured for activity: ' . $pattern, DEBUG_DEVELOPER);
            return false;
        }

        return $result === 1;
    }

    /**
     * Wrap a stored pattern with delimiters for preg_match.
     *
     * @param string $pattern Teacher-provided pattern.
     * @return string Delimited PCRE pattern.
     */
    protected static function wrap_pattern(string $pattern): string {
        if ($pattern[0] === '/' || $pattern[0] === '#') {
            return $pattern;
        }
        return '/' . str_replace('/', '\\/', $pattern) . '/';
    }
}
