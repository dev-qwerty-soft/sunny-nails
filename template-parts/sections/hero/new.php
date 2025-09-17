<section class="hero-new-section">
  <?php
  $image_desktop = get_field('hero_new_image_desktop');
  $image_mobile = get_field('hero_new_image_phone');
  if ($image_desktop) {
    $url = isset($image_desktop['url']) ? $image_desktop['url'] : null;
    $title = isset($image_desktop['title']) ? $image_desktop['title'] : null;
    $w = isset($image_desktop['width']) ? $image_desktop['width'] : null;
    $h = isset($image_desktop['height']) ? $image_desktop['height'] : null;
    echo "<img class='hero-new-section__image hero-new-section__image--desktop' loading='eager' fetchpriority='high' decoding='async' width='$w' height='$h' src='$url' alt='$title'>";
  }
  if ($image_mobile) {
    $url = isset($image_mobile['url']) ? $image_mobile['url'] : null;
    $title = isset($image_mobile['title']) ? $image_mobile['title'] : null;
    $w = isset($image_mobile['width']) ? $image_mobile['width'] : null;
    $h = isset($image_mobile['height']) ? $image_mobile['height'] : null;
    echo "<img class='hero-new-section__image hero-new-section__image--mobile' loading='eager' fetchpriority='high' decoding='async' width='$w' height='$h' src='$url' alt='$title'>";
  }
  ?>
  <div class="container">
    <h1 class="hero-new-section__title"><?= get_field('hero_new_title') ?></h1>
    <div class="hero-new-section__box">
      <div class="hero-new-section__fire"></div>
      <div class="hero-new-section__span"><?= get_field('hero_new_text') ?></div>
      <div class="hero-new-section__buttons">
        <?php
        $buttons = get_field('hero_new_buttons');
        if ($buttons && is_array($buttons) && count($buttons) > 0) {
          foreach ($buttons as $buttonWrapper) {
            $button = $buttonWrapper['button'];
            $type = isset($buttonWrapper['type']) ? $buttonWrapper['type'] : null;
            $url = isset($button['url']) ? $button['url'] : null;
            $text = isset($button['title']) ? $button['title'] : null;
            $target = isset($button['target']) ? $button['target'] : null;
            echo "<a class='btn $type' href='$url' target='$target'>$text</a>";
          }
        }
        ?>
      </div>
    </div>
  </div>
</section>