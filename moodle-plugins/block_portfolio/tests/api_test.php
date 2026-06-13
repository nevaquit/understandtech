<?php
// This file is part of Moodle - http://moodle.org/

namespace block_portfolio;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for portfolio aggregation.
 *
 * @covers \block_portfolio\api
 */
final class api_test extends \advanced_testcase {

    public function test_get_portfolio_returns_empty_structure_without_data(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $portfolio = api::get_portfolio((int) $user->id, 0);

        $this->assertSame(0, $portfolio['readiness']);
        $this->assertSame([], $portfolio['labs']);
        $this->assertSame([], $portfolio['assessments']);
    }

    public function test_get_portfolio_includes_readiness_when_certmaster_present(): void {
        $this->resetAfterTest();

        if (!class_exists('\local_certmaster\api')) {
            $this->markTestSkipped('local_certmaster is not installed.');
        }

        $user = $this->getDataGenerator()->create_user();
        global $DB;
        $cert = $DB->get_record('certmaster_certifications', [], 'id ASC', '*', IGNORE_MULTIPLE);
        if (!$cert) {
            $this->markTestSkipped('No certification frameworks are seeded.');
        }

        $portfolio = api::get_portfolio((int) $user->id, (int) $cert->id);

        $this->assertArrayHasKey('readiness', $portfolio);
        $this->assertGreaterThanOrEqual(0, $portfolio['readiness']);
        $this->assertLessThanOrEqual(100, $portfolio['readiness']);
    }
}
