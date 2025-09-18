<?php
function reviews_item() {
  $rating = number_format(getPlaceReviews()['rating'], 1);
  $stars = str_repeat("<div class='star'></div>", $rating);
  $link = get_field('reviews_link_url', 'option');
  return "<a target='_blank' rel='noopener noreferrer' href='$link' class='rating-new-item'>
        <span class='rating-new-item__title'>Average Referral Rating</span>
        <div class='rating-new-item__bottom'>
            <span class='rating-new-item__number'>$rating</span>
            <div class='rating-new-item__stars'>$stars</div>
        </div>
    </a>";
} ?>
<section class="hero-section">
  <div class="container">
      <div class="hero-section__content">
          <?php if (get_field('hero_big_title')): ?>
            <h1 class="hero-big-title"><?php the_field('hero_big_title'); ?></h1>
          <?php endif; ?>
          <div class="hero-section__main">
            <div class="hero-section__left">
              <div class="rating-desktop">
                <?php echo reviews_item(); ?>
              </div>
              <?php if (get_field('hero_title')): ?>
                <h2 class="hero-title"><?php the_field('hero_title'); ?></h2>
              <?php endif; ?>
              <div class="hero-section__buttons">
                <button type="button" class="btn white open-popup">Book an Appointment</button>
              </div>
            </div>
            <div class="hero-section__center">
                <?php
                $hero_image = get_field('hero_big_foto');
                if ($hero_image): ?>
                    <div class="hero-image girl">
                        <img src="<?php echo esc_url(
                          $hero_image['url'],
                        ); ?>" alt="<?php echo esc_attr($hero_image['alt']); ?>">
                    </div>
                <?php endif;
                ?>
            </div>
            <div class="hero-section__right">
                <div class="rating-mobile">
                    <?php echo reviews_item(); ?>
                </div>
                <?php
                $hero_benefits = get_field('hero_benefits');
                if ($hero_benefits): ?>
                    <div class="hero-image benefits">
                        <img src="<?php echo esc_url(
                          $hero_benefits['url'],
                        ); ?>" alt="<?php echo esc_attr($hero_benefits['alt']); ?>">
                    </div>
                <?php endif;
                ?>
            </div>
          </div>
      </div>
  </div>
</section>