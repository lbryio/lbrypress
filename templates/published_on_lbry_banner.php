<?php
defined('ABSPATH') || die(); // Exit if accessed directly

    $url = get_post_meta(get_the_id(), LBRY_CANONICAL_URL, true);
    if (!$url) {
        // Get channel canonical for backwards compatibility
        $channel_id = get_post_meta(get_the_id(), LBRY_POST_CHANNEL, true);
        $url = LBRY()->daemon->canonical_url($channel_id);
    }

    if ($url) {
        $url = str_replace('lbry://', 'open.lbry.com/', $url);
    }
?>
<div class="lbry-published-banner">
    <h5>Stored Safely on Blockchain</h5>
    <p>
        This post is published to <a href="https://lbry.com/get">LBRY</a> blockchain
        <?php if($url): ?>
            at <a href="https://<?= $url ?>"><?= $url ?></a>.
        <?php else: ?>
            .
        <?php endif; ?>
    </p>
    <p>
        <a href="https://lbry.com/get" target="_blank">Try LBRY</a> to experience content freedom, earn crypto, and support us directly!
    </p>
</div>
