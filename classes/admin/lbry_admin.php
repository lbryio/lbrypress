<?php
/**
* Class for intializing and displaying all admin settings
*
* @package LBRYPress
*/

if (! class_exists('LBRY_Admin')) {
    class LBRY_Admin
    {
        public function settings_init()
        {
            add_action('admin_menu', array($this, 'create_options_page'));
        }

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

        public function options_page_html()
        {
            require_once(LBRY_URI . '/templates/options_page.php');
        }
    }
}
