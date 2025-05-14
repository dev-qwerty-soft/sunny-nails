<?php
  $items = $args["items"] ?? [];
?>
<section class="winners-section">
  <div class="container">
    <h2 class="title"><?= $args["title"]; ?></h2>
    <div class="winners-section__wrapper button-container black">
      <div class="swiper winners-swiper">
        <div class="swiper-wrapper">
          <div class="swiper-slide">
            <img src="<?= getUrl("images/image.png"); ?>" alt="image">
            <span>Ann P.</span>
          </div>
          <div class="swiper-slide">
            <img src="<?= getUrl("images/image.png"); ?>" alt="image">
            <span>Ann P.</span>
          </div>
          <div class="swiper-slide">
            <img src="<?= getUrl("images/image.png"); ?>" alt="image">
            <span>Ann P.</span>
          </div>
        </div>
      </div>
      <button type="button" aria-label="Next slide" class="button swiper-button-next"></button>
      <button type="button" aria-label="Previous slide" class="button swiper-button-prev"></button>
    </div>
  </div>
</section>