<section class="promo-form-section">
  <?php
    $image_desktop = get_field('form_image', 'option');
    $url_desktop = isset($image_desktop["url"]) ? $image_desktop["url"] : null;
    $title_desktop = isset($image_desktop["title"]) ? $image_desktop["title"] : '';
    if ($url_desktop) {
      echo "<img class='promo-form-section__image' src='$url_desktop' alt='$title_desktop'>";
    };
  ?>
  <div class="container">
    <?php
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