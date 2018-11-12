<?php
// HACK: Fix this so its not TAM specific
    global $post;
    $slug = $post->post_name;
    $url = '@AntiMedia/' . $slug;
?>
<div class="lbry-published-banner">
    <h5>Stored Safely on Blockchain</h5>
    <p>
        This post is published to <a href="https://lbry.io/get">LBRY</a> blockchain at <a href="https://open.lbry.io/<?= $url ?>">lbry://<?= $url ?></a>.
    </p>
    <p>
        <a href="https://lbry.io/get" target="_blank">Try LBRY</a> to experience content freedom, earn crypto, and support The Anti-Media!
    </p>
</div>
