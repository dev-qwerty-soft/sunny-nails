<section class="promo-form-section">
  <div class="container">
    <?php
      $image = get_field('form_image', 'option');
      $url = isset($image["url"]) ? $image["url"] : null;
      $title = isset($image["title"]) ? $image["title"] : null;
      if($url && $title) {
        echo "<img src='$url' alt='$title'>";
      }

      $title = get_field('form_title', 'option');
      if($title) {
        echo "<h2 class='title'>$title</h2>";
      };
    ?>
    <div class="paragraph">
      <?php the_field('form_text', 'option'); ?>
    </div>
    <?php 
      $code = get_field('form_code', 'option');
      if($code) {
        echo do_shortcode($code);
      };
    ?>
  </div>
</section>