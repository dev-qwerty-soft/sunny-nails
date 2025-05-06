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
          Singapore’s Favorite <br>
          Russian Manicure Studio
        </h1>
        <div class="hero-section__buttons">
          <a href="#" class="btn yellow">Free Manicure</a>
          <a href="#" class="btn">Free Manicure</a>
        </div>
      </div>
      <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
          <div class="swiper-slide">
            <img src="https://plus.unsplash.com/premium_photo-1713200811001-af93d0dcdfc2?fm=jpg&q=60&w=3000&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="">
          </div>
          <div class="swiper-slide">
            <img src="https://plus.unsplash.com/premium_photo-1713200811001-af93d0dcdfc2?fm=jpg&q=60&w=3000&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="">
          </div>
          <div class="swiper-slide">
            <img src="https://plus.unsplash.com/premium_photo-1713200811001-af93d0dcdfc2?fm=jpg&q=60&w=3000&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="">
          </div>
        </div>
        <div class="swiper-pagination"></div>
        <button type="button" aria-label="Next slide" class="button swiper-button-next"></button>
        <button type="button" aria-label="Previous slide" class="button swiper-button-prev"></button>
      </div>
    </div>
  </div>
  
</main>
<?php get_footer(); ?>  