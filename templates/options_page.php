<?php
$LBRY = LBRY();
$wallet_balance = $LBRY->daemon->wallet_balance();
$channel_list = $LBRY->daemon->channel_list();
?>
<div class="wrap">
    <h1><?= esc_html(get_admin_page_title()); ?></h1>

    <h2>Your wallet amount:</h2>
    <code><?= number_format($wallet_balance, 6, '.', ','); ?></code>

    <form action="options.php" method="post">
        <?php
        settings_fields(LBRY_SETTINGS_GROUP);
        do_settings_sections(LBRY_ADMIN_PAGE);
        submit_button('Save Settings');
        ?>
    </form>

    <h2>Your Publishable Channels</h2>
    <?php if ($channel_list): ?>

    <?php else: ?>
        <p>Looks like you haven't added any channels yet, feel free to do so below:</p>
    <?php endif; ?>

    <h2>Add a new channel to publish to:</h2>
    <form action="" method="post">
        <input type="text" name="new_channel" value="" placeholder="Your New Channel">
        <?php submit_button('Add New Channel'); ?>
    </form>
</div>
