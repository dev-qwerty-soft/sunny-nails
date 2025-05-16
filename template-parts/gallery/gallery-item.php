<?php
  $index = $args["index"] ?? 0;
  $image = $args["image"] ?? [];
  $tag = $image["master_image_work_tag"];
  $tagName = isset($tag->name) ? $tag->name : "";
  $slug = isset($tag->slug) ? $tag->slug : "";
  $img = $image["master_image_work"];
  $url = isset($img['url']) ? $img['url'] : "";
  $master = $args["master"] ?? "";
  $level = (int) get_field('master_level', $master->ID);
  $name = isset($master->post_title) ? $master->post_title : '';
  $services = get_services_by_category($tag->term_id);
  $post_id = isset($services[0]->ID) ? $services[0]->ID : null;
  if($post_id) {
    $price = get_post_meta($post_id, 'price_min', true);
    $currency = get_post_meta($post_id, 'currency', true) ?: 'SGD';
  };
?>
<div data-index='<?= $index; ?>' data-slug='<?= $slug; ?>' class='image active'>
  <div class='image__front'>
    <img src='<?= $url; ?>' alt='<?= $tagName; ?>'>
    <div class='wrapper'>
      <button type='button' aria-label='View' class='view'></button>
      <a href='#' class='btn white'>I want this</a>
    </div>
  </div>
  <div class='image__back'>
    <span class='image__title'><?= $tagName; ?></span>
    <span class='image__price'>Price: <?= "$price $currency"; ?></span>
    <span class='image__master'><?= $name; ?></span>
    <div class='stars'>
      <?php
        if(!is_array($level)) {
          echo str_repeat("<div class='star'></div>", $level);
        };
      ?>
      <span>(Sunny Inferno)</span>
    </div>
    <div class='wrapper'>
      <a href='#' class='btn white'>I want this</a>
    </div>
  </div>
</div>