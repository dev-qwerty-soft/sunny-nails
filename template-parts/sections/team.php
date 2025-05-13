<?php
  $isPage = $args["page"] ?? false;
  $img = getUrl('images/image-1.png');
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
        <?php for ($i = 0; $i < 9; $i++): ?>
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
        <?php endfor; ?>
      </div>

    <?php else: ?>
      <div class='team-section__wrapper button-container black'>
        <div class='swiper team-swiper'>
          <div class='swiper-wrapper'>
            <?php for ($i = 0; $i < 9; $i++): ?>
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
            <?php endfor; ?>
          </div>
        </div>
        <button type='button' aria-label='Next slide' class='button swiper-button-next'></button>
        <button type='button' aria-label='Previous slide' class='button swiper-button-prev'></button>
      </div>
    <?php endif; ?>
  </div>
</section>
