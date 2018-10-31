<?php
/**
 * @package LBRYPress
 */
/*
Plugin Name: LBRYPress
Plugin URI:
Description: Connect your wordpress posts to
Version: 0.0.1
Author: Underground Web Lab
Author URI: https://undergroundweblab.com
License: MIT License
Text Domain: lbrypress

Copyright 2018 Underground Web Lab

// TODO: Finalize License Verbage

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

defined('ABSPATH') || die(); // Exit if accessed directly

define('LBRY_REQUIRED_PHP_VERSION', '5.3'); // TODO: Figure out what versions we actually need
define('LBRY_REQUIRED_WP_VERSION', '3.1');
define('LBRY_PLUGIN_FILE', __FILE__);

/**
 * Checks if the system requirements are met
 *
 * @return bool True if system requirements are met, false if not
 */
function lbry_requirements_met()
{
    global $wp_version;
    //require_once( ABSPATH . '/wp-admin/includes/plugin.php' );		// to get is_plugin_active() early

    if (version_compare(PHP_VERSION, LBRY_REQUIRED_PHP_VERSION, '<')) {
        return false;
    }

    if (version_compare($wp_version, LBRY_REQUIRED_WP_VERSION, '<')) {
        return false;
    }

    return true;
}

/**
 * Prints an error that the system requirements weren't met.
 */
function lbry_requirements_error()
{
    global $wp_version;

    require_once(dirname(__FILE__) . '/templates/requirements-error.php');
}


/**
 * Returns the singal instance of LBRYPress
 */
function LBRY()
{
    if (lbry_requirements_met()) {
        if (! class_exists('LBRYPress')) {
            require_once(dirname(__FILE__) . '/classes/LBRYPress.php');
        }
        // Bring in configuration requirements
        // HACK: Will probably be getting rid of configuration once we sort out Spee.ch Implementation
        require_once(dirname(__FILE__) . '/lbry_config.php');
        return LBRYPress::instance();
    } else {
        add_action('admin_notices', 'lbry_requirements_error');
        return;
    }
}

// Kickoff
LBRY();
