<?php
/**
 * Template Name: Home
 */
get_header();
// $services = AltegioClient::getServices();
// $staff = AltegioClient::getStaff();
?>
<main>
  <div class="hero-section">
    <div class="container">
      <div class="hero-section__top">
        <h1 class="title">
          <?php the_field('hero_title'); ?>
        </h1>
        <div class="hero-section__buttons">
          <a href="#" class="btn yellow">Free Manicure</a>
          <a href="#" class="btn">Free Manicure</a>
        </div>
      </div>
      <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
          <?php
            foreach (get_field('hero_slides') as $slide) {
              $img = $slide["url"];
              $title = $slide["title"];
              echo "<div class='swiper-slide'>
                <img src='$img' alt='$title'>
              </div>";
            }
          ?>
        </div>
        <div class="swiper-pagination"></div>
        <button type="button" aria-label="Next slide" class="button swiper-button-next"></button>
        <button type="button" aria-label="Previous slide" class="button swiper-button-prev"></button>
      </div>
    </div>
  </div>
  <div class="reasons-section">
    <div class="container">
      <h2 class="title">4 Reasons to Choose Sunny Nails</h2>
      <div class="reasons-section__items">
        <div class="item">
          <img src="<?= getUrl("images/icons.svg") ?>" alt="images">
          <span>High-quality nail work</span>
        </div>
        <div class="item">
          <img src="<?= getUrl("images/icons-1.svg") ?>" alt="images">
          <span>Exceptional hygiene</span>
        </div>
        <div class="item">
          <img src="<?= getUrl("images/icons-2.svg") ?>" alt="images">
          <span>Russian manicure techniques</span>
        </div>
        <div class="item">
          <img src="<?= getUrl("images/icons-3.svg") ?>" alt="images">
          <span>Outstanding service at accessible prices</span>
        </div>
      </div>
    </div>
  </div>
  <?php
    get_template_part("template-parts/sections/contact");
  ?>
</main>
<?php get_footer(); ?>  