<?php

/**
 * Template Name: Home
 */
get_header();

$ordered_category_ids = [];

if (function_exists('get_field')) {
  $ordered_category_ids = get_field('category_selection');
}

if (empty($ordered_category_ids)) {
  $service_categories = get_terms([
    'taxonomy' => 'service_category',
    'hide_empty' => true,
    'order' => 'DESC',
  ]);
} else {
  $service_categories = [];
  foreach ($ordered_category_ids as $cat_id) {
    $term = get_term($cat_id, 'service_category');
    if (!is_wp_error($term) && !empty($term)) {
      $service_categories[] = $term;
    }
  }
}
?>
<main>
  <?php get_template_part('template-parts/sections/hero/new'); ?>
  <?php $choose_section_active = get_field('choose_section_active'); ?>
  <?php if ($choose_section_active): ?>
    <section class=" reasons-section">
      <div class="container">
        <h2 class="title"><?php the_field('choose_title'); ?></h2>
        <div class="reasons-section__items">
          <?php foreach (get_field('choose_cards') as $card) {
            $text = $card['card_text'];
            $image = $card['card_image'];
            $url = $image['url'];
            $title = $image['title'];
            echo "<div class='item'>
              <img src='$url' alt='$title'>
              <span>$text</span>
            </div>";
          } ?>
        </div>
      </div>
    </section>
  <?php endif; ?>
  <?php get_template_part('template-parts/gallery/gallery-grid', null, [
    'full' => false,
  ]); ?>
  <?php
  $services_link = get_field('services_link_url', 'option');
  $services_link_url = $services_link['url'] ?? '';
  $services_link_target = $services_link['target'] ?? '_self';
  $services_link_title = $services_link['title'] ?? '';
  ?>
  <section class="services-preview-section">
    <div class="container">
      <div class="services-preview-section__top">
        <h2 class="title"><?php the_field('services_title', 'option'); ?></h2>
        <?php if ($services_link_url && $services_link_title): ?>
          <a href="<?= esc_url($services_link_url) ?>" target="<?= esc_attr(
  $services_link_target,
) ?>" class="btn yellow">
            <?= esc_html($services_link_title) ?>
          </a>
        <?php endif; ?>
      </div>
      <div class="services-preview-section__items">
        <div class="services-column services-column--left">
          <?php
          $index = 1;
          $left_categories = get_field('category_selection_left');

          if ($left_categories && is_array($left_categories)):
            foreach ($left_categories as $cat_id):
              $selected_category = get_term($cat_id, 'service_category');
              if ($selected_category && !is_wp_error($selected_category)) {
                $name = $selected_category->name;
                $clean_name = preg_replace('/\s*\([^)]*\)/', '', $name);
                $indexPretty = $index < 10 ? "0$index" : $index;

                echo "<a href='" .
                  esc_url($services_link_url) .
                  "' target='" .
                  esc_attr($services_link_target) .
                  "' class='item'>
                                        <span class='item__number'>/$indexPretty</span>
                                        <span class='item__title'>" .
                  esc_html($clean_name) .
                  "</span>
                                        <span class='item__arrow'></span>
                                    </a>";
                $index++;
              }
            endforeach;
          else:
            echo 'No data in category_selection_left';
          endif;
          ?>
        </div>

        <div class="services-column services-column--right">
          <?php
          $right_categories = get_field('category_selection_right');

          if ($right_categories && is_array($right_categories)):
            foreach ($right_categories as $cat_id):
              $selected_category = get_term($cat_id, 'service_category');
              if ($selected_category && !is_wp_error($selected_category)) {
                $name = $selected_category->name;
                $clean_name = preg_replace('/\s*\([^)]*\)/', '', $name);
                $indexPretty = $index < 10 ? "0$index" : $index;
                echo "<a href='" .
                  esc_url($services_link_url) .
                  "' target='" .
                  esc_attr($services_link_target) .
                  "' class='item'>
                                        <span class='item__number'>/$indexPretty</span>
                                        <span class='item__title'>" .
                  esc_html($clean_name) .
                  "</span>
                                        <span class='item__arrow'></span>
                                    </a>";
                $index++;
              }
            endforeach;
          else:
            echo "<p style='color: #999; font-style: italic; padding: 20px 0;'>Please select categories in ACF field 'category_selection_right' in admin panel</p>";
          endif;
          ?>
        </div>
      </div>
    </div>
  </section>
  <?php
  get_template_part('template-parts/sections/form');
  get_template_part('template-parts/sections/team');
  $interior_id_page = get_pages_by_template('template-page/interior.php')[0];
  ?>
  <section class="place-section">
    <div class="container">
      <div class="place-section__top">
        <h2 class="title"><?php the_field('slider_section_title', $interior_id_page); ?></h2>
        <p class="paragraph"><?php the_field('slider_section_small_text', $interior_id_page); ?></p>
        <a rel='noopener noreferrer' href='<?= esc_url(
          get_page_link($interior_id_page),
        ) ?>' class='btn yellow'>Show more</a>
      </div>
      <div class="place-section__images">
        <?php
        $slides = get_field('slider_section_images', $interior_id_page);
        $slides = array_slice($slides, 0, 4);
        if ($slides && is_array($slides) && !empty($slides)) {
          foreach ($slides as $slide) {
            $image = $slide['image'];
            $url = isset($image['url']) ? $image['url'] : null;
            $title = isset($image['title']) ? $image['title'] : null;
            $w = isset($image['width']) ? $image['width'] : null;
            $h = isset($image['height']) ? $image['height'] : null;
            if ($url && $title) {
              echo "<div class='image'>
                        <img loading='lazy' width='$w' height='$h' src='$url' alt='$title'>
                    </div>";
            }
          }
        }
        ?>
      </div>
    </div>
  </section>
  <section class="reviews-section">
    <div class="container">
      <div class="reviews-section__top">
        <h2 class="title">What Our Clients Say</h2>
        <?php
        $text = get_field('reviews_link_text', 'option');
        $link = get_field('reviews_link_url', 'option');
        if ($link && $text) {
          echo "<a target='_blank' rel='noopener noreferrer' href='$link' class='btn white'>$text</a>";
        }
        ?>
      </div>
      <div class="reviews-section__wrapper button-container">
        <div class="swiper reviews-swiper">
          <div class="swiper-wrapper">
            <?php
            $reviews = get_option('selected_google_reviews_data', []);
            foreach ($reviews as $slide) {
              $image = $slide['profile_photo_url'];
              $date = $slide['relative_time_description'];
              $name = $slide['author_name'];
              $text = $slide['text'];
              $rating = $slide['rating'];
              $stars = str_repeat("<div class='star'></div>", $rating);

              $char_limit = 150;
              $short_text = strlen($text) > $char_limit ? substr($text, 0, $char_limit) : $text;
              $needs_expand = strlen($text) > $char_limit;

              echo "<div class='swiper-slide'>
                                        <div class='review'>
                                            <div class='review__message'>
                                                <div class='review__rate'>
                                                    $stars
                                                </div>";

              if ($needs_expand) {
                echo "<div class='review__text-container'>
                                        <p class='review__text review__text--short'>" .
                  $short_text .
                  "...</p>
                                        <p class='review__text review__text--full' style='display: none;'>$text</p>
                                        <button class='review__expand-btn' type='button'>Read more</button>
                                    </div>";
              } else {
                echo "<div class='review__text-container'>
                                        <p class='review__text review__text--short'>$text</p>
                                        <button class='review__expand-btn' type='button' disabled style='opacity:0;pointer-events:none;'>Read more</button>
                                    </div>";
              }

              echo "</div>
                                        <div class='review__info'>
                                            <img src='$image' alt='$name'>
                                            <div class='review__author'>
                                                <span class='review__name'>$name</span>
                                                <span class='review__date'>$date</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>";
            }
            ?>
          </div>
        </div>
        <button type="button" aria-label="Next slide" class="button swiper-button-next"></button>
        <button type="button" aria-label="Previous slide" class="button swiper-button-prev"></button>
      </div>
    </div>
  </section>
  <?php get_template_part('template-parts/sections/contact'); ?>
</main>
<?php get_footer(); ?>
