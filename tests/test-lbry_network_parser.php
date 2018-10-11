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
        $content = file_get_contents(LBRY_ABSPATH . 'tests/convert_content.txt');
        $post = self::factory()->post->create_and_get(array(
            'post_title' => 'Markdown Test!',
            'post_content' => $content
        ));
        $attachment = $this->factory->attachment->create_and_get(array(
            'post_parent' => $post->ID,
            'file' => '/wp-content/uploads/2018/08/BBC-John-Bolton-610x343.jpg'
        ));
        add_post_meta($post->ID, '_thumbnail_id', $attachment->ID, true);

        $actual = fopen(LBRY_ABSPATH . 'tests/convert_actual.md', 'w');
        $converted = $this->class_instance->convert_to_markdown($post->ID);
        fwrite($actual, $converted);
        $this->assertFileEquals(LBRY_ABSPATH . 'tests/convert_expected.md', LBRY_ABSPATH . 'tests/convert_actual.md');
    }
}
