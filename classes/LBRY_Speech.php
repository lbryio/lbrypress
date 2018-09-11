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
    /**
     * [__construct description]
     */
    public function __construct()
    {
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
