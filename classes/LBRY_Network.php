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
        add_action('save_post', array($this, 'save_post_meta'));
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
            'high'                       // Priority
        );
    }

    /**
     * Handles saving the post meta that is relative to publishing to the LBRY Network
     */
    public function save_post_meta()
    {
    }

    public function meta_box_html()
    {
        require_once(LBRY_ABSPATH . 'templates/meta_box.php');
    }
}
