<?php
  $slugs = ["all", "nude", "french", "bright", "pedicure"];
  $isFull = $args["full"] ?? false;
?>
<div class="gallery-modal">
  <button type="button" aria-label="Close" class="cross"></button>
  <div class="swiper gallery-swiper button-container black">
    <div class="swiper-wrapper">
      <?php
        $index = 0;
        foreach ($slugs as $slug) {
          if($slug !== "all") {
            for($i = 0; $i < 10; $i++) {
              $title = "File Manicure + Wraps + Thin Gel Polish Layer";
              $price = "Price: 75 SGD";
              $master = "Master: Ann Ivanova";
              $image = getUrl("images/image.png");
              $wants = "I want this";
              
              echo "<div data-index='$index' data-slug='$slug' class='swiper-slide image'>
                <img src='$image' alt='$title'>
                <span class='image__title'>$title</span>
                <span class='image__price'>$price</span>
                <span class='image__master'>$master</span>
                <div class='stars'>
                  <div class='star'></div>
                  <div class='star'></div>
                  <div class='star'></div>
                  <span>(Sunny Inferno)</span>
                </div>
                <a href='#' class='btn white'>$wants</a>
              </div>";
              $index++;
            }
          }
        }
      ?>
    </div>
    <button type="button" aria-label="Next slide" class="button swiper-button-next"></button>
    <button type="button" aria-label="Previous slide" class="button swiper-button-prev"></button>
  </div>
</div>
<section class="<?= $isFull ? "gallery-section full" : "gallery-section"; ?>">
  <div class="container">
    <div class="gallery-section__top">
      <h2 class="title"><?php the_field('gallery_title', 'option'); ?></h2>
      <p class="paragraph"><?php the_field('gallery_text', 'option'); ?></p>
    </div>
    <div class="gallery-section__filters">
      <?php
        foreach ($slugs as $slug) {
          echo "<button type='button' data-slug='$slug' class='filter'>$slug</button>";
        }
      ?>
    </div>
    <div class="gallery-section__images">
        <?php
          $index = 0;
          foreach ($slugs as $slug) {
            if($slug !== "all") {
              for($i = 0; $i < 10; $i++) {
                $title = "File Manicure + Wraps + Thin Gel Polish Layer";
                $price = "Price: 75 SGD";
                $master = "Master: Ann Ivanova";
                $image = getUrl("images/image.png");
                $wants = "I want this";
                
                echo "<div data-index='$index' data-slug='$slug' class='image active'>
                  <div class='image__front'>
                    <img src='$image' alt='$title'>
                    <div class='wrapper'>
                      <button type='button' aria-label='View' class='view'></button>
                      <a href='#' class='btn white'>$wants</a>
                    </div>
                  </div>
                  <div class='image__back'>
                    <span class='image__title'>$title</span>
                    <span class='image__price'>$price</span>
                    <span class='image__master'>$master</span>
                    <div class='stars'>
                      <div class='star'></div>
                      <div class='star'></div>
                      <div class='star'></div>
                      <span>(Sunny Inferno)</span>
                    </div>
                    <div class='wrapper'>
                      <a href='#' class='btn white'>$wants</a>
                    </div>
                  </div>
                </div>";
                $index++;
              }
            }
          }
        ?>
    </div>
    <?php
      $link = get_field('gallery_link_url', "option");
      $text_btn = get_field('gallery_link_text', "option");
      if(!$isFull && $link && $text_btn) {
        echo "<a href='$link' class='btn yellow'>$text_btn</a>";
      };
    ?>
  </div>
</section>