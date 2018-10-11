<?php
/**
 * Class LBRYPressTest
 *
 * @package Lbrypress
 */

/**
 * Test case for primary plugin class
 */
class LBRYPressTest extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->class_instance = LBRYPress::instance();
    }

    public function test_init()
    {
        // Init is called during constructor
        $this->assertInstanceOf(LBRY_Daemon::class, $this->class_instance->daemon);
        $this->assertInstanceOf(LBRY_Speech::class, $this->class_instance->speech);
        $this->assertInstanceOf(LBRY_Admin_Notice::class, $this->class_instance->notice);
    }

    /**
     * @depends test_init
     * Test activation hook
     */
    public function test_activate()
    {
        // Make sure we have options when activated
        $this->class_instance->activate();
        $this->assertTrue(!empty(get_option(LBRY_SETTINGS)));
    }
}
