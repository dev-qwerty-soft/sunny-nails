<?php
  $slugs = ["all", "nude", "french", "bright", "pedicure"];
  $isFull = $args["full"] ?? false;
  $class = "gallery-section";
  if($isFull) {
    $class .= " full";
  }
?>

<section class="<?= $class; ?>">
  <div class="container">
    <div class="gallery-section__top">
      <h2 class="title">Gallery</h2>
      <p class="paragraph">
        Discover the art of beautiful nails. Browse our gallery to see the stunning results our team creates for clients every day.
      </p>
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
          foreach ($slugs as $slug) {
            if($slug !== "all") {
              for($i = 0; $i < 10; $i++) {
                $title = "File Manicure + Wraps + Thin Gel Polish Layer";
                $price = "Price: 75 SGD";
                $master = "Master: Ann Ivanova";
                $image = getUrl("images/image.png");
                $wants = "I want this";
                
                echo "<div data-slug='$slug' class='image active'>
                  <div class='image__front'>
                    <img src='$image' alt='$title'>
                    <span class='label'>$wants</span>
                  </div>
                  <div class='image__back'>
                    <span class='image__title'>$title</span>
                    <span class='image__price'>$price</span>
                    <span class='image__master'>$master</span>
                    <div class='image__stars'>
                      <div class='star'></div>
                      <div class='star'></div>
                      <div class='star'></div>
                      <span>(Sunny Inferno)</span>
                    </div>
                    <span class='label'>$wants</span>
                  </div>
                </div>";
              }
            }
          }
        ?>
    </div>
    <?php
      if(!$isFull) {
        echo "<a href='/gallery' class='btn yellow'>Show all</a>";
      }
    ?>
  </div>
</section>