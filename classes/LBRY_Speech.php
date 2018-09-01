<?php
/**
 * Connects to an spee.ch style server to host assets via the LBRY Protocol
 *
 * Visit https://github.com/lbryio/spee.ch for more info
 *
 * @package LBRYPress
 */

class LBRY_Speech
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

    public function get_address()
    {
        return get_option(LBRY_SPEECH);
    }

    public function set_address($address)
    {
        update_option(LBRY_SPEECH, $address);
    }
}
