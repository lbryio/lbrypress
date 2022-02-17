<?php
defined('ABSPATH') || die(); // Exit if accessed directly

    $lbry_url = get_post_meta( get_the_id(), LBRY_CANONICAL_URL, true );
    if ( ! $lbry_url ) {
        // Get channel canonical for backwards compatibility
        $channel_id = ( get_post_meta( get_the_id(), LBRY_POST_PUB_CHANNEL, true ) ? get_post_meta( get_the_id(), LBRY_POST_PUB_CHANNEL, true ) : get_post_meta( get_the_id(), '_lbry_channel', true ) );
        $lbry_url = LBRY()->daemon->canonical_url( $channel_id );
    }

    if ( $lbry_url ) {
        $open_url = str_replace( 'lbry://', 'open.lbry.com/', $lbry_url );
    }
?>
<div class="lbry-published-banner">
    <h5>Stored Safely on Blockchain</h5>
    <p>
        This post is published to the <a href="https://lbry.com/get">LBRY</a> blockchain
        <?php if( $lbry_url ) : ?>
            at: <a href="<?php echo esc_url( $open_url ); ?>"><?php esc_html_e( $lbry_url, 'lbrypress' ); ?></a>.
        <?php else: ?>
            .
        <?php endif; ?>
    </p>
    <p>
        <a href="https://lbry.com/get" target="_blank">Try LBRY</a> to experience content freedom, earn crypto, and support us directly!
    </p>
</div>
