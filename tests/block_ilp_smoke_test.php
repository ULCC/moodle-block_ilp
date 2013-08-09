<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Class block_ilp_smoke_test
 *
 * Tests that the PHPUnit stuff is working
 */
class block_ilp_smoke_test extends  basic_testcase {
    function test_for_smoke() {
        $this->assertEquals(1, 1);
    }
}