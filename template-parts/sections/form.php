<section class="promo-form-section">
  <div class="container">
    <?php
      $image_desktop = get_field('form_image', 'option');
      $image_mobile = get_field('form_image_mobile', 'option');
      $url_desktop = isset($image_desktop["url"]) ? $image_desktop["url"] : null;
      $title_desktop = isset($image_desktop["title"]) ? $image_desktop["title"] : '';
      $url_mobile = isset($image_mobile["url"]) ? $image_mobile["url"] : null;
      if ($url_desktop || $url_mobile) {
        echo "<picture class='promo-form-section__image'>";
        if ($url_mobile) {
          echo "<source srcset='$url_mobile' media='(max-width: 767px)'>";
        }
        echo "<img src='$url_desktop' alt='$title_desktop'>";
        echo "</picture>";
      };
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