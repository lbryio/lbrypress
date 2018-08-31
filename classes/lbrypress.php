<?php
/**
 * Main LBRYPress class
 *
 * @package LBRYPress
 */

class LBRYPress
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
     * Set up all hooks and actions necessary for the plugin to run
     * @return [type] [description]
     */
    public function init()
    {
        // Initialize the admin interface
        $LBRY_Admin = LBRY_Admin::get_instance();
        $LBRY_Admin->settings_init();

        $LBRY_Daemon = LBRY_Daemon::get_instance();
    }

    /**
     * Run during plugin activation
     * @return [type] [description]
     */
    public function activate()
    {
        $LBRY_Daemon = LBRY_Daemon::get_instance();

        // Add options to the options table we need
        if (! get_option(LBRY_WALLET)) {
            $wallet_address = $LBRY_Daemon->wallet_unused_address();
            add_option(LBRY_WALLET, $wallet_address);
        }


        error_log('Activated');
    }

    /**
     * Clean up on deactivation
     * @return [type] [description]
     */
    public function deactivate()
    {
        error_log('Deactivated');
    }
}
