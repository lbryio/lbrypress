<?php
/**
 * Main LBRYPress class
 *
 * @package LBRYPress
 */

class LBRYPress
{
    // TODO: Remove Singleton pattern, replace with dependency injection for all classes
    // private static $instance = null;
    //
    // public static function get_instance()
    // {
    //     // Create the object
    //     if (self::$instance === null) {
    //         self::$instance = new self;
    //     }
    //
    //     return self::$instance;
    // }
    protected $daemon;
    protected $admin;

    public function __construct(LBRY_Daemon $daemon = null, LBRY_Admin $admin = null)
    {
        $this->daemon = $daemon ?? new LBRY_Daemon();
        $this->admin = $admin ?? new LBRY_Admin();
        error_log('new LBRYPress constructed');
    }

    /**
     * Set up all hooks and actions necessary for the plugin to run
     */
    public function init()
    {
        // Initialize the admin interface
        $this->admin->settings_init();
    }

    /**
     * Run during plugin activation
     */
    public function activate()
    {
        $LBRY_Daemon = LBRY_Daemon::get_instance();

        // Add options to the options table we need
        if (! get_option(LBRY_WALLET)) {
            $wallet_address = $this->daemon->wallet_unused_address();
            add_option(LBRY_WALLET, $wallet_address);
        }

        error_log('Activated');
    }

    /**
     * Clean up on deactivation
     */
    public function deactivate()
    {
        error_log('Deactivated');
    }
}
