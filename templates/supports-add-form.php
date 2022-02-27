<?php
/**
 * ============================
 * ADD SUPPORTS FORM ADMIN PAGE
 * Uses the post-admin action and the $_POST global variable to build our cURL request
 * @package LBRYPress
 * ============================
 */
defined('ABSPATH') || die(); // Exit if accessed directly

if ( current_user_can( 'manage_options' ) ) {

    // Generate a custom nonce
    $lbrynonce = wp_create_nonce( 'add_supports_nonce' );

    // TODO sanitize more
    $claim_id = $_GET['claim_id'];
    $claim_id = sanitize_text_field( $claim_id );
    $lbry_url = $_GET['lbry_url'];
    $lbry_url = urldecode($lbry_url);
    $lbry_url = sanitize_text_field($lbry_url);
    $init_bid = $_GET['init_bid'];
    $init_bid = number_format( floatval( $init_bid ), 3, '.', '' );
    $supporting_channel = $_GET['supporting_channel'];
    $supporting_channel = sanitize_user( $supporting_channel );
    $support_amount = $_GET['current_support'];
    $support_amount = number_format( floatval( $support_amount ), 3, '.', '' );
    $return_post = $_GET['post_id'];
    $return_post = intval( $return_post );


    // Build the page ?>

	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="lbry_add_supports_form">	
            		
		<input type="hidden" name="action" value="lbry_add_supports">
		<input type="hidden" name="_lbrynonce" value="<?php echo esc_attr($lbrynonce); ?>">
        <input type="hidden" name="post_id" value="<?php echo esc_attr($return_post); ?>">
        <input type="hidden" name="lbry_url" value="<?php echo esc_url($lbry_url); ?>">
        <input type="hidden" name="supporting_channel" value="<?php echo esc_attr($supporting_channel); ?>">

        <h2><?php echo _e( 'Add Supports to Claim:', 'lbrypress' ); ?></h2>
            <?php printf(
                        '<h3>' . esc_html__( '%1$s', 'lbrypress' ) . '</h3>
                        <h4>Claim ID: <code>' . esc_html__( '%2$s', 'lbrypress' ) . '</code></h4><p>If you want to add supports to a different channel or post, use the channel or post link that corresponds with that specific claim to add supports.</p>',
                        $lbry_url,
                        $claim_id,
                    ); ?>			
		<table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">Claim ID</th>
                        <td>
                            <?php printf(
                                '<input type="text" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('%2$s') . '" required readonly>',
                                'lbry_supports_add_claim_id',
                                $claim_id,
                            ); ?>
                            <p>Claims can be for either Channels or Posts</p>
                        </td>
                </tr>
                <tr>
                    <th scope="row">Add LBC as Support</th>
                        <td>
                            <?php printf(
                                '<input type="number" step="0.001" min="0.01" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('%2$.3f') . '" required>',
                                'lbry_supports_add_bid_amount',
                                $bid_amount,
                            ); ?>
                            <p>Current minimum support bid <img src="<?php echo esc_url( plugin_dir_url( LBRY_PLUGIN_FILE ) . 'admin/images/lbc.png' ) ?>" class="icon icon-lbc bid-icon-lbc"> 0.01</p>
                        </td>
                </tr>
                <tr>
                    <th scope=""row>Amount Used to Create Claim</th>
                        <td>
                            <?php printf(
                                '<p><img src="' . esc_attr( '%2$s', 'lbrypress' ) . '" class="icon icon-lbc bid-icon-lbc"> ' . esc_html__( '%1$.3f', 'lbrypress' ) . '</p><p>Initial bid that was used to publish the claim.</p>',
                                $init_bid,
                                plugin_dir_url( LBRY_PLUGIN_FILE ) . 'admin/images/lbc.png',
                            ); ?>
                        </td>
                </tr>
                <tr>
                    <th scope="row">Current Supports</th>
                        <td>
                            <?php printf(
                                '<p><img src="' . esc_attr( '%2$s', 'lbrypress' ) . '" class="icon icon-lbc bid-icon-lbc"> ' . esc_html__('%1$.3f', 'lbrypress' ) . '</p><p>May not include very recently added supports, please be patient as it may take a short while to update.</p>',
                                $support_amount,
                                plugin_dir_url( LBRY_PLUGIN_FILE ) . 'admin/images/lbc.png',
                            ); ?>
                        </td>
            </tbody>
        </table>                 
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Add Supports"></p>
    </form>
    <?php 
} else {  
    ?>
    <p> <?php __( "You are not authorized to perform this operation.", 'lbrypress' ); ?> </p>
<?php   
 }