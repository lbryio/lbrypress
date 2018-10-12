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
     * @param  array $channels An array of channels we are publishing to
     */
    public function publish($post, $channels)
    {
        // Leave if nothing to publish to
        if (!$channels) {
            return;
        }

        // Get converted markdown into a file
        $filepath = LBRY_ABSPATH . 'tmp/' . $post->post_name . time() . '.md';
        $file = fopen($filepath, 'w');
        $converted = LBRY()->network->parser->convert_to_markdown($post);
        $write_status = $file && fwrite($file, $converted);
        fclose($file);

        // TODO: Catch relative exceptions if necessary
        try {
            // If everything went well with the conversion, carry on
            if ($write_status) {
                $featured_image = get_the_post_thumbnail($post);

                $name = $post->post_name;
                $bid = get_option(LBRY_SETTINGS)[LBRY_LBC_PUBLISH];
                $title = $post->post_title;
                $language = substr(get_locale(), 0, 2);
                $license = get_option(LBRY_SETTINGS)[LBRY_LICENSE];
                // TODO: See if we can grab from yoast or a default?
                $description = $post->post_title;
                // TODO: Bring thumbnails into the mix
                // $thumbnail = $featured_image ? $featured_image : null;

                foreach ($channels as $channel) {
                    LBRY()->daemon->publish($name, $bid, $filepath, $title, $description, $language, $channel);
                }
            }
        } finally {
            // Delete the temporary markdown file
            unlink($filepath);
        }
    }
}
