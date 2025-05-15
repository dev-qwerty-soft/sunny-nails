<section class="winners-section">
  <div class="container">
    <h2 class="title"><?= $args["title"]; ?></h2>
    <div class="winners-section__wrapper button-container black">
      <div class="swiper winners-swiper">
        <div class="swiper-wrapper">
          <?php
            foreach ($args["items"] ?? [] as $item) {
              $name = $item["winner_name"];
              $image = $item["winner_image"];
              $url = $image["url"];
              $title = $image["title"];
              echo "<div class='swiper-slide'>
                <img src='$url' alt='$name-$title'>
                <span>$name</span>
              </div>";
            };
          ?>
        </div>
      </div>
      <button type="button" aria-label="Next slide" class="button swiper-button-next"></button>
      <button type="button" aria-label="Previous slide" class="button swiper-button-prev"></button>
    </div>
  </div>
</section>