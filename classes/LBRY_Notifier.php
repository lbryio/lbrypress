<?php
/**
 * Class for sending LBRYPress related admin notifications
 *
 * @package LBRYPress
 */

class LBRY_Notifier
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
