<?php
/**
 * ============================
 * CHANNELS EDIT ADMIN PAGE
 * Uses the post-admin action so we can use the $_POST global variable to build our cURL request and the settings are not saved to the datbase
 * @package LBRYPress
 * ============================
 */
defined('ABSPATH') || die(); // Exit if accessed directly

if ( current_user_can( 'manage_options' ) ) {

    // Generate a custom nonce
    $lbrynonce = wp_create_nonce( 'edit_channel_nonce' );

    $claim_id = $_GET['claim_id'];
    $claim_id = sanitize_text_field( $claim_id );
    $lbry_url = $_GET['lbry_url'];
    $lbry_url = urldecode($lbry_url);
    $init_bid = $_GET['init_bid'];
    $init_bid = number_format( floatval( $init_bid ), 3, '.', '' );
    $channel = $_GET['channel_name'];
    $channel = sanitize_user( $channel );
    $support_amount = $_GET['current_support'];
    $support_amount = number_format( floatval( $support_amount ), 3, '.', '' );

    // Save attachment ID
	// if ( isset( $_POST['submit'] ) && isset( $_POST['lbry_header_attachment_id'] ) ) :
	// 	update_option( 'lbry_media_selector_header_id', absint( $_POST['lbry_header_attachment_id'] ) );
	// endif;
    if ( isset( $_POST['submit'] ) && isset( $_POST['lbry_thumbnail_attachment_id'] ) ) :
		update_option( 'lbry_media_selector_thumbnail_id', absint( $_POST['lbry_thumbnail_attachment_id'] ) );
	endif;

    // Build the page
    ?>
    <img src="">
    <img src="">
    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="lbry_edit_channel_form">	
            		
		<input type="hidden" name="action" value="lbry_edit_channel">
		<input type="hidden" name="_lbrynonce" value="<?php echo $lbrynonce ?>">
        <input type="hidden" name="claim_id" value ="<?php echo $claim_id ?>">
		<!-- <input type='hidden' name='lbry_header_attachment_id' id='lbry_header_attachment_id' value='<?php //echo get_option( 'lbry_media_selector_header_id' ); ?>'> -->
        <input type='hidden' name='lbry_thumbnail_attachment_id' id='lbry_thumbnail_attachment_id' value='<?php echo get_option( 'lbry_media_selector_thumbnail_id' ); ?>'>
        <?php if ( $claim_id ) { ?>
            <h2><?php echo _e( 'Editing Channel: ' . esc_html__( $channel ), 'lbrypress' ); ?></h2>
            <?php printf(
                            '<h3>' . esc_html__( '%1$s', 'lbrypress' ) . '</h3>
                            <h4>Claim ID: <code>' . esc_html__( '%2$s', 'lbrypress' ) . '</code></h4>',
                            $lbry_url,
                            $claim_id,
                        );
            } ?>
        <table class="form-table" role="presentation">
            <tbody>
                <!-- <tr>
                    <th scope="row">Header Image</th>
                        <td>
                            <div class='image-preview-wrapper'>
			                    <img id='header-preview' src='<?php //echo wp_get_attachment_url( get_option( 'lbry_media_selector_header_id' ) ); ?>' height='100'>
		                    </div>
		                        <input id="lbry_upload_header_button" type="button" class="button" value="<?php //_e( 'Upload Header', 'lbrypress' ); ?>">
                            <p class="header-image-info">6.25:1 ratio for best result</p>
                        <td>
                </tr> -->
                <tr>
                    <th scope="row">Thumbnail Image</th>
                        <td>
                            <div class='image-preview-wrapper'>
			                    <img id="thumbnail-preview" src="'<?php echo wp_get_attachment_url( get_option( 'lbry_media_selector_thumbnail_id' ) ); ?>'" height="100">
		                    </div>
		                        <input id="lbry_upload_thumbnail_button" type="button" class="button" value="<?php _e( 'Upload Thumbnail', 'lbrypress' ); ?>">
                            <p class="channel-image-info">1:1 ratio for best result</p>
                        <td>
                </tr>
                <?php if ( $channel ) { ?>
                <tr>
                    <th scope="row">Channel Name</th>
                        <td>
                            <?php printf(
                                '<input type="text" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('%2$s') . '" required readonly>',
                                'lbry_edit_channel_name',
                                $channel,
                            ); ?>
                            <p>If you want to edit another channel, use the link for the specific channel claim found on the <a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'lbrypress', 'tab' => 'channels' ),'options.php' ) ) ); ?>">Channels tab</a>or to create a complete <a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'lbrypress', 'tab' => 'channel-edit' ), 'admin.php' ) ) ); ?>">Channel</a></p>
                        </td>
                </tr>
                <?php } else { ?>
                <tr>
                    <th scope="row">Channel Name</th>
                    <td>
                        <?php printf(
                            '<input type="text" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="@' . esc_attr('') . '" required>',
                            'lbry_edit_channel_name',
                        ); ?>
                        <p>No spaces or special characters in @ Channel Name</p>
                    </td>
                </tr>
                <?php } ?>
                <tr>
                    <th scope="row">Title</th>
                        <td>
                            <?php printf(
                                '<input type="text" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('') . '">',
                                'lbry_edit_channel_title',
                            ); ?>
                            <p></p>
                        </td>
                </tr>
                <tr>
                    <th scope="row">Description</th>
                        <td>
                            <?php printf(
                                '<textarea rows="8" cols="24" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('') . '"></textarea>',
                                'lbry_edit_channel_description',
                            ); ?>
                            <p></p>
                        </td>
                </tr>
                <tr>
                    <th scope="row">Tags</th>
                        <td>
                            <?php printf(
                                '<input type="text" rows="10" cols="50" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('') . '">',
                                'lbry_edit_channel_tags',
                            ); ?>
                            <p>Add up to five tags (comma separated)</p>
                        </td>
                </tr>
                <tr>
                    <th scope="row">Website</th>
                        <td>
                            <?php printf(
                                '<input type="text" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('') . '">',
                                'lbry_new_channel_website',
                            ); ?>
                            <p>Default is LBRYPress site channel was created on</p>
                        </td>
                </tr>
                <tr>
                    <th scope="row">Email</th>
                        <td>
                            <?php printf(
                                '<input type="text" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('') . '">',
                                'lbry_new_channel_email',
                            ); ?>
                            <p>Default is WordPress admin email</p>
                        </td>
                </tr>
                <tr>
                    <th scope="row">Language</th>
                        <td>
                            <?php printf(
                                '<input type="text" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('') . '">',
                                'lbry_new_channel_prim_lang',
                            ); ?>
                            <p>Primary language of the channel</p>
                        </td>
                </tr>
                <tr>
                    <th scope="row">Second Language</th>
                        <td>
                            <?php printf(
                                '<input type="text" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('') . '">',
                                'lbry_new_channel_sec_lang',
                            ); ?>
                            <p>Secondary language channel uses (if any)</p>
                        </td>
                </tr>
                <?php if ( $channel ) { ?>
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
                <?php } else { ?>
                <tr>
                    <th scope="row">LBC to Bid</th>
                        <td>
                        <?php printf(
                                    '<input type="number" step="0.001" min="0.001" id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '" value="' . esc_attr('%2$.3f') . '" required>',
                                    'lbry_channel_add_bid_amount',
                                    $bid_amount,
                                ); ?>
                                <p>Current minimum bid <img src="<?php echo esc_url( plugin_dir_url( LBRY_PLUGIN_FILE ) . 'admin/images/lbc.png' ) ?>" class="icon icon-lbc bid-icon-lbc"> 0.001</p>
                        </td>
                </tr>
                    <?php } ?>
            </tbody>
        </table>                 
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Create New Channel"></p>
    </form>
    <?php
}