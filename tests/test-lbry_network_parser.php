<?php
/**
 * Class LBRYPressTest
 *
 * @package Lbrypress
 */

/**
 * Test case for primary plugin class
 */
class LBRY_Network_Parser_Test extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->class_instance = new LBRY_Network_Parser();
    }

    public function test_convert_to_markdown()
    {
    }
}
