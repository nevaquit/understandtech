<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_ctfflag\tests;

use mod_ctfflag\local\flag_validator;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for flag validation.
 *
 * @covers \mod_ctfflag\local\flag_validator
 */
final class flag_validator_test extends \advanced_testcase {

    /**
     * @return void
     */
    public function test_matches_valid_flag(): void {
        $this->assertTrue(flag_validator::matches('UT{lab01}', 'UT\\{[A-Za-z0-9_\\-]+\\}'));
    }

    /**
     * @return void
     */
    public function test_rejects_invalid_flag(): void {
        $this->assertFalse(flag_validator::matches('wrong-flag', 'UT\\{[A-Za-z0-9_\\-]+\\}'));
    }

    /**
     * @return void
     */
    public function test_rejects_overlong_submission(): void {
        $this->assertFalse(flag_validator::matches(str_repeat('A', 300), 'UT\\{.*\\}'));
    }

    /**
     * @return void
     */
    public function test_rejects_overlong_pattern(): void {
        $this->assertFalse(flag_validator::matches('UT{test}', str_repeat('A', 200)));
    }
}
