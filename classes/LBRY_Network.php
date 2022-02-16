<?php
/**
 * Class for connecting with the LBRY Network
 *
 * @package LBRYPress
 */
defined('ABSPATH') || die(); // Exit if accessed directly

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
        add_action( 'add_meta_boxes', array( $this, 'lbry_meta_boxes' ) );

        // Save the post meta on 'save_post' hook
        add_action( 'wp_insert_post', array( $this, 'save_post_meta' ), 11, 2 );
        
       // Checkbox inside the WordPres meta box near "Publish" button
        add_action( 'post_submitbox_misc_actions', array( $this, 'publish_to_lbry_checkbox' ) );
    }

    /**
     * Adds the meta boxes to the post editing backend
     */
    public function lbry_meta_boxes( $post )
    {
        // IDEA: Support post types based on user selection
        add_meta_box(
            'lbry-network-publishing',      // Unique ID
            __('LBRY Network', 'lbrypress'),                // Title
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
    public function save_post_meta( $post_id, $post )
    {
        if ( $post->post_type != 'post' ) {
            return $post_id;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
        // Verify the nonce before proceeding.
        if ( ! isset( $_POST['_lbrynonce'] ) || ! wp_verify_nonce( $_POST['_lbrynonce'], 'lbry_publish_post_nonce' ) ) {
            //LBRY()->notice->set_notice('error', 'Security check failed' );
            return $post_id;
        }
        $post_type = get_post_type_object( $post->post_type );
        if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
            return $post_id;
        }
        if ( ( $_POST[LBRY_WILL_PUBLISH] ) && $_POST[LBRY_WILL_PUBLISH] != get_post_meta( $post_id, LBRY_WILL_PUBLISH, true ) ) {
            update_post_meta( $post_id, LBRY_WILL_PUBLISH, $_POST[LBRY_WILL_PUBLISH] );
        } elseif ( ! isset( $_POST[LBRY_WILL_PUBLISH] ) ) {
            update_post_meta( $post_id, LBRY_WILL_PUBLISH, 0 );
        }

        $channel = $_POST[LBRY_POST_PUB_CHANNEL];
         $cur_channel = ( get_post_meta( $post_id, LBRY_POST_PUB_CHANNEL, true ) ? get_post_meta( $post_id, LBRY_POST_PUB_CHANNEL, true ) : get_post_meta( $post_id, '_lbry_channel', true ) );
        $license = $_POST[LBRY_POST_PUB_LICENSE];
        $cur_license = get_post_meta( $post_id, LBRY_POST_PUB_LICENSE, true );
        $will_publish = $_POST[LBRY_WILL_PUBLISH];

        // Update meta acordingly
            
        if ( $channel !== $cur_channel ) {
            update_post_meta( $post_id, LBRY_POST_PUB_CHANNEL, $channel );
            delete_post_meta( $post_id, '_lbry_channel'); // remove the _lbry_channel if already set from the post and replaces with _lbry_post_pub_channel to avoid confusion
        } elseif ( $channel === $cur_channel && ( $cur_channel === get_post_meta( $post_id, '_lbry_channel', true ) ) ) {
            update_post_meta( $post_id, LBRY_POST_PUB_CHANNEL, $channel );
            delete_post_meta( $post_id, '_lbry_channel'); // remove the _lbry_channel if already set from the post and replaces with _lbry_post_pub_channel to avoid confusion
        }
        if ( $license !== $cur_license ) {
            update_post_meta( $post_id, LBRY_POST_PUB_LICENSE, $license );
        }
        if ( ( $will_publish ) && ( $will_publish == 1 ) && $post->post_status == 'publish') {
            // Publish the post on the LBRY Network
            $this->publisher->publish( $post, $channel, $license );
        }
    }

    /**
     * Creates a checkbox that changes the default setting to always publish to LBRY, 
     * can be reverted individually to not publish on a per post basis. Saves to options table.
     */

    public function publish_to_lbry_checkbox( $post ) 
    {
        if ( get_post_type( $post_id ) != 'post' ) {
            return $post;
        }
        $post_id = $post->ID;
        $lbry_claim_id = get_post_meta( $post_id, '_lbry_claim_id', true );
        if ( $_GET['action'] === 'edit' ) {
            if ( get_post_meta( $post_id, '_lbry_canonical_url', true ) == null || empty( get_post_meta( $post_id, '_lbry_canonical_url', true ) ) ) {
            $canonical_url = LBRY()->daemon->canonical_url( $lbry_claim_id );
            update_post_meta( $post_id, '_lbry_canonical_url', $canonical_url );
            }
        }
        if ( ( get_post_meta( $post_id, '_lbry_will_publish', true ) == true ) && (isset( $lbry_claim_id ) ) ) {
            update_post_meta( $post_id, '_lbry_is_published', true );
        }
        $lbry_published = get_post_meta( $post_id, '_lbry_is_published', true );
        $lbry_url = ( get_post_meta( $post_id, '_lbry_canonical_url', true ) );

        if ($lbry_url) {
            $open_url = str_replace('lbry://', 'open.lbry.com/', $lbry_url );
        }
        
        $default_value = get_option( LBRY_SETTINGS )['lbry_default_publish_setting']; 
        $new_value = get_post_meta( $post_id, LBRY_WILL_PUBLISH, true );
        if ( ( $new_value ) ? $new_value : $new_value = $default_value );
        $value = $new_value;
        if ( ( $value ) ? $value : 0 );

        // nonce set on page meta-box.php
        if ( ( $lbry_published ) && ( ( $lbry_url) || ( $lbry_claim_id ) ) ) {
            printf(
                '<hr class="lbry-hr-meta">
                <div class="misc-pub-section lbry-meta-published-lbry-wrapper">
                    <span class="lbry-pub-metabox"><img src="' . __( '%1$s', 'lbrypress' ) . '" class="icon icon-lbry meta-icon-lbry"></span> <span class="post-lbry-display-before">Published on:</span> <span class="post-lbry-display"><strong> LBRY</strong></span>
                </div>
                <div class="misc-pub-section lbry-url-meta-wrapper">
                    <span class="lbry-meta-label"><strong> LBRY URL: </strong></span><a href="' . esc_url( '%2$s', 'lbrypress' ) . '" target="_blank">' . esc_html__( '%3$s', 'lbrypress' ) . '</a>
                </div>
                <div class="misc-pub-section lbry-claim-id-meta-wrapper">
                    <span class="lbry-meta-label"><strong> LBRY claim ID: </strong></span><span class="lbry-pub-metabox"><p class="lbry-claim-id-metabox">' . esc_html__( '%4$s', 'lbrypress' ) . '</p></span>
                </div>
                <div class="misc-pub-section lbry-meta-checkbox-wrapper lbry-meta-wrapper-last">
                    <span class="lbry-meta-label"> Update Post on: <span><strong>' . esc_html__('LBRY', 'lbrypress') . '</strong></span> <input type="checkbox" class="lbry-meta-checkbox" value="1"' . esc_attr('%5$s') . ' name="' . esc_attr('%6$s') . '">
                </div>',
                plugin_dir_url( LBRY_PLUGIN_FILE ) . 'admin/images/lbry.png',
                $open_url,
                $lbry_url,
                $lbry_claim_id,
                checked( $value, true, false ),
                LBRY_WILL_PUBLISH
                );
        } else {
            printf (
            '<div class="misc-pub-section lbry-meta-checkbox-wrapper lbry-meta-wrapper-last">
                <span class="lbry-pub-metabox"><img src="' . __( '%1$s', 'lbrypress' ) . '" class="icon icon-lbry meta-icon-lbry"></span><span class="lbry-meta-label"> Publish to: <strong>' . esc_html__('LBRY', 'lbrypress') . '</strong></span><input type="checkbox" class="lbry-meta-checkbox" value="1"' . esc_attr('%2$s') . ' name="' . esc_attr('%3$s') . '">
            </div>',
            plugin_dir_url( LBRY_PLUGIN_FILE ) . 'admin/images/lbry.png',
            checked( $value, true, false ),
            LBRY_WILL_PUBLISH
            );
        }
    }

    /**
     * Returns the HTML for the LBRY Meta Box
     * @param WP_POST   $post
     */
    public function meta_box_html( $post )
    {
        require_once( LBRY_ABSPATH . 'templates/meta-box.php' );
    }
}
