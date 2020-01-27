<?php
$LBRY = LBRY();
$wallet_balance = $LBRY->daemon->wallet_balance();
$channel_list = $LBRY->daemon->channel_list();
// TODO: Make this page look cleaner
?>
<div class="wrap">
    <h1><?= esc_html(get_admin_page_title()); ?></h1>
    <h2>Your wallet amount:</h2>
    <code><?= number_format($wallet_balance, 2, '.', ','); ?></code>
    <form action="options.php" method="post">
        <?php
        settings_fields(LBRY_SETTINGS_GROUP);
        do_settings_sections(LBRY_ADMIN_PAGE);
        submit_button('Save Settings');
        ?>
    </form>
    <h2>Your Publishable Channels</h2>
    <?php if ($channel_list): ?>
        <?php error_log(print_r($channel_list, true)); ?>
        <ul class="lbry-channel-list">
            <?php foreach ($channel_list as $channel): ?>
                <li><?= $channel->name ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Looks like you haven't added any channels yet, feel free to do so below:</p>
    <?php endif; ?>
    <h2>Add a new channel to publish to:</h2>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <?php wp_nonce_field('lbry_add_channel', '_lbrynonce'); ?>
        <input type="hidden" name="action" value="lbry_add_channel">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">New Channel Name</th>
                    <td>
                        <span>@</span>
                        <input type="text" name="new_channel" value="" placeholder="your-new-channel" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Amount of LBC to Bid</th>
                    <td>
                        <input type="number" step="0.1" min="0.1" name="bid_amount" value="10" required>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button('Add New Channel'); ?>
    </form>
</div>
