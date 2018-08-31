<?php
/**
* Class for intializing and displaying all admin settings
*
* @package LBRYPress
*/

class LBRY_Admin
{
    private static $instance = null;

    public static function get_instance()
    {
        // Create the object
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Called to initialize the settings interface
     * @return [type] [description]
     */
    public function settings_init()
    {
        add_action('admin_menu', array($this, 'create_options_page'));
    }

    /**
     * Creates the options page in the WP admin interface
     * @return [type] [description]
     */
    public function create_options_page()
    {
        add_options_page(
            __('LBRYPress Settings', 'lbrypress'),
            __('LBRYPress', 'lbrypress'),
            'manage_options',
            'LBRYPress',
            array($this, 'options_page_html')
        );
    }

    /**
     * Returns the Options Page HTML for the plugin
     * @return [type] [description]
     */
    public function options_page_html()
    {
        require_once(LBRY_URI . '/templates/options_page.php');
    }
}
