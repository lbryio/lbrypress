<?php
/**
 * Class for connecting with the LBRY Network
 *
 * @package LBRYPress
 */

class LBRY_Network
{

    /**
     * The Publishing Object
     * @var LBRY_Network_Publisher
     */
    public $publisher = null;

    /**
     * The Parsing Object
     * @var LBRY_Network_Parser
     */
    public $parser = null;

    /**
     * [__construct description]
     */
    public function __construct()
    {
        $this->publisher = new LBRY_Network_Publisher();
        $this->parser = new LBRY_Network_Parser();

        $this->post_meta_setup();
    }

    /**
     * Sets up everything for the post meta boxes
     */
    private function post_meta_setup()
    {
        // Add the meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

        // Save the post meta on 'save_post' hook
        add_action('save_post', array($this, 'save_post_meta'), 10, 2);
    }

    /**
     * Adds the meta boxes to the post editing backend
     */
    public function add_meta_boxes()
    {
        add_meta_box(
            'lbry-network-publishing',      // Unique ID
            'LBRY Network',                 // Title
            array($this, 'meta_box_html'),  // Callback function
            'post',                         // Screen Options (or post type)
            'side',                         // Context
            'high'                          // Priority
        );
    }

    /**
     * Handles saving the post meta that is relative to publishing to the LBRY Network
     */
    public function save_post_meta($post_id, $post)
    {
        // Verify the nonce before proceeding.
        if (!isset($_POST['_lbrynonce']) || !wp_verify_nonce($_POST['_lbrynonce'], 'lbry_publish_channels')) {
            return $post_id;
        }

        // Check if the current user has permission to edit the post.
        $post_type = get_post_type_object($post->post_type);
        if (!current_user_can($post_type->cap->edit_post, $post_id)) {
            return $post_id;
        }

        $meta_key = 'lbry_channels';
        $new_channels = (isset($_POST[$meta_key]) ? $_POST[$meta_key] : null);
        $cur_channels = get_post_meta($post_id, $meta_key);

        // COMBAK: Make this a bit more efficent if they have lots of channels
        // Start with clean meta, then add new channels if there are any
        delete_post_meta($post_id, $meta_key);
        if ($new_channels) {
            foreach ($new_channels as $channel) {
                add_post_meta($post_id, $meta_key, $channel);
            }
        }

        // Publish the post on the LBRY Network
        $this->publisher->publish($post, $new_channels);
    }

    public function meta_box_html($post)
    {
        require_once(LBRY_ABSPATH . 'templates/meta_box.php');
    }
}
