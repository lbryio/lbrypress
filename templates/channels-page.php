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
    <?php //LBRY()->admin->available_channels_callback(); ?>		
    <?php 
          if ( $_POST['lbry_new_channel'] ) {
            $channel = $_POST['lbry_new_channel'];
            $channel = str_replace( '@', '', $channel );
            $channel = str_replace( ' ', '-', $channel );          
            $clean_input['lbry_new_channel'] = sanitize_user( $channel );
          }
          if ( $_POST['lbry_channel_bid_amount'] ) {
            $channel_bid = $_POST['lbry_channel_bid_amount'];
            $clean_input['lbry_channel_bid_amount'] = number_format( floatval( $channel_bid ), 3, '.', '' );
          }
          
          $channel_list = LBRY()->daemon->channel_list();
          if ( $channel_list ) { ?>
            <table class="lbry-channel-table">
              <thead>
                  <tr>
                      <th data-sort="channel">Channel</th>
                      <th data-sort="lbryurl">LBRY URL</th>
                      <th data-sort="claim">Claim ID</th>
                      <th data-sort="date">~ Date Created</th>
                      <th data-sort="posts">Posts</th>
                      <th data-sort="support" colspan="2">Supports</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach ( $channel_list as $channel ):
                  $claim_id = $channel->claim_id;
                  $results = LBRY()->daemon->claim_search( $claim_id );
                  $lbry_url = $results->items[0]->canonical_url;
                  if ( $lbry_url ) {
                      $open_url = str_replace( 'lbry://', 'https://open.lbry.com/', $lbry_url );
                  }
                  $timestamp = $results->items[0]->meta->creation_timestamp;
                  $created_date = date( 'm-d-y', $timestamp );
                  $support_amount = $results->items[0]->meta->support_amount;
                  $claims_published = $results->items[0]->meta->claims_in_channel;
                  if ( ( $support_amount < 0.001 ) ) {
                      ( $support_amount = '0' );
                  } elseif ( ( $support_amount < 0.01 ) && ( $support_amount >= 0.001 ) ) {
                      ( $support_amount = '<0.01' );
                  } elseif ( ( $support_amount <= 0.099 ) && ( $support_amount >= 0.01) ) {
                      ( $support_amount = number_format( floatval( $support_amount ), 2, '.', '' ) );
                  } elseif ( ( $support_amount <= 0.999 ) && ( $support_amount >= 0.1 ) ) {
                      ( $support_amount = number_format( floatval( $support_amount ), 1, '.', '' ) );
                  } else {
                      ( $support_amount = number_format( intval( $support_amount ) ) );
                  }
                  $init_bid = $results->items[0]->amount; ?>          
                  <tr>
                  <td><a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'lbrypress', 'tab' => 'channel-edit', 'claim_id' => urlencode( esc_html__( $claim_id, 'lbrypress' ) ), 'channel_name' => urlencode( esc_html__($channel->name, 'lbrypress' ) ), 'current_support' => urlencode( floatval($support_amount) ), 'init_bid' => urlencode( floatval($init_bid) ), 'lbry_url' => urlencode( esc_url($lbry_url) ) ), 'admin.php' ) ) ); ?>"><?php esc_html_e( $channel->name, 'lbrypress' ); ?></a></td>
                  <td><a href="<?php echo esc_url( $open_url, 'lbrypress' ); ?>"><?php esc_html_e( esc_url( $lbry_url ), 'lbrypress' ); ?></a></td>
                  <td><?php esc_html_e( $claim_id, 'lbrypress' ); ?></td>
                  <td><?php esc_html_e( $created_date, 'lbrypress' ); ?></td>
                  <td><?php esc_html_e( $claims_published, 'lbrypress' ); ?></td>
                  <td><span title="Initial Bid Amount: <?php esc_html_e( $init_bid, 'lbrypress' ); ?>"><img src="<?php echo esc_url( plugin_dir_url( LBRY_PLUGIN_FILE ) . 'admin/images/lbc.png' ) ?>" class="icon icon-lbc bid-icon-lbc channel-bid-icon-lbc"><?php esc_html_e( $support_amount, 'lbrypress' ); ?></span></td>
                  <td><a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'lbrypress', 'tab' => 'supports', 'claim_id' => urlencode( esc_html__( $claim_id, 'lbrypress' ) ), 'supporting_channel' => urlencode( esc_html__($channel->name, 'lbrypress' ) ), 'current_support' => urlencode( floatval($support_amount) ), 'init_bid' => urlencode( floatval($init_bid) ), 'lbry_url' => urlencode( esc_url($lbry_url) ) ), 'admin.php' ) ) ); ?>">Add</a></td>
                  </tr>
            <?php endforeach; ?>
              </tbody>
              <tfoot>
                  <tr><th colspan="7">LBRYPress</th></tr>
              </tfoot>
           </table>
         <?php } else { ?>
           <p>Looks like you haven't added any channels yet, feel free to do so below:</p>
       <?php }
    ?>

	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="lbry_add_channel_form">	
            		
		<input type="hidden" name="action" value="lbry_add_channel">
		<input type="hidden" name="_lbrynonce" value="<?php echo $lbrynonce ?>">
        <h3><?php echo _e( 'Quick Create a New Channel', 'lbrypress' ); ?></h3>
        <p>Create a Channel that can be edited later to add details or set-up a complete <a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'lbrypress', 'tab' => 'channel-edit' ), 'admin.php' ) ) ); ?>">Channel</a> now.</p>		
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
	<p> <?php __( "You are not authorized to perform this operation.", 'lbrypress' ) ?> </p>
<?php   
}