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
        add_action('admin_init', array($this, 'wallet_balance_warning'));
        add_action('admin_post_lbry_add_channel', array($this, 'add_channel'));
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
        register_setting(LBRY_SETTINGS_GROUP, LBRY_SETTINGS, array('sanitize_callback' => array($this, 'sanitize')));

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
            LBRY_SPEECH,
            'Spee.ch URL',
            array( $this, 'speech_callback' ),
            LBRY_ADMIN_PAGE,
            LBRY_SETTINGS_SECTION_GENERAL
        );

        add_settings_field(
            LBRY_SPEECH_CHANNEL,
            'Spee.ch Channel',
            array( $this, 'speech_channel_callback' ),
            LBRY_ADMIN_PAGE,
            LBRY_SETTINGS_SECTION_GENERAL
        );

        add_settings_field(
            LBRY_SPEECH_PW,
            'Spee.ch Password',
            array( $this, 'speech_pw_callback' ),
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
    * // COMBAK Potentially sanitize more
    */
    public function sanitize($input)
    {
        if (!empty($input[LBRY_SPEECH_CHANNEL])) {
            $channel = $input[LBRY_SPEECH_CHANNEL];
            $channel = str_replace('@', '', $channel);
            $input[LBRY_SPEECH_CHANNEL] = $channel;
        }

        if (!empty($input[LBRY_SPEECH_PW])) {
            $encrypted = $this->encrypt($input['lbry_speech_pw']);
            $input[LBRY_SPEECH_PW] = $encrypted;
        } else {
            // If we have a password and its empty, keep orginal password
            if (!empty(get_option(LBRY_SETTINGS)[LBRY_SPEECH_PW])) {
                $input[LBRY_SPEECH_PW] = get_option(LBRY_SETTINGS)[LBRY_SPEECH_PW];
            }
        }

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
        // Get first available address from Daemon
        $address = LBRY()->daemon->address_list();
        $address = is_array($address) && !empty($address) ? $address[0] : '';
        printf(
            '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" readonly />',
            LBRY_WALLET,
            LBRY_SETTINGS,
            $address
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
    * Prints Spee.ch channel input
    */
    public function speech_channel_callback()
    {
        printf(
            '<span>@</span><input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" placeholder="your-channel"/>',
            LBRY_SPEECH_CHANNEL,
            LBRY_SETTINGS,
            isset($this->options[LBRY_SPEECH_CHANNEL]) ? esc_attr($this->options[LBRY_SPEECH_CHANNEL]) : ''
        );
    }

    /**
    * Prints Spee.ch password input
    */
    public function speech_pw_callback()
    {
        printf(
            '<input type="password" id="%1$s" name="%2$s[%1$s]" value="" placeholder="Leave empty for same password"',
            LBRY_SPEECH_PW,
            LBRY_SETTINGS
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

    /**
    * Handles new channel form submission
    */
    public function add_channel()
    {
        $redirect_url = admin_url('options-general.php?page=' . LBRY_ADMIN_PAGE);

        // Check that nonce
        if (! isset($_POST['_lbrynonce']) || ! wp_verify_nonce($_POST['_lbrynonce'], 'lbry_add_channel')) {
            LBRY()->notice->set_notice('error');
        } elseif (! isset($_POST['new_channel']) || ! isset($_POST['bid_amount'])) {
            LBRY()->notice->set_notice('error', 'Must supply both channel name and bid amount');
        } else {
            $new_channel = $_POST['new_channel'];
            $bid_amount = $_POST['bid_amount'];

            // Try to add the new channel
            try {
                $result = LBRY()->daemon->channel_new($new_channel, $bid_amount);
                // Tell the user it takes some time to go through
                LBRY()->notice->set_notice('success', 'Successfully added a new channel! Please wait a few minutes for the bid to process.', true);
            } catch (\Exception $e) {
                LBRY()->notice->set_notice('error', $e->getMessage(), false);
            }
        }

        wp_safe_redirect($redirect_url);
        exit();
    }

    /**
     * Checks at most once an hour to see if the wallet balance is too low
     */
    // IDEA: Check user permissions possibly
    public static function wallet_balance_warning()
    {
        // See if we've checked in the past two hours
        if (!get_transient('lbry_wallet_check')) {
            $balance = LBRY()->daemon->wallet_balance();
            if ($balance < get_option(LBRY_SETTINGS)[LBRY_LBC_PUBLISH] * 20) {
                // If LBRY Balance is low, send email, but only once per day
                if (!get_transient('lbry_wallet_warning_email')) {
                    $email = get_option('admin_email');
                    $subject = 'Your LBRYPress Wallet Balance is Low!';
                    $message = "You LBRY Wallet for your wordpress installation at " . site_url() . " is running very low.\r\n\r\nYou currently have " . $balance . ' LBC left in your wallet. In order to keep publishing to the LBRY network, please add some LBC to your account.';
                    wp_mail($email, $subject, $message);
                    set_transient('lbry_wallet_warning_email', true, DAY_IN_SECONDS);
                }
            }
            set_transient('lbry_wallet_check', true, 2 * HOUR_IN_SECONDS);
        }
    }

    private function encrypt($plaintext)
    {
        $ivlen = openssl_cipher_iv_length($cipher="AES-256-CTR");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, wp_salt(), $options=OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, wp_salt(), $as_binary=true);
        return base64_encode($iv.$hmac.$ciphertext_raw);
    }

    private function decrypt($ciphertext)
    {
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

    public function get_speech_pw()
    {
        $ciphertext = get_option(LBRY_SETTINGS)[LBRY_SPEECH_PW];
        if (empty($ciphertext)) {
            return false;
        }

        return $this->decrypt($ciphertext);
    }
}
