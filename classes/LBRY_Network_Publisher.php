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
    // NOTE: This is currently sitting at about 150ms, mostly the post parsing
    public function publish($post, $channel)
    {

        // TODO: Handle unnatributed channel
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
        $endtime = microtime(true);

        try {
            // If everything went well with the conversion, carry on
            if ($write_status) {
                $name = $post->post_name;
                $bid = number_format(floatval(get_option(LBRY_SETTINGS)[LBRY_LBC_PUBLISH]), 2, '.', '');
                $title = $post->post_title;
                $language = substr(get_locale(), 0, 2);
                $license = get_option(LBRY_SETTINGS)[LBRY_LICENSE];

                // Setup featured image
                $featured_id = get_post_thumbnail_id($post);
                $featured_image = wp_get_attachment_image_src($featured_id, 'medium');
                $thumbnail = $featured_image[0] ? $featured_image[0] : false;

                // Build description using Yoast if installed and its used, excerpt/title otherwise
                $description = false;
                if (class_exists('WPSEO_META')) {
                    $description = WPSEO_META::get_value('opengraph-description', $post->ID);
                }
                if (!$description) {
                    $excerpt = get_the_excerpt($post);
                    $description = $excerpt ? $excerpt : $title;
                }
                $description .= ' | Originally published at ' . get_permalink($post);

                //TODO: Switch this to an array of args. This is getting out of hand.
                LBRY()->daemon->publish($name, $bid, $filepath, $title, $description, $language, $license, $channel, $thumbnail);
            }
        } catch (Exception $e) {
            error_log('Issue publishing post ' . $post->ID . ' to LBRY: ' .  $e->getMessage());
        } finally {
            //Delete the temporary markdown file
            unlink($filepath);
        }
    }
}
