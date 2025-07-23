<?php
$isFull = $args['full'] ?? false;
$masters = getPosts('master');
$usedTermsArray = [];
if (!empty($masters)) {
  foreach ($masters as $master) {
    if (!get_field('is_bookable', $master->ID)) {
      continue;
    }
    $images = get_field('master_images_work', $master->ID);
    if (is_array($images) && !empty($images)) {
      foreach ($images as $image) {
        $tag_ids = $image['master_image_work_tag'] ?? [];
        if (is_array($tag_ids)) {
          foreach ($tag_ids as $term_id) {
            if (!isset($usedTermsArray[$term_id])) {
              $term = get_term($term_id, 'gallery_tag');
              if ($term && !is_wp_error($term)) {
                $usedTermsArray[$term_id] = $term;
              }
            }
          }
        }
      }
    }
  }
  $usedTermsArray = array_values($usedTermsArray);
}
?>
<div class="gallery-modal">
  <button type="button" aria-label="Close" class="cross"></button>
  <div class="swiper gallery-swiper button-container black">
    <div class="swiper-wrapper">
      <?php
      $index = 0;
      foreach ($masters as $master) {
        if (!get_field('is_bookable', $master->ID)) {
          continue;
        }
        $images = get_field('master_images_work', $master->ID);
        if ($images && is_array($images) && !empty($images)) {
          foreach ($images as $image) {
            get_template_part('template-parts/gallery/gallery-slide', null, [
              'index' => $index,
              'master' => $master,
              'image' => $image,
            ]);
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
<section class="<?= $isFull ? 'gallery-section full' : 'gallery-section' ?>">
  <div class="container">
    <div class="gallery-section__top">
      <?php
      $title = get_field('gallery_title', 'option');
      if ($isFull) {
        echo "<h1 class='title'>$title</h1>";
      } else {
        echo "<h2 class='title'>$title</h2>";
      }
      ?>
      <p class="paragraph"><?php the_field('gallery_text', 'option'); ?></p>
    </div>
    <div class="gallery-section__filters">
      <button type='button' data-slug='all' class='filter active'>All</button>
      <?php foreach ($usedTermsArray as $term) {
        $slug = $term->slug;
        $name = $term->name;
        echo "<button type='button' data-slug='$slug' class='filter'>$name</button>";
      } ?>
    </div>
    <div class="gallery-section__images">
      <?php
      $index = 0;
      foreach ($masters as $master) {
        if (!get_field('is_bookable', $master->ID)) {
          continue;
        }
        $images = get_field('master_images_work', $master->ID);
        if ($images && is_array($images) && !empty($images)) {
          foreach ($images as $image) {
            get_template_part('template-parts/gallery/gallery-item', null, [
              'index' => $index,
              'master' => $master,
              'image' => $image,
            ]);
            $index++;
          }
        }
      }
      ?>
    </div>
    <?php
    $link = get_field('gallery_link_url', 'option');
    $url = $link['url'] ?? '';
    $target = $link['target'] ?? '_self';
    $title = $link['title'] ?? '';

    if (!$isFull && $url && $title) {
      echo "<a href='" .
        esc_url($url) .
        "' target='" .
        esc_attr($target) .
        "' class='btn yellow'>" .
        esc_html($title) .
        '</a>';
    }
    ?>

  </div>
</section>