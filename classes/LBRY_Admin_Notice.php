<?php
/**
* Class for logging and displaying admin notices
*
* @package LBRYPress
*/
defined('ABSPATH') || die(); // Exit if accessed directly

class LBRY_Admin_Notice
{

    public function __construct() {
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }

    /**
    * Displays all messages set with the lbry_notices transient
    */
    public function admin_notices()
    {
        if ( get_transient( 'lbry_notices' ) ) {
            $notices = get_transient( 'lbry_notices' );
            foreach ( $notices as $key => $notice ) {
                $this->create_admin_notice( $notice );
            }
            delete_transient( 'lbry_notices' );
        }
    }

    /**
     * Sets transients for admin errors
     */
    // TODO: Make sure we only set one transient at a time per error
    public function set_notice( $status = 'error', $message = 'Something went wrong', $is_dismissible = false )
    {
        $notice = array(
            'status' => $status,
            'message' => $message,
            'is_dismissible' => $is_dismissible
        );

        if (! get_transient( 'lbry_notices' ) ) {
            set_transient( 'lbry_notices', array( $notice ) );
        } else {
            $notices = get_transient( 'lbry_notices' );
            $notices[] = $notice;
            set_transient( 'lbry_notices', $notices );
        }
    }

    /**
     * Prints an admin notice
     */
    private function create_admin_notice( $notice )
    {
        $class = 'notice notice-' . $notice['status'];
        if ( $notice['is_dismissible'] ) {
            $class .= ' is-dismissible';
        }
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $notice['message'] ) );
    }
}
