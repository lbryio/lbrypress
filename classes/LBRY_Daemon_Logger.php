<?php
/**
 * A class for logging LBRY Daemon interactions
 *
 * @package LBRYPress
 */

class LBRY_Daemon_Logger
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
