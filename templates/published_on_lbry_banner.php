<?php
    $url = get_post_meta(get_the_id(), LBRY_PRETTY_URL, true);

    // Backwards compatible if LBRY_PRETTY_URL wasn't set
    if (!$url) {
        $url = get_post_meta(get_the_id(), LBRY_PERM_URL, true);
    }
?>
<div class="lbry-published-banner">
    <h5>Stored Safely on Blockchain</h5>
    <p>
        This post is published to <a href="https://lbry.io/get">LBRY</a> blockchain at <a href="<?= $url ?>"><?= $url ?></a>.
    </p>
    <p>
        <a href="https://lbry.io/get" target="_blank">Try LBRY</a> to experience content freedom, earn crypto, and support The Anti-Media!
    </p>
</div>
