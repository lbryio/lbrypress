<?php
/**
 * The Admin Options Page
 * @package LBRYPress
 */
defined('ABSPATH') || die(); // Exit if accessed directly

$LBRY = LBRY();
$wallet_balance = $LBRY->daemon->wallet_balance();
$available_balance = $wallet_balance->result->available;
$channel_tab = ( isset( $_GET['action'] ) && 'channels' == $_GET['action'] ) ? true : false;
$speech_tab = ( isset( $_GET['action'] ) && 'speech' == $_GET['action'] ) ? true : false; ?>

<div class="wrap">
    <h1><?php esc_html( get_admin_page_title() ); ?></h1>
    <!-- <h2>Your wallet available to spend amount:</h2> -->
    <h2><img src="<?php echo esc_url( plugin_dir_url( LBRY_PLUGIN_FILE ) . 'admin/images/lbc.png' ) ?>" class="icon icon-lbc wallet-icon-lbc"><code><?= number_format( $available_balance, 3, '.', ',' ); ?></code> Wallet Available Balance</h2>
    <nav class="nav-tab-wrapper">
        <a href="<?php echo admin_url( 'admin.php?page=lbrypress' ) ?>" class="nav-tab<?php if ( !isset( $_GET['action'] ) || isset( $_GET['action'] ) && 'channels' != $_GET['action'] && 'speech' != $_GET['action'] ) echo ' nav-tab-active'; ?>"><?php esc_html_e( 'Settings' ); ?></a>
        <a href="<?php echo esc_url( add_query_arg( array( 'action'=>'channels' ), admin_url( 'admin.php?page=lbrypress' ) ) ); ?>" class="nav-tab<?php if ( $channel_tab ) echo ' nav-tab-active'; ?>"><?php esc_html_e( 'Channels' ); ?></a>
        <a href="<?php echo esc_url( add_query_arg( array( 'action'=>'speech' ), admin_url( 'admin.php?page=lbrypress') ) ); ?>" class="nav-tab<?php if ( $speech_tab ) echo ' nav-tab-active'; ?>"><?php esc_html_e( 'Spee.ch' ); ?></a>
    </nav>
        <form class="" action="options.php" method="post">
            <?php // render fields for tabbed pages
            if ( $channel_tab ) {
                settings_fields( 'lbry_channel_settings' );
                do_settings_sections( 'lbrypress-channel' );
                submit_button( 'Create New Channel' );
            } elseif ( $speech_tab ) {
                settings_fields( 'lbry_speech_settings' );
                do_settings_sections( 'lbrypress-speech' );
                submit_button();
            } else {
                settings_fields( 'lbry_general_settings' );
                do_settings_sections( LBRY_ADMIN_PAGE );
                submit_button();
            }
            ?>
          </form>
  </div><!-- wrap -->
