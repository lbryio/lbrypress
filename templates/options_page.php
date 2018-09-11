<?php
$LBRY = LBRY();
$wallet_balance = $LBRY->daemon->wallet_balance();
$speech_address = $LBRY->speech->get_address() || '';
?>
<div class="wrap">
    <h1><?= esc_html(get_admin_page_title()); ?></h1>

    <h2>Your wallet address:</h2>
    <code><?= get_option(LBRY_WALLET); ?></code>

    <h2>Your wallet amount:</h2>
    <code><?= number_format($wallet_balance, 6, '.', ','); ?></code>
    <form action="options.php" method="post">
        <label for="speech_address">
            <h2>Your Spee.ch server address to act as a cdn for assets:</h2>
            <p class="form-help">Learn more about spee.ch <a href="https://github.com/lbryio/spee.ch" target="_blank">here</a>.</p>
        </label>
        <input type="text" name="speech_address" placeholder="https://your-speech-address.com" value="<?= $speech_address ?>">
        <?php
        submit_button('Save Settings');
        ?>
    </form>
</div>
