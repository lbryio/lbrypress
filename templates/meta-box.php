<?php
/**
 * ============================
 * META BOX FOR POST PAGE
 * Prints the post meta box
 * @package LBRYPress
 * ============================
 */
defined('ABSPATH') || die(); // Exit if accessed directly

$unnatributed = (object) array(
    'name' => 'none (anonymous)',
    'claim_id' => 'null'
);
$post_id = $post->ID;
// Generate a custom nonce
$lbrynonce = wp_create_nonce( 'lbry_publish_post_nonce' );

$lbry_published = get_post_meta( $post_id, '_lbry_is_published', true );
$lbry_claim_id = get_post_meta( $post_id, '_lbry_claim_id', true );
$lbry_published_channel = get_post_meta( $post_id, '_lbry_post_published_channel', true );
if ( ( $lbry_published == true ) && ( $lbry_claim_id ) && ( ! ( $lbry_published_channel ) ) ) {
    $result = LBRY()->daemon->claim_search( $lbry_claim_id );
    $name =  $result->items[0]->signing_channel->name;
    update_post_meta( $post_id, '_lbry_post_published_channel', $name );
}
$lbry_channel_claim_id = get_post_meta( $post_id, '_lbry_post_pub_channel', true );
$lbry_published_license = get_post_meta( $post_id, '_lbry_post_pub_license', true );
if ( ( $lbry_published == true ) && ( ( $lbry_claim_id ) ) && ( ! ( $lbry_published_license ) ) ) {
    $result = LBRY()->daemon->claim_search( $lbry_claim_id );
    $license = $result->items[0]->value->license;
    update_post_meta( $post_id, '_lbry_post_pub_license', $license );
}
$lbry_canonical_url = get_post_meta( $post_id, '_lbry_canonical_url', true );
$lbry_url = ( ($lbry_canonical_url) ? $lbry_canonical_url : 'lbry://' . $lbry_published_channel . '#' . $lbry_claim_id );
$cur_channel = ( get_post_meta( $post_id, LBRY_POST_PUB_CHANNEL, true ) ? get_post_meta( $post_id, LBRY_POST_PUB_CHANNEL, true ) : get_post_meta( $post_id, '_lbry_channel', true ) );
$default_channel = get_option( LBRY_SETTINGS )['default_lbry_channel'];
$chan_open_url = ( 'open.lbry.com/'. $lbry_published_channel .'#' . $lbry_channel_claim_id . '');

?>
<input type="hidden" id="_lbrynonce" name="_lbrynonce" value="<?php echo $lbrynonce ?>"><?php 

    if ( ( ( $will_publish == true ) && ( $lbry_channel_claim_id ) ) || ( ( ( $lbry_published == true ) || ( $lbry_claim_id ) || ( $lbry_published_channel ) ) && ( $lbry_published_license != null ) ) ) { 

        $results = LBRY()->daemon->claim_search( $lbry_claim_id );
        $init_bid = $results->items[0]->amount;
        $support_amount = $results->items[0]->meta->support_amount;
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
        printf(
            '<div class="lbry-meta-label lbry-meta-bx-channel"><strong>' . __( 'Initial bid amount:', 'lbrypress' ) . ' </strong>
            <span class="lbry-meta-bx-content lbry-meta-bx-channel"><img src="' . esc_url( plugin_dir_url( LBRY_PLUGIN_FILE ) ) . 'admin/images/lbc.png" class="icon icon-lbc bid-icon-lbc bid-icon-lbc"> ' . esc_html__( '%1$s', 'lbrypress' ) . '</span></div>',
            $init_bid
        );
        printf(
            '<div class="lbry-meta-label lbry-meta-bx-channel"><strong>' . __( 'Supports:', 'lbrypress' ) . ' </strong>
            <span class="lbry-meta-bx-content lbry-meta-bx-channel"><img src="' . esc_url( plugin_dir_url( LBRY_PLUGIN_FILE ) ) . 'admin/images/lbc.png" class="icon icon-lbc bid-icon-lbc bid-icon-lbc"> ' . esc_html__( '%1$s', 'lbrypress' ) . '</span><a href="' . admin_url( add_query_arg( array( 'page' => 'lbrypress', 'tab' => 'supports', 'post_id' => $post_id, 'claim_id' => '%2$s', 'lbry_url' => '%3$s', 'supporting_channel' => '%4$s', 'current_support' => '%5$.3f', 'init_bid' => '%6$.3f' ), 'admin.php' ) ) . '">' . __( 'Add', 'lbrypress' ) . '</a></div>',
            $support_amount,
            $lbry_claim_id,
            urlencode($lbry_url),
            $lbry_published_channel,
            $support_amount,
            $init_bid
        );
        printf(
            '<div class="lbry-meta-label lbry-meta-bx-channel"><strong>' . __( 'LBRY channel published to:', 'lbrypress' ) . '</strong></div>
            <div class="lbry-meta-bx-content lbry-meta-bx-channel"><a href="' . esc_url( '%1$s', 'lbrypress' ) . '">' . esc_html__( '%2$s', 'lbrypress' ) . '</a></div>',
            $chan_open_url,
            $lbry_published_channel,
        );
        printf(
            '<div class="lbry-meta-label lbry-meta-bx-license"><strong>' . __( 'License published under:', 'lbrypress' ) .'</strong> </div>
            <div class="lbry-meta-bx-content lbry-meta-bx-license lbry-meta-bx-content-last">' . esc_html__( '%1$s', 'lbrypress' ) . '</div>',
            $lbry_published_license,
        );
    } else { 
        $channels = LBRY()->daemon->channel_list();
        $channels[] = $unnatributed;
        // Sort the channels in a natural way
        usort( $channels, array( 'LBRYPress', 'channel_name_comp' ) ); ?>
        
            <div><label for="LBRY_POST_PUB_CHANNEL" class="lbry-meta-bx-label lbry-meta-bx-channel"><?php 

            esc_html_e( 'Channel to Publish:', 'lbrypress' ); ?> </label></div><?php

            $options = '';
            if ( $channels ) {
                foreach ( $channels as $index=>$channel ) {   
                    $options .= '<option class="lbry-meta-bx-option lbry-meta-option-channel" value="' . esc_attr( $channel->claim_id ) . '"';
                        if ( ( $cur_channel ) ? $cur_channel : $cur_channel = $default_channel ) {
                            $options .= selected( $cur_channel, $channel->claim_id, false );
                }
                    $options .= '>' . esc_html__( $channel->name, 'lbrypress' ) . '</option>';
                            }
                    printf(
                        '<select id="' . esc_attr('%1$s') . '" name="' . esc_attr('%1$s') . '">' . esc_html('%2$s') . '</select>',
                        LBRY_POST_PUB_CHANNEL,
                        $options
                    );
            } 
            ?>
            <div><label for="LBRY_POST_PUB_LICENSE" class="lbry-meta-bx-label lbry-meta-bx-license"><?php esc_html_e( 'Publish License:', 'lbrypress' ); ?> </label></div><?php
            $licenses = LBRY()->licenses;
            $options = '';
            $default_license = get_option(LBRY_SETTINGS)[LBRY_LICENSE];
            $cur_license = get_post_meta( $post_id, LBRY_POST_PUB_LICENSE, true );
        
        // Create options list, select current license
        if ( $licenses ) {
            foreach ( $licenses as $value => $name ) {
                $options .= '<option class="lbry-meta-bx-option lbry-meta-bx-option-last lbry-meta-option-license" value="' . esc_attr( $value ) . '"';
                    if ( ( $cur_license ) ? $cur_license : $cur_license = $default_license ) {
                        $options .= selected( $cur_license, $value, false );
                    } 
                $options .= '>'. esc_html__( $name, 'lbrypress' ) . '</option>';
            }
        }
        printf(
            '<select class="" id="'.esc_attr('%1$s').'" name="'. esc_attr('%1$s') .'">' . esc_html('%2$s') . '</select>',
            LBRY_POST_PUB_LICENSE,
            $options
        ); 
    }