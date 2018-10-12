<?php
$LBRY = LBRY();
$unnatributed = (object) array(
    'name' => 'Unattributed',
    'permanent_url' => 'unattributed'
);
$channels = LBRY()->daemon->channel_list();
array_unshift($channels, $unnatributed);
?>
<h4>Choose which channels you would like to publish this post to:</h4>
<ul class="categorychecklist">
    <?php if ($channels): ?>
      <?php foreach ($channels as $channel): ?>
          <li>
              <label class="selectit">
                  <input type="checkbox" name="channels[]" value="<?= $channel->permanent_url ?>">
                  <?= $channel->name ?>
              </label>
              <br />
          </li>
      <?php endforeach; ?>
    <?php endif; ?>
</ul>
