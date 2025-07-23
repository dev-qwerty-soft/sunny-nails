<?php
/**
 * Template Name: Interior
 */

$slides = get_field('slider_section_images');
$slides_html = '';

if ($slides && is_array($slides) && !empty($slides)) {
  foreach ($slides as $slide) {
    $image = $slide['image'];
    $url = isset($image['url']) ? $image['url'] : null;
    $title = isset($image['title']) ? $image['title'] : null;
    $w = isset($image['width']) ? $image['width'] : null;
    $h = isset($image['height']) ? $image['height'] : null;
    if ($url && $title) {
      $slides_html .= "<div class='swiper-slide'>
        <img loading='lazy' width='$w' height='$h' src='$url' alt='$title'>
      </div>";
    }
  }
}

get_header();
?>
<main>
  <section class="interior-hero-section">
    <div class="container">
      <div class="interior-hero-section__content">
        <h1 class="title"><?php the_field('interior_hero_section_title'); ?></h1>
        <p class="paragraph"><?php the_field('interior_hero_section_text'); ?></p>
        <button type="button" class="btn yellow open-popup">
          Book an Appointment
        </button>
        <div class="mini-img">
          <?php
          $interior_image = get_field('interior_hero_section_mini_image');
          $url = isset($interior_image['url']) ? $interior_image['url'] : null;
          $title = isset($interior_image['title']) ? $interior_image['title'] : null;
          $w = isset($interior_image['width']) ? $interior_image['width'] : null;
          $h = isset($interior_image['height']) ? $interior_image['height'] : null;
          if ($url && $title) {
            echo "<img width='$w' height='$h' src='$url' alt='$title'>";
          }
          ?>
        </div>
      </div>
      <div class="interior-hero-section__image">
        <?php
        $interior_image = get_field('interior_hero_section_mini_image');
        $url = isset($interior_image['url']) ? $interior_image['url'] : null;
        $title = isset($interior_image['title']) ? $interior_image['title'] : null;
        $w = isset($interior_image['width']) ? $interior_image['width'] : null;
        $h = isset($interior_image['height']) ? $interior_image['height'] : null;
        if ($url && $title) {
          echo "<img width='$w' height='$h' src='$url' alt='$title'>";
        }
        ?>
      </div>
    </div>
  </section>
  <section class="interior-slider-section">
    <div class="container">
      <div class="interior-slider-section__slider">
        <div class="single-swiper-thumbs--arrows">
          <button type="button" aria-label="Previous slide" class="button prev"></button>
          <div class="swiper single-swiper-thumbs">
            <div class="swiper-wrapper"><?= $slides_html ?></div>
          </div>
          <button type="button" aria-label="Next slide" class="button next"></button>
        </div>
        <div class="swiper single-swiper">
          <div class="swiper-wrapper"><?= $slides_html ?></div>
        </div>
      </div>
      <div class="interior-slider-section__text">
        <h2 class="title"><?php the_field('slider_section_title'); ?></h2>
        <div class="paragraph"><?php the_field('slider_section_text'); ?></div>
      </div>
    </div>
  </section>
  <section class="interior-cta-section">
    <div class="container">
      <h2><?php the_field('interior_cta_section_title'); ?></h2>
      <button type="button" class="btn yellow open-popup">
        Book an Appointment
      </button>
    </div>
  </section>
</main>
<?php get_footer(); ?>
