<?php
/**
 * Main LBRYPress class
 *
 * @package LBRYPress
 */
defined('ABSPATH') || die(); // Exit if accessed directly

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
     * The Admin Notice object
     */
    public $notice = null;

    /**
     * The Library Network Object
     */
    public $network = null;

    /**
     * The Licenses Available
     */
    public $licenses = array(
        'Creative Commons Attribution 4.0 International' => 'Creative Commons Attribution 4.0 International',
        'Creative Commons Attribution-ShareAlike 4.0 International' => 'Creative Commons Attribution-ShareAlike 4.0 International',
        'Creative Commons Attribution-NoDerivatives 4.0 International' => 'Creative Commons Attribution-NoDerivatives 4.0 International',
        'Creative Commons Attribution-NonCommercial 4.0 International' => 'Creative Commons Attribution-NonCommercial 4.0 International',
        'Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International' => 'Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International',
        'Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International' => 'Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International',
        'Copyrighted' => 'Copyrighted',
        'Public Domain' => 'Public Domain'
    );

    /**
     * Main LBRYPress Instance.
     *
     * Ensures only one instance of LBRYPress is loaded or can be loaded.
     */
    public static function instance()
    {
        if ( is_null( self::$_instance ) ) {
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
        spl_autoload_register( array( $this, 'lbry_autoload_register' ) );
        $this->init();
        $this->init_hooks();
    }

    /**
     * Define a constant if its not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function define( $name, $value )
    {
        if ( ! defined( $name ) ) {
            define( $name, $value );
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
        //$this->define('LBRY_SETTINGS_GROUP', 'lbry_settings_group');
        $this->define('LBRY_SETTINGS', 'lbry_settings');
        $this->define('LBRY_SETTINGS_SECTION_GENERAL', 'lbry_settings_section_general');
        $this->define('LBRY_ADMIN_PAGE', 'lbrypress');
        $this->define('LBRY_WALLET', 'lbry_wallet'); // the wallet address
        $this->define('LBRY_SPEECH_SETTINGS', 'lbry_speech_settings');
        $this->define('LBRY_SPEECH', 'lbry_speech'); // the spee.ch address
        $this->define('LBRY_SPEECH_CHANNEL', 'lbry_speech_channel'); // The spee.ch channel
        $this->define('LBRY_SPEECH_PW', 'lbry_speech_pw'); // The password for the spee.ch channel
        $this->define('LBRY_LICENSE', 'lbry_license'); // the license to publish with to the LBRY network
        $this->define('LBRY_LBC_PUBLISH', 'lbry_lbc_publish'); // amount of lbc to use per publish
        $this->define('LBRY_WILL_PUBLISH', '_lbry_will_publish'); // The meta key for if to publish to LBRY Network or not
        $this->define('LBRY_POST_CHANNEL', '_lbry_channel'); // The meta key for which channel to publish
        $this->define('LBRY_POST_PUB_CHANNEL', '_lbry_post_pub_channel'); // The meta key for which channel to publish on
        $this->define('LBRY_POST_PUB_LICENSE', '_lbry_post_pub_license'); // The meta key for which license to publish on
        $this->define('LBRY_CLAIM_ID', '_lbry_claim_id'); // The Claim ID for the post as it was published on LBRY
        $this->define('LBRY_CANONICAL_URL', '_lbry_canonical_url'); // The canonical url for the published lbry post
        $this->define('LBRY_SPEECH_ASSET_URL', 'speech_asset_url'); // The meta key for an asset's speech url
    }

    /**
     * Autoloader Registration
     */
    private function lbry_autoload_register( $class )
    {
        $file_name = LBRY_ABSPATH . 'classes/' . $class . '.php';

        if ( file_exists( $file_name ) ) {
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
        $this->speech = new LBRY_Speech();

        // Admin request
        if ( is_admin() ) {
            $this->admin = new LBRY_Admin();
            $this->notice = new LBRY_Admin_Notice();
            $this->network = new LBRY_Network();
        }
    }

    /**
     * Set up all hooks and actions necessary for the plugin to run
     */
    private function init_hooks()
    {
        register_activation_hook( LBRY_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( LBRY_PLUGIN_FILE, array( $this, 'deactivate' ) );

        // Banner output for published posts
        // NOTE: move this to its own class to reduce clutter?
        add_action( 'the_content', array( $this, 'published_on_lbry_banner' ), 12, 1 );

        add_action( 'wp_enqueue_scripts', function () {
            wp_enqueue_style( 'lbry-css', plugins_url( '/frontend/lbry.css', LBRY_PLUGIN_FILE ) );
        });
    }

    /**
     * Run during plugin activation
     */
    public function activate()
    {
        // TODO: Make sure errors are thrown if daemon can't be contacted, stop activation

        // Add options to the options table we need
        if (! get_option( LBRY_SETTINGS ) ) {

            //Default options
            $option_defaults = array(
                LBRY_WALLET => '',
                'lbry_default_publish_setting' => '',
                'default_lbry_channel' => '',
                LBRY_LICENSE => '',
                LBRY_LBC_PUBLISH => 0.001,
            );

            add_option( LBRY_SETTINGS, $option_defaults, false );
        }

        if ( ! get_option( LBRY_SPEECH_SETTINGS ) ) {
            // Default Speech Settings
            $option_defaults = array(
                LBRY_SPEECH =>'',
                LBRY_SPEECH_CHANNEL => '',
                LBRY_SPEECH_PW => '',
            );
            add_option( LBRY_SPEECH_SETTINGS, $option_defaults, false );
        }

        // COMBAK: decide if we need to check for missing or corrupt settings. May be unecessary.
        // Double check we have all settings, if not, update with default
        // $current_settings = get_option(LBRY_SETTINGS);
        // $new_settings = $current_settings;
        // foreach ($option_defaults as $key => $value) {
        //     if (! array_key_exists($key, $current_settings)) {
        //         $new_settings[$key] = $value;
        //     }
        // }
        // update_option(LBRY_SETTINGS, $new_settings);
    }

    /**
     * Clean up on deactivation
     */
    public function deactivate()
    {
        // TODO: Stop the daemon
        error_log( 'Deactivated LBRYPress' );
    }

    public function published_on_lbry_banner($content)
    {
        if ( ! is_single() || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        global $post;
        if ( $post->post_type != 'post' ) {
            return $content;
        }

        if ( ! get_post_meta( $post->ID, LBRY_WILL_PUBLISH, true ) ) {
            return $content;
        }

        ob_start();
        require( LBRY_ABSPATH . 'templates/published_on_lbry_banner.php' );
        $banner = ob_get_clean();

        return $content .= $banner;
    }

    /*
     * Utility Functions
     */
    public static function channel_name_comp($a, $b)
    {
        if ($a->name === $b->name) {
            return 0;
        }

        if ($b->claim_id == 'null') {
            return -1;
        }
        return strnatcasecmp($a->name, $b->name);
    }
}
