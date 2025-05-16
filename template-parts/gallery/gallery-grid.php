<?php
  $isFull = $args["full"] ?? false;
  $masters = getPosts("master");
  // $ordered_category_ids = [];
  // $service_categories = [];
  // if (empty($ordered_category_ids)) {
  //   $service_categories = get_terms([
  //       'taxonomy' => 'service_category',
  //       'hide_empty' => true,
  //       'order' => 'DESC'
  //   ]);
  // } else {
  //     $service_categories = [];
  //     foreach ($ordered_category_ids as $cat_id) {
  //         $term = get_term($cat_id, 'service_category');
  //         if (!is_wp_error($term) && !empty($term)) {
  //             $service_categories[] = $term;
  //         }
  //     }
  // };
  $usedTermsArray = [];
  if(!empty($masters)) {
    foreach ($masters as $master) {
      $images = get_field('master_images_work', $master->ID);
      if (!is_array($images)) continue;
      foreach ($images as $image) {
        $tag = $image["master_image_work_tag"] ?? null;
        if ($tag instanceof WP_Term && !isset($usedTerms[$tag->term_id])) {
          $usedTerms[$tag->term_id] = $tag;
        }
      }
    }
    $usedTermsArray = array_values($usedTerms);
  }
?>
<div class="gallery-modal">
  <button type="button" aria-label="Close" class="cross"></button>
  <div class="swiper gallery-swiper button-container black">
    <div class="swiper-wrapper">
      <?php
        $index = 0;
        foreach($masters as $master) {
          $images = get_field('master_images_work', $master->ID);
          foreach($images as $image) {
            get_template_part("template-parts/gallery/gallery-slide", null, [
              "index" => $index,
              "master" => $master,
              "image" => $image
            ]);
            $index++;
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
      <button type='button' data-slug='all' class='filter'>All</button>
      <?php
        foreach ($usedTermsArray as $term) {
          $slug = $term->slug;
          $name = $term->name;
          echo "<button type='button' data-slug='$slug' class='filter'>$name</button>";
        }
      ?>
    </div>
    <div class="gallery-section__images">
      <?php
        $index = 0;
        foreach($masters as $master) {
          $images = get_field('master_images_work', $master->ID);
          foreach($images as $image) {
            get_template_part("template-parts/gallery/gallery-item", null, [
              "index" => $index,
              "master" => $master,
              "image" => $image
            ]);
            $index++;
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