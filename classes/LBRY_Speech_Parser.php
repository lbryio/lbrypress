<?php
/**
 * Parses post markup in order to use specified spee.ch url for assets
 *
 * @package LBRYPress
 */

class LBRY_Speech_Parser
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
