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

defined('ABSPATH') || die('No Peeking!');

// Global Constants
define('LBRY_NAME', 'LBRYPress');
define('LBRY_URI', dirname(__FILE__));
define('LBRY_REQUIRED_PHP_VERSION', '5.3'); // TODO: Figure out what versions we actually need
define('LBRY_REQUIRED_WP_VERSION', '3.1');

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

    require_once(LBRY_URI . '/templates/requirements-error.php');
}

/*
 * Check requirements and load main class
 * The main program needs to be in a separate file that only gets loaded if the plugin requirements are met. Otherwise older PHP installations could crash when trying to parse it.
 */
if (lbry_requirements_met()) {
    require_once(dirname(__FILE__) . '/classes/lbrypress.php');

    if (class_exists('LBRYPress')) {
        $lbryPress = LBRYPress::get_instance();
        // register_activation_hook(__FILE__, array( $lbryPress, 'activate' ));
        // register_deactivation_hook(__FILE__, array( $lbryPress, 'deactivate' ));
    }
} else {
    add_action('admin_notices', 'lbry_requirements_error');
}
