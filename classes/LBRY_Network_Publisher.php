<?php
/**
 * Class for publishing to the LBRY Network
 *
 * @package LBRYPress
 */

class LBRY_Network_Publisher
{
    /**
     * [__construct description]
     */
    public function __construct()
    {
    }

    /**
     * Publish the post to the LBRY Network
     * @param  int $post_id  The ID of the post we are publishing
     * @param  string $channel The Claim ID of the channel we are posting to
     */
    public function publish($post, $channel)
    {
        // Leave if nothing to publish to
        if (!$channel) {
            return;
        }

        // Get converted markdown into a file
        $filepath = LBRY_ABSPATH . 'tmp/' . $post->post_name . time() . '.md';
        $file = fopen($filepath, 'w');
        $converted = LBRY()->network->parser->convert_to_markdown($post);
        $write_status = $file && fwrite($file, $converted);
        fclose($file);

        try {
            // If everything went well with the conversion, carry on
            if ($write_status) {
                $featured_id = get_post_thumbnail_id($post);
                $featured_image = wp_get_attachment_image_src($featured_id, 'medium');
                $name = $post->post_name;
                $bid = floatval(get_option(LBRY_SETTINGS)[LBRY_LBC_PUBLISH]);
                $title = $post->post_title;
                $language = substr(get_locale(), 0, 2);
                $license = get_option(LBRY_SETTINGS)[LBRY_LICENSE];
                // TODO: See if we can grab from yoast or a default?
                $description = $post->post_title;
                $thumbnail = $featured_image[0] ? $featured_image[0] : false;

                LBRY()->daemon->publish($name, $bid, $filepath, $title, $description, $language, $channel, $thumbnail);
            }
        } catch (Exception $e) {
            error_log('Issue publishing post ' . $post->ID . ' to LBRY: ' .  $e->getMessage());
        } finally {
            // Delete the temporary markdown file
            // unlink($filepath);
        }
    }
}
