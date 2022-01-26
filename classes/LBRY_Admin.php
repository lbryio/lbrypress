<?php
/**
* Class for intializing and displaying all admin settings
*
* @package LBRYPress
*/
defined('ABSPATH') || die(); // Exit if accessed directly

class LBRY_Admin {
    /**
    * LBRY_Admin Constructor
    */
    public function __construct() {
        add_action('admin_menu', array($this, 'create_options_page'));
        add_action('admin_init', array($this, 'options_page_init'));
        add_action('admin_init', array($this, 'wallet_balance_warning'));
        add_action('admin_post_lbry_add_channel', array($this, 'add_channel'));
    }

    /**
    * Creates the options page in the WP admin interface
    */

    public function create_options_page() {

        $hook_suffix = add_menu_page(
                          __( 'LBRYPress Settings', 'lbrypress' ),
                          __( 'LBRYPress', 'lbrypress' ),
                          'manage_options',
                          LBRY_ADMIN_PAGE,
                          array( $this, 'options_page_html' ),
                          plugin_dir_url( LBRY_PLUGIN_FILE  ) . '/admin/images/lbry-logo.svg'
                          );

        // Admin stylesheet enqueue
        function load_admin_stylesheet() {
                    wp_enqueue_style(
                        'lbry-admin',
                        plugins_url( '/admin/css/lbry-admin.css', LBRY_PLUGIN_FILE ),
                        array(),
                        LBRY_VERSION,
                        'all'
                    );
        }
        add_action( 'load-' . $hook_suffix , 'load_admin_stylesheet' );
        function lbry_plugin_not_configured_notice() {
          	echo "<div id='notice' class='updated fade'><p>LBRYPress plugin is not configured yet. Please do it now.</p></div>\n";
        }
        $lbry_wallet = get_option('lbry_wallet');
        if ( ! isset($lbry_wallet) ) {
            add_action( 'admin_notices', 'lbry_plugin_not_configured_notice' );
        }
        function admin_permission_check() {
          	if (!current_user_can('manage_options'))  {
          		wp_die( __('You do not have sufficient permissions to access this page.') );
          	}
        }
    }

    /**
    * Returns the Options Page HTML for the plugin
    */
    public function options_page_html() {
      //$LBRY = LBRY();
          // Set class properties to be referenced in callbacks
          $this->options = get_option( LBRY_SETTINGS );
        //  $this->options_channel = get_option( 'lbry_channel_settings' );
        //  $this->options_speech = get_option( 'lbry_speech_settings' );
          require_once( LBRY_ABSPATH . 'templates/options-page.php' );
    }

    /**
    * Register settings for the plugin
    */
    public function options_page_init() {

        register_setting(
            'lbry_general_settings',
            LBRY_SETTINGS,
            array( $this, 'sanitize' )
        );

        add_settings_section(
            LBRY_SETTINGS_SECTION_GENERAL, // ID
            'General Settings', // Title
            array( $this, 'general_section_callback' ), // Callback
            LBRY_ADMIN_PAGE // Page
        );

        add_settings_field(
            LBRY_WALLET,
            'LBRY Wallet Address',
            array( $this, 'wallet_callback' ),
            LBRY_ADMIN_PAGE,
            LBRY_SETTINGS_SECTION_GENERAL
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
            array( $this, 'lbc_per_publish_callback' ),
            LBRY_ADMIN_PAGE,
            LBRY_SETTINGS_SECTION_GENERAL
        );

        register_setting(
            'lbry_channel_settings',
            LBRY_SETTINGS,
            array( $this, 'sanitize' )
        );

        add_settings_section(
            'lbry_available_channels_section',
            'Available Channels to Publish to',
            array( $this, 'available_channels_callback' ),
            'lbrypress-channel'
        );
        // add_settings_field(
        //     'lbry_available_channels_list',
        //     'Your Published Channels',
        //     array( $this, 'lbry_available_channels_callback' ),
        //     'lbrypress-channel',
        //     'lbry_available_channels_section'
        // );

        add_settings_section(
            'lbry_settings_section_channel',
            'Create a New Channel',
            array( $this, 'channel_section_callback' ),
            'lbrypress-channel'
        );

        add_settings_field(
            'new_channel',
            'New Channel Name',
            array( $this, 'channel_create_callback' ),
            'lbrypress-channel',
            'lbry_settings_section_channel'
        );

        add_settings_field(
            'bid_amount',
            'Amount of LBC to Bid',
            array( $this, 'channel_lbc_bid_callback' ),
            'lbrypress-channel',
            'lbry_settings_section_channel'
        );

        register_setting(
            'lbry_speech_settings',
            LBRY_SETTINGS,
            array( $this, 'sanitize' )
        );

        add_settings_section(
            'lbry_settings_section_speech', // ID
            'Spee.ch Channel Settings', // Title
            array( $this, 'speech_section_callback' ), // Callback
            'lbrypress-speech' // Page
        );

        add_settings_field(
            LBRY_SPEECH,
            'Spee.ch URL',
            array( $this, 'speech_callback' ),
            'lbrypress-speech',
            'lbry_settings_section_speech'
        );

        add_settings_field(
            LBRY_SPEECH_CHANNEL,
            'Spee.ch Channel',
            array( $this, 'speech_channel_callback' ),
            'lbrypress-speech',
            'lbry_settings_section_speech'
        );

        add_settings_field(
            LBRY_SPEECH_PW,
            'Spee.ch Password',
            array( $this, 'speech_pw_callback' ),
            'lbrypress-speech',
            'lbry_settings_section_speech'
        );
    }

    /**
    * Sanitizes setting input
    * // COMBAK Potentially sanitize more
    */
    public function sanitize($input) {
        $input[LBRY_WALLET] = sanitize_text_field( $input[LBRY_WALLET] );
        $input[LBRY_SPEECH] = esc_url_raw( $input[LBRY_SPEECH] );
        // TODO sanitize License
        $input['new_channel'] = sanitize_text_field( $input['new_channel'] );
        $input['bid_amount'] = number_format( floatval( $input['bid_amount'] ), 3, '.', '' );
        $input[LBRY_LBC_PUBLISH] = number_format( floatval( $input[LBRY_LBC_PUBLISH] ), 3, '.', '' );

        if (!empty($input[LBRY_SPEECH_CHANNEL])) {
            $channel = $input[LBRY_SPEECH_CHANNEL];
            $channel = str_replace('@', '', $channel);
            $input[LBRY_SPEECH_CHANNEL] = sanitize_user($channel);
        }

        if (!empty($input[LBRY_SPEECH_PW])) {
            $input[LBRY_SPEECH_PW] = sanitize_text_field($input[LBRY_SPEECH_PW]);
            $encrypted = $this->encrypt($input[LBRY_SPEECH_PW]);
            $input[LBRY_SPEECH_PW] = $encrypted;
        } else {
            // If we have a password and its empty, keep orginal password
            if (!empty(get_option(LBRY_SETTINGS)[LBRY_SPEECH_PW])) {
                $input[LBRY_SPEECH_PW] = get_option(LBRY_SETTINGS[LBRY_SPEECH_PW]);
            }
        }

        return $input;
    }

    /**
    * Section info for the General Section
    */
    public function general_section_callback() {
        print 'This is where you can configure how LBRYPress will distribute your content:';
    }

    /**
    * Section info for the Available Channel(s) Section
    */
    public function available_channels_callback() {
        $channel_list = LBRY()->daemon->channel_list();

        if ($channel_list): ?>
            <ul class="lbry-channel-list">
                <?php foreach ($channel_list as $channel): ?>
                    <li><?php esc_html_e($channel->name) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Looks like you haven't added any channels yet, feel free to do so below:</p>
        <?php endif;
    }

    /**
    * Section info for the Create New Channel Section
    */
    public function channel_section_callback() {
        // TODO add some markup for a callback
    }

    /**
    * Section info for the Speech Channel Section
    */
    public function speech_section_callback() {
      print 'If you have a Spee.ch account, you can enter your account details here, if you don\'t already have a Spee.ch account, no need to enter anything here.';
    }

    /**
    * Prints Wallet input
    */
    public function wallet_callback() {
        // Get first available account address from Daemon
        $address = LBRY()->daemon->address_list();
        $address = is_array($address) && !empty($address) ? $address[0]->address : '';
        printf(
            '<input type="text" id="'. esc_attr('%1$s') .'" name="'. esc_attr('%1$s') .'" value="' . esc_attr('%2$s') . '" readonly />',
            LBRY_WALLET,
//            LBRY_SETTINGS,
            $address
        );
    }

    /**
    * Prints License input
    */
    public function license_callback() {
        // TODO: Maybe make this more elegant?
        $options = '';
        // Create options list, select current license
        //
        foreach (LBRY()->licenses as $value => $name) {
            $selected = $this->options[LBRY_LICENSE] === $value;

            $options .= '<option value="' . $value . '"';
            if ($selected) {
                $options .= ' selected';
            }
            $options .= '>'. $name . '</option>';
        }

        printf(
            '<select id="'.esc_attr('%1$s').'" name="'. esc_attr('%1$s') .'">' . esc_html('%2$s') . '</select>',
            LBRY_LICENSE,
//            LBRY_SETTINGS,
            $options
        );
    }

    /**
    * Prints LBC per publish input
    */
    public function lbc_per_publish_callback() {
        printf(
            '<input type="number" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('%3$s') . '" min="0.001" step="0.001"/>',
            LBRY_LBC_PUBLISH,
            LBRY_SETTINGS,
            $this->options[LBRY_LBC_PUBLISH]
        );
    }

    /**
     * Channels Page
     */

    public function channel_create_callback() {
        printf(
          '<span>@</span><input type="text" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('%3$s') . '" placeholder="your-new-channel" required>',
          'new_channel',
          LBRY_SETTINGS,
          $this->options['new_channel']
        );
    }
    public function channel_lbc_bid_callback() {
        printf(
            '<input type="number" step="0.001" min="0.001" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_html('%3$s') . '" required>',
            'bid_amount',
            LBRY_SETTINGS,
            $this->options['bid_amount']
        );
    }

    /**
    * Prints Spee.ch input
    */
    public function speech_callback() {
        printf(
            '<input type="text" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('%3$s') . '" placeholder="https://your-speech-address.com"/>',
            LBRY_SPEECH,
            LBRY_SETTINGS,
            isset($this->options[LBRY_SPEECH]) ? $this->options[LBRY_SPEECH] : ''
        );
    }

    /**
    * Prints Spee.ch channel input
    */
    public function speech_channel_callback() {
        printf(
            '<span>@</span><input type="text" id="' . esc_attr('%1$s') . '" name="'. esc_attr('%1$s') .'" value="' . esc_attr('%3$s') . '" placeholder="your-channel"/>',
            LBRY_SPEECH_CHANNEL,
            LBRY_SETTINGS,
            isset($this->options[LBRY_SPEECH_CHANNEL]) ? $this->options[LBRY_SPEECH_CHANNEL] : ''
        );
    }

    /**
    * Prints Spee.ch password input
    */
    public function speech_pw_callback() {
        printf(
            '<input type="password" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '"' . esc_attr('%3$s') . '" placeholder="Leave empty for same password"',
            LBRY_SPEECH_PW,
            LBRY_SETTINGS,
            isset($this->options[LBRY_SPEECH_PW]) ? $this->options[LBRY_SPEECH_PW] : ''

        );
    }

    /**
    * Handles new channel form submission
    */
    public function add_channel() {
        $redirect_url = admin_url( 'options-general.php?page=' . LBRY_ADMIN_PAGE );

        // Check that nonce
        if ( ! isset( $_POST['new_channel'] ) || ! isset($_POST['bid_amount'] ) ) {
            LBRY()->notice->set_notice( 'error', 'Must supply both channel name and bid amount' );
        } else {
            $new_channel = $_POST['new_channel'];
            $bid_amount = $_POST['bid_amount'];

            // Try to add the new channel
            try {
                $result = LBRY()->daemon->channel_new( $new_channel, $bid_amount );
                // Tell the user it takes some time to go through
                LBRY()->notice->set_notice( 'success', 'Successfully added a new channel! Please wait a few minutes for the bid to process.', true );
            } catch ( \Exception $e ) {
                LBRY()->notice->set_notice( 'error', $e->getMessage(), false );
            }
        }

        wp_safe_redirect( $redirect_url );
        exit();
    }

    /**
     * Checks at most once an hour to see if the wallet balance is too low
     */
    // IDEA: Check user permissions possibly
    public static function wallet_balance_warning() {
        // See if we've checked in the past two hours
        if (!get_transient('lbry_wallet_check')) {
            $balance = LBRY()->daemon->wallet_balance();
            if ($balance < get_option(LBRY_SETTINGS)[LBRY_LBC_PUBLISH] * 20) {
                // If LBRY Balance is low, send email, but only once per day
                if (!get_transient('lbry_wallet_warning_email')) {
                    $email = get_option('admin_email');
                    $subject = 'Your LBRYPress Wallet Balance is Low!';
                    $message = "You LBRY Wallet for your WordPress installation at " . site_url() . " is running very low.\r\n\r\nYou currently have " . $balance . ' LBC left in your wallet. In order to keep publishing to the LBRY network, please add some LBC to your account.';
                    wp_mail($email, $subject, $message);
                    set_transient('lbry_wallet_warning_email', true, DAY_IN_SECONDS);
                }
            }
            set_transient('lbry_wallet_check', true, 2 * HOUR_IN_SECONDS);
        }
    }

    private function encrypt($plaintext) {
        $ivlen = openssl_cipher_iv_length($cipher="AES-256-CTR");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, wp_salt(), $options=OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, wp_salt(), $as_binary=true);
        return base64_encode($iv.$hmac.$ciphertext_raw);
    }

    private function decrypt($ciphertext) {
        $c = base64_decode($ciphertext);
        $ivlen = openssl_cipher_iv_length($cipher="AES-256-CTR");
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len=32);
        $ciphertext_raw = substr($c, $ivlen+$sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, wp_salt(), $options=OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, wp_salt(), $as_binary=true);
        if (hash_equals($hmac, $calcmac)) {//PHP 5.6+ timing attack safe comparison
            return $original_plaintext;
        }

        return false;
    }

    public function get_speech_pw() {
        $ciphertext = get_option(LBRY_SETTINGS)[LBRY_SPEECH_PW];
        if (empty($ciphertext)) {
            return false;
        }

        return $this->decrypt($ciphertext);
    }
}
