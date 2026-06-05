<?php
// This file is part of Moodle - http://moodle.org/

namespace local_certmaster;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for mastery algorithm constants.
 *
 * @covers \local_certmaster\api
 */
final class api_test extends \advanced_testcase {

    public function test_confident_correct_increases_mastery(): void {
        $score = api::apply_confidence_delta(50.0, api::CONFIDENCE_CONFIDENT, true);
        $this->assertEquals(58.0, $score);
    }

    public function test_certain_incorrect_penalizes_mastery(): void {
        $score = api::apply_confidence_delta(50.0, api::CONFIDENCE_CERTAIN, false);
        $this->assertEquals(35.0, $score);
    }

    public function test_mastery_clamped_at_bounds(): void {
        $this->assertEquals(0.0, api::apply_confidence_delta(1.0, api::CONFIDENCE_CERTAIN, false));
        $this->assertEquals(100.0, api::apply_confidence_delta(99.0, api::CONFIDENCE_CERTAIN, true));
    }
}
