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

    // Build the page ?>
    <h3><?php _e( 'Add Supports to a Claim', 'lbrypress' ); ?></h3>

	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="lbry_supports_add_form">	
            		
		<input type="hidden" name="action" value="lbry_supports_add">
		<input type="hidden" name="_lbrynonce" value="<?php echo $lbrynonce ?>">

    </form>
<?php
}