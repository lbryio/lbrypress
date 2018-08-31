<?php
/**
 * Class for connecting with the LBRY Network
 *
 * @package LBRYPress
 */

class LBRY_Network
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
}
