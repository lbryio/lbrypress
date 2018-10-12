<?php
$LBRY = LBRY();
$unnatributed = (object) array(
    'name' => 'Unattributed',
    'permanent_url' => 'unattributed'
);
$channels = LBRY()->daemon->channel_list();
array_unshift($channels, $unnatributed);
$cur_channels = get_post_meta($post->ID, 'lbry_channels');
?>
<?php wp_nonce_field('lbry_publish_channels', '_lbrynonce'); ?>
<h4>Choose which channels you would like to publish this post to:</h4>
<ul class="categorychecklist">
    <?php if ($channels): ?>
      <?php foreach ($channels as $channel): ?>
          <li>
              <label class="selectit">
                  <input type="checkbox" name="lbry_channels[]" value="<?= $channel->permanent_url ?>"
                  <?php if (in_array($channel->permanent_url, $cur_channels)): ?>
                      checked="true"
                  <?php endif; ?>
                  >
                  <?= $channel->name ?>
              </label>
              <br />
          </li>
      <?php endforeach; ?>
    <?php endif; ?>
</ul>
