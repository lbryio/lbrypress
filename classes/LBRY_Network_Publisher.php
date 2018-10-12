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
        $name = $post->post_name;
        
        return;
    }
}
