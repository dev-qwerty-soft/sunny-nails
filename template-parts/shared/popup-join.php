<div id="popupJoin" class="popup-join">
  <button type="button" aria-label="Close" class="cross"></button>
  <?php
  $title = get_field('form_title', 'option');
  if ($title) {
    echo "<h2 class='title'>$title</h2>";
  }
  ?>
  <?php
  $code = get_field('form_code', 'option');
  if ($code) {
    echo do_shortcode($code);
  }
  ?>
</div>