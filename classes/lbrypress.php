<?php
/**
 * Main LBRYPress class
 *
 * @package LBRYPress
 */

class LBRYPress
{

    /**
     * Version of LBRYPress
     */
    public $version = '0.0.1';

    /**
     * The single instance of this class
     */
    protected static $_instance = null;

    /**
     * The LBRY Daemon Interface
     */
    public $daemon = null;

    /**
     * The LBRY Admin object
     */
    public $admin = null;

    /**
     * The LBRY Spee.ch object
     */
    public $speech = null;

    /**
     * Main LBRYPress Instance.
     *
     * Ensures only one instance of LBRYPress is loaded or can be loaded.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * LBRYPress Constructor
     */
    public function __construct()
    {
        $this->define_constants();
        spl_autoload_register(array($this, 'lbry_autoload_register'));
        $this->init();
        $this->init_hooks();
    }

    /**
     * Define a constant if its not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function define($name, $value)
    {
        if (! defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Define all LBRYPress constants
     */
    private function define_constants()
    {
        $upload_dir = wp_upload_dir(null, false);

        $this->define('LBRY_ABSPATH', dirname(LBRY_PLUGIN_FILE) . '/');
        $this->define('LBRY_NAME', plugin_basename(LBRY_PLUGIN_FILE));
        $this->define('LBRY_VERSION', $this->version);

        // Library Options Names
        $this->define('LBRY_WALLET', 'lbry_wallet'); // the wallet address
        $this->define('LBRY_SPEECH', 'lbry_speech'); // the spee.ch address
        $this->define('LBRY_LICENSE', 'lbry_license'); // the license to publish with to the LBRY network
        $this->define('LBRY_LBC_PUBLISH', 'lbry_lbc_publish'); // amount of lbc to use per publish
    }

    /**
     * Autoloader Registration
     */
    private function lbry_autoload_register($class)
    {
        $file_name = LBRY_ABSPATH . 'classes/' . $class . '.php';

        if (file_exists($file_name)) {
            require $file_name;
            return;
        }
    }

    /**
     * Initialize this class itself
     */
    private function init()
    {
        $this->daemon = new LBRY_Daemon();
        $this->admin = new LBRY_Admin();
        $this->speech = new LBRY_Speech();
    }

    /**
     * Set up all hooks and actions necessary for the plugin to run
     */
    private function init_hooks()
    {
        register_activation_hook(LBRY_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(LBRY_PLUGIN_FILE, array($this, 'deactivate'));

        // Admin request
        if (is_admin()) {
            $this->admin->settings_init();
        }
    }

    /**
     * Run during plugin activation
     */
    public function activate()
    {
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
