<?php
  $isPage = $args["page"] ?? false;
  $array = getPosts("master");
?>

<section class='team-section<?= $isPage ? ' page' : '' ?>'>
  <div class='container'>
    <div class='team-section__top'>
      <h2 class='title'><?php the_field('team_title', 'option'); ?></h2>
      <p class='paragraph'><?php the_field('team_description', 'option'); ?></p>
      <?php
        $link = get_field('team_link_url', 'option');
        $text = get_field('team_link_text', 'option');
        if ($link && $text) {
          echo "<a href='$link' class='btn yellow'>$text</a>";
        }
      ?>
    </div>
    <?php if ($isPage): ?>
      <div class='team-section__grid'>
        <?php foreach ($array as $post): ?>
          <div class='team-card' data-altegio-id='<?= get_field('altegio_id', $post->ID); ?>'>
            <img class='team-card__image' src='<?= get_the_post_thumbnail_url($post->ID); ?>' alt='images'>
            <div class='team-card__text'>
              <span class='team-card__name'><?= isset($post->post_title) ? $post->post_title : ''; ?></span>
              <?php
                $link = get_field('instagram_url', $post->ID);
                if($link) {
                  echo "<a href='$link' aria-label='Instagram' target='_blank' class='team-card__instagram'></a>";
                };
              ?>
              <div class='team-card__rate'>
                <div class='stars yellow'>
                  <?php
                    $num = get_field('master_level', $post->ID);
                    // dump($num);
                    // echo str_repeat("<div class='star'></div>", $num);
                  ?>
                  <span>(Sunny Inferno)</span>
                </div>
              </div>
            </div>
            <div class='swiper mini-swiper'>
              <div class='swiper-wrapper'>
                <?php 
                  $images = get_field('master_images_work', $post->ID);
                  if(!empty($images)) {
                    foreach ($images as $item) {
                      $image = $item['master_image_work'];
                      $url = isset($image['url']) ? $image['url'] : null;
                      $title = isset($image['title']) ? $image['title'] : null;
                      echo "<div class='swiper-slide'>
                        <img src='$url' alt='$title'>
                      </div>";
                    };
                  };
                ?>
              </div>
              <div class='swiper-scrollbar'></div>
            </div>
            <div class='team-card__buttons'>
              <button class='btn yellow'>Book an Appointment</button>
              <button class='btn'>Learn More</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <div class='team-section__wrapper button-container black'>
        <div class='swiper team-swiper'>
          <div class='swiper-wrapper'>
            <?php foreach ($array as $post): ?>
              <div class='swiper-slide'>
                <div class='team-card'>
                  <img class='team-card__image' src='<?= $img ?>' alt='images'>
                  <div class='team-card__text'>
                    <span class='team-card__name'>Ann Ivanova</span>
                    <a href='#' aria-label='Instagram' target='_blank' class='team-card__instagram'></a>
                    <div class='team-card__rate'>
                      <div class='stars yellow'>
                        <div class='star'></div>
                        <div class='star'></div>
                        <div class='star'></div>
                        <span>(Sunny Inferno)</span>
                      </div>
                    </div>
                  </div>
                  <div class='swiper mini-swiper'>
                    <div class='swiper-wrapper'>
                      <?php for ($j = 0; $j < 12; $j++): ?>
                        <div class='swiper-slide active'>
                          <img src='<?= $img ?>' alt='images'>
                        </div>
                      <?php endfor; ?>
                    </div>
                    <div class='swiper-scrollbar'></div>
                  </div>
                  <div class='team-card__buttons'>
                    <button class='btn yellow'>Book an Appointment</button>
                    <button class='btn'>Learn More</button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <button type='button' aria-label='Next slide' class='button swiper-button-next'></button>
        <button type='button' aria-label='Previous slide' class='button swiper-button-prev'></button>
      </div>
    <?php endif; ?>
  </div>
</section>
