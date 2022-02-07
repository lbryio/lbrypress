<?php
defined('ABSPATH') || die(); // Exit if accessed directly

$unnatributed = (object) array(
    'name' => 'none (anonymous)',
    'claim_id' => 'null'
);
// Generate a custom nonce
$lbrynonce = wp_create_nonce( 'lbry_publish_post_nonce' );

$channels = LBRY()->daemon->channel_list();
$channels[] = $unnatributed;
$post_id = $post->ID;
$cur_channel = ( get_post_meta( $post_id, '_lbry_post_pub_channel', true ) ? get_post_meta( $post_id, '_lbry_post_pub_channel', true ) : get_post_meta( $post_id, '_lbry_channel', true ) );
$default_channel = get_option( LBRY_SETTINGS )['default_lbry_channel'];

// Sort the channels in a natural way
usort( $channels, array( 'LBRYPress', 'channel_name_comp' ) );
?>

<section>
<input type="hidden" id="_lbrynonce" name="_lbrynonce" value="<?php echo $lbrynonce ?>">
<div><label for="_lbry_post_pub_channel" class="lbry-meta-bx-label lbry-meta-bx-channel"><?php esc_html_e( 'Channel to Publish: ', 'lbrypress' ); ?></label></div><?php

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
                '_lbry_post_pub_channel',
                $options
            );
    } ?>
<div><label for="_lbry_post_pub_license" class="lbry-meta-bx-label lbry-meta-bx-license"><?php esc_html_e( 'Publish License: ', 'lbrypress' ); ?></label></div><?php
    $licenses = LBRY()->licenses;
    $options = '';
    $default_license = get_option(LBRY_SETTINGS)[LBRY_LICENSE];
    $cur_license = get_post_meta( $post_id, '_lbry_post_pub_license', true );
    
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
        '_lbry_post_pub_license',
        $options
    ); 
    ?>
</section>
