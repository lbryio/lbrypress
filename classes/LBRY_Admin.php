<?php
/**
* Class for intializing and displaying all admin settings
*
* @package LBRYPress
*/

class LBRY_Admin
{
    private $options;

    /**
     * LBRY_Admin Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'create_options_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    /**
     * Creates the options page in the WP admin interface
     */
    public function create_options_page()
    {
        add_options_page(
            __('LBRYPress Settings', 'lbrypress'),
            __('LBRYPress', 'lbrypress'),
            'manage_options',
            LBRY_ADMIN_PAGE,
            array($this, 'options_page_html')
        );
    }

    /**
     * Registers all settings for the plugin
     */
    public function page_init()
    {
        // Register the LBRY Setting array
        register_setting(LBRY_SETTINGS_GROUP, LBRY_SETTINGS, array($this, 'sanitize'));

        // Add Required Settings Sections
        add_settings_section(
           LBRY_SETTINGS_SECTION_GENERAL, // ID
           'General Settings', // Title
           array( $this, 'general_section_info' ), // Callback
           LBRY_ADMIN_PAGE // Page
        );

        // Add all settings fields
        add_settings_field(
            LBRY_WALLET,
            'LBRY Wallet Address',
            array( $this, 'wallet_callback' ),
            LBRY_ADMIN_PAGE,
            LBRY_SETTINGS_SECTION_GENERAL
        );

        add_settings_field(
            LBRY_SPEECH, // ID
            'Spee.ch URL', // Title
            array( $this, 'speech_callback' ), // Callback
            LBRY_ADMIN_PAGE, // Page
            LBRY_SETTINGS_SECTION_GENERAL // Section
        );

        add_settings_field(
            LBRY_LICENSE,
            'LBRY Publishing License',
            array( $this, 'license_callback' ),
            LBRY_ADMIN_PAGE,
            LBRY_SETTINGS_SECTION_GENERAL
        );

        add_settings_field(
            LBRY_LBC_PUBLISH,
            'LBC Per Publish',
            array( $this, 'lbc_publish_callback' ),
            LBRY_ADMIN_PAGE,
            LBRY_SETTINGS_SECTION_GENERAL
        );
    }

    /**
     * Returns the Options Page HTML for the plugin
     */
    public function options_page_html()
    {
        // Set class property to be referenced in callbacks
        $this->options = get_option(LBRY_SETTINGS);
        require_once(LBRY_ABSPATH . 'templates/options_page.php');
    }

    /**
     * Sanitizes setting input
     * // TODO Actually sanitize the input
     */
    public function sanitize($input)
    {
        return $input;
    }

    /**
     * Section info for the General Section
     */
    public function general_section_info()
    {
        print 'This is where you can configure how LBRYPress will distribute your content:';
    }

    /**
     * Prints Wallet input
     */
    public function wallet_callback()
    {
        printf(
            '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" readonly />',
            LBRY_WALLET,
            LBRY_SETTINGS,
            isset($this->options[LBRY_WALLET]) ? esc_attr($this->options[LBRY_WALLET]) : ''
        );
    }

    /**
     * Prints Spee.ch input
     */
    public function speech_callback()
    {
        printf(
           '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" placeholder="https://your-speech-address.com"/>',
           LBRY_SPEECH,
           LBRY_SETTINGS,
           isset($this->options[LBRY_SPEECH]) ? esc_attr($this->options[LBRY_SPEECH]) : ''
        );
    }

    /**
     * Prints License input
     */
    public function license_callback()
    {
        // TODO: Maybe make this more elegant?
        $options = '';
        // Create options list, select current license
        foreach (LBRY_AVAILABLE_LICENSES as $value => $name) {
            $selected = $this->options[LBRY_LICENSE] === $value;

            $options .= '<option value="' . $value . '"';
            if ($selected) {
                $options .= ' selected';
            }
            $options .= '>'. $name . '</option>';
        }

        printf(
            '<select id="%1$s" name="%2$s[%1$s]">%3$s</select>',
            LBRY_LICENSE,
            LBRY_SETTINGS,
            $options
        );
    }

    /**
     * Prints LBC per publish input
     */
    public function lbc_publish_callback()
    {
        printf(
           '<input type="number" id="%1$s" name="%2$s[%1$s]" value="%3$s" min="0.01" step="0.01"/>',
           LBRY_LBC_PUBLISH,
           LBRY_SETTINGS,
           $this->options[LBRY_LBC_PUBLISH]
        );
    }
}
