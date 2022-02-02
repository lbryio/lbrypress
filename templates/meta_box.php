<?php
defined('ABSPATH') || die(); // Exit if accessed directly

$unnatributed = (object) array(
    'name' => 'none (anonymous)',
    'claim_id' => 'null'
);
$channels = LBRY()->daemon->channel_list();
$channels[] = $unnatributed;
// Sort the channels in a natural way
usort($channels, array('LBRYPress', 'channel_name_comp'));
$cur_channel = get_post_meta($post->ID, LBRY_POST_CHANNEL, true);
$will_publish = get_post_meta($post->ID, LBRY_WILL_PUBLISH, true);
?>
<?php wp_nonce_field('lbry_publish_channels', '_lbrynonce'); ?>
<div class="lbry-meta-checkbox-wrapper">
    <label class="lbry-meta-label">
        <input type="checkbox" class="lbry-meta-checkbox" name="<?= LBRY_WILL_PUBLISH ?>" value="true"
        <?php
        if ($will_publish === 'true' || $will_publish === '') {
            echo 'checked';
        }
        ?>
        >
        Sync this post on channel:
    </label>
</div>
<select class="lbry-meta-select" name="<?= LBRY_POST_CHANNEL ?>">
     <?php foreach ($channels as $index=>$channel): ?>
         <option value="<?= $channel->claim_id ?>"
             <?php
                if ($cur_channel) {
                    if ($cur_channel === $channel->claim_id) {
                        echo 'selected';
                    }
                } elseif ($index === 0) {
                    echo 'selected';
                }
             ?>
             >
             <?= $channel->name ?>
         </option>
     <?php endforeach; ?>
</select>
