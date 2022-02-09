<?php
/**
 * ============================
 * CHANNELS SETTINGS ADMIN PAGE
 * Uses the post-admin action so we can use the $_POST global variable to build our cURL request and the settings are not saved to the datbase
 * @package LBRYPress
 * ============================
 */
defined('ABSPATH') || die(); // Exit if accessed directly

if ( current_user_can( 'manage_options' ) ) {

    // Generate a custom nonce
    $lbrynonce = wp_create_nonce( 'add_channel_nonce' );

    // Build the page
    ?>	
			
	<h3><?php _e( 'Available Channels To Publish', 'lbrypress' ); ?></h3>
    <?php LBRY()->admin->available_channels_callback(); ?>		
    <?php if ( isset( $_POST['lbry_new_channel'] ) ) {
            $channel = $_POST['lbry_new_channel'];
            $channel = str_replace( '@', '', $channel );
            $channel = str_replace( ' ', '-', $channel );          
            $clean_input['lbry_new_channel'] = sanitize_user( $channel );
          }
          if ( isset( $_POST['lbry_channel_bid_amount'] ) ) {
            $channel_bid = $_POST['lbry_channel_bid_amount'];
            $clean_input['lbry_channel_bid_amount'] = number_format( floatval( $channel_bid ), 3, '.', '' );
          }
    ?>

	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="lbry_add_channel_form">	
            		
		<input type="hidden" name="action" value="lbry_add_channel">
		<input type="hidden" name="_lbrynonce" value="<?php echo $lbrynonce ?>">
        <h3><?php echo _e( 'Create a New Channel', 'lbrypress' ); ?></h3>			
		<table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">New Channel Name</th>
                        <td>
                            <?php printf(
                                '<input type="text" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="@' . esc_attr('%2$s') . '" placeholder="your-new-channel" required>',
                                'lbry_new_channel',
                                $clean_input['lbry_new_channel'],
                            ); ?>
                            <p>No Spaces in Channel Names</p>
                        </td>
                </tr>
                <tr>
                    <th scope="row">Amount of LBC to Bid</th>
                        <td>
                            <?php printf(
                                '<input type="number" step="0.001" min="0.001" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('%2$.3f') . '" required>',
                                'lbry_channel_bid_amount',
                                $clean_input['lbry_channel_bid_amount'],
                            ); ?>
                            <p>Current minimum bid <img src="<?php echo esc_url( plugin_dir_url( LBRY_PLUGIN_FILE ) . 'admin/images/lbc.png' ) ?>" class="icon icon-lbc bid-icon-lbc"> 0.001</p>
                        </td>
                </tr>
            </tbody>
        </table>                 
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Create New Channel"></p>
	</form>			
<?php    
} else {  
?>
	<p> <?php __( "You are not authorized to perform this operation.", $this->plugin_name ) ?> </p>
<?php   
}