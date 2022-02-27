<?php
/**
 * The Options Page with tabs
 * @package LBRYPress
 */
defined('ABSPATH') || die(); // Exit if accessed directly

$LBRY = LBRY();
$wallet_balance = $LBRY->daemon->wallet_balance();
$available_balance = $wallet_balance->result->available;
$total_balance = $wallet_balance->result->total;
$lbry_active_tab  = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
?>

<div class="wrap">
    <h1><?php esc_html_e( get_admin_page_title(), 'lbrypress' ); ?></h1>
    <h2><img src="<?php echo esc_url( plugin_dir_url( LBRY_PLUGIN_FILE ) . 'admin/images/lbc.png' ) ?>" class="icon icon-lbc wallet-icon-lbc" title="<?php echo esc_attr( number_format( $total_balance, 3, '.', ',' ) ); ?> Wallet Total Balance"><code title="<?php echo esc_attr( number_format( $total_balance, 3, '.', ',' ) ); ?> Wallet Total Balance"><?php esc_html_e( number_format( $available_balance, 3, '.', ',' ) ); ?></code> Wallet Available Balance</h2>
    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url( admin_url( 'options.php?page=lbrypress&tab=general' ) ); ?>" class="nav-tab <?php echo $lbry_active_tab == 'general' || '' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=lbrypress&tab=channels' ) ); ?>" class="nav-tab <?php echo $lbry_active_tab == 'channels' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Channels' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'options.php?page=lbrypress&tab=speech' ) ); ?>" class="nav-tab <?php echo $lbry_active_tab == 'speech' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Spee.ch' ); ?></a>
        <?php
        if ( $lbry_active_tab == 'supports' ) {
            $admin_url = admin_url( 'admin.php?page=lbrypress&tab=supports' );
            printf(
                '<a href="' . esc_url( $admin_url ) . '" class="nav-tab nav-tab-active">' . esc_html__( 'Supports', 'lbrypress') . '</a>',
                $admin_url,
            );
        }
        if ( $lbry_active_tab == 'channel-edit' ) {
            $admin_url = admin_url( 'admin.php?page=lbrypress&tab=channel-edit' );
            printf(
                '<a href="' . esc_url( $admin_url ) . '" class="nav-tab nav-tab-active">' . esc_html__( 'Channel', 'lbrypress' ) . '</a>',
                $admin_url,
            );
        } ?>
    </nav>
        <?php if ( $lbry_active_tab == 'channels' ) {
            include_once( 'channels-page.php' );
        } elseif ( $lbry_active_tab == 'supports' ) {
            include_once( 'supports-add-form.php' );
        } elseif ( $lbry_active_tab == 'channel-edit' ) {
            include_once( 'channel-edit-page.php' );
        } else {
            ?>
            <form class="form-table" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
            <?php // TODO: write this as a switch?
        }
                if ( $lbry_active_tab == 'general' ) {
                    settings_fields( 'lbry_general_settings' );
                    do_settings_sections( LBRY_ADMIN_PAGE );
                    submit_button();
                } elseif ( $lbry_active_tab == 'channels' ) {
                    //include_once( 'channels-page.php' );
                } elseif ( $lbry_active_tab == 'supports' ) {
                    //include_once( 'supports-add-form.php' ); 
                } elseif ( $lbry_active_tab == 'channel-edit' ) {
                    //include_once( 'supports-add-form.php' ); 
                } elseif ( $lbry_active_tab == 'speech' ) {
                    settings_fields( LBRY_SPEECH_SETTINGS );
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
