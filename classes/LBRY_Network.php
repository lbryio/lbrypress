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
        add_action('wp_insert_post', array($this, 'save_post_meta'), 11, 2);
    }

    /**
     * Adds the meta boxes to the post editing backend
     */
    public function add_meta_boxes()
    {
        // IDEA: Support post types based on user selection
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
     * @param  int      $post_id    The ID of the post we are saving
     * @param  WP_Post  $post       The Post Object we are saving
     * @return int                  Returns post_id if user cannot edit post
     */
    public function save_post_meta($post_id, $post)
    {
        if ($post->post_type != 'post') {
            return;
        }

        // Verify the nonce before proceeding.
        if (!isset($_POST['_lbrynonce']) || !wp_verify_nonce($_POST['_lbrynonce'], 'lbry_publish_channels')) {
            return $post_id;
        }

        // Check if the current user has permission to edit the post.
        $post_type = get_post_type_object($post->post_type);
        if (!current_user_can($post_type->cap->edit_post, $post_id)) {
            return $post_id;
        }

        $will_publish = (isset($_POST[LBRY_WILL_PUBLISH]) ? $_POST[LBRY_WILL_PUBLISH] : false);
        $new_channel = (isset($_POST[LBRY_POST_CHANNEL]) ? $_POST[LBRY_POST_CHANNEL] : null);
        $cur_channel = get_post_meta($post_id, LBRY_POST_CHANNEL, true);

        // Update meta acordingly
        if (!$will_publish) {
            update_post_meta($post_id, LBRY_WILL_PUBLISH, 'false');
        } else {
            update_post_meta($post_id, LBRY_WILL_PUBLISH, 'true');
        }
        if ($new_channel !== $cur_channel) {
            update_post_meta($post_id, LBRY_POST_CHANNEL, $new_channel);
        }

        if ($will_publish) {
            // Publish the post on the LBRY Network
            $this->publisher->publish($post, get_post_meta($post_id, LBRY_POST_CHANNEL, true));
        }
    }

    /**
     * Returns the HTML for the LBRY Meta Box
     * @param WP_POST   $post
     */
    public function meta_box_html($post)
    {
        require_once(LBRY_ABSPATH . 'templates/meta_box.php');
    }
}
