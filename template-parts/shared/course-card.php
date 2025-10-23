<?php
$post = isset($args['post']) ? $args['post'] : null;
if (!$post) {
  return;
}
$id = isset($post->ID) ? $post->ID : null;
$title = isset($post->post_title) ? $post->post_title : '';
$discount = get_field('discount', $id) ? intval(get_field('discount', $id)) : 0;
$discount_type = get_field('discount_type', $id);
$price = get_field('price', $id) ? intval(get_field('price', $id)) : 0;
$new_price = null;
if ($discount_type == 'percent' && $discount > 0) {
  $new_price = $price - ($price / 100) * $discount;
} elseif ($discount_type == 'fixed' && $discount > 0) {
  $new_price = $price - $discount;
}
$categories = get_the_terms($id, 'course_cat');
$categories_slugs = [];
if ($categories && is_array($categories) && !empty($categories)) {
  foreach ($categories as $category) {
    $categories_slugs[] = $category->slug;
  }
}
$master = get_field('related_master', $id);
?>
<div class="course-card active"
  data-id="<?= $id ?>"
  data-course-title="<?= esc_attr($title) ?>"
  data-course-price="<?= $new_price && $new_price > 0 ? $new_price : $price ?>"
  data-categories="<?= implode(' ', $categories_slugs) ?>">
  <div class="course-card__header">
    <?php
    $date = get_field('date', $id);
    $is_discount = $discount && $discount > 0;
    $discount_type_symbol = $discount_type == 'percent' ? '%' : "$";
    $is_certificated = get_field('is_certificated', $id);
    $url_image = get_the_post_thumbnail_url($id, 'large');
    if ($is_certificated) {
      $class = 'course-card--certificated';
      if ($is_discount) {
        $class .= ' is_discount';
      }
      echo "<span class='$class'>Certificate included</span>";
    }
    if ($date) {
      echo "<span class='course-card--date'>$date</span>";
    }
    if ($is_discount) {
      echo "<span class='course-card--discount'>-$discount$discount_type_symbol</span>";
    }
    if ($url_image) {
      echo "<div class='course-card--image'><img loading='eager' fetchpriority='high' decoding='async' width='380' height='200' src='$url_image' alt='$title'></div>";
    }
    ?>
  </div>
  <div class="course-card__text">
    <h3 class="course-card--title"><?= $title ?></h3>
    <p class="course-card--description"><?= get_field('description', $id) ?></p>
  </div>
  <?php if ($master) {
    $master_id = $master ? $master->ID : null;
    $master_level = max((int) get_field('master_level', $master_id), -1);
    $master_levelName = get_master_level_title($master_level, true);
    $master_starsCount = get_master_level_stars($master_level);
    $master_id_altegio = get_field('altegio_id', $master_id);
    $master_image = get_the_post_thumbnail_url($master_id, 'large') ?: '';
    $master_name = isset($master->post_title) ? $master->post_title : '';
    $days = get_field('duration_days', $id);
    $seats = get_field('seats_available', $id);
    echo "<div data-id='$master_id' data-altegio-id='$master_id_altegio' class='course-card__master'>";
    echo "<div class='course-card__master--wrapper'>";
    echo "<img src='$master_image' class='course-card__master--image' width='50' height='50' decoding='async' loading='eager' fetchpriority='high' alt='$master_name'>";
    echo "<span class='course-card__master--name'>$master_name</span>";
    echo "<div class='course-card--rate'>
        <div class='stars yellow'>
          " .
      str_repeat("<div class='star'></div>", $master_starsCount) .
      "
          <span>($master_levelName)</span>
        </div>
      </div>";
    echo '</div>';
    echo "<div class='course-card__master--info'>";
    echo "<span class='course-card--days'>$days</span>";
    echo "<span class='course-card--seats'>$seats</span>";
    echo '</div>';
    echo '</div>';
  } ?>
  <div class="course-card__footer">
    <?php 
      $info = get_field('info', $id);
      if($info) {
        echo "<span class='course-card--info'>$info</span>";
      } else {
        echo "<span class='course-card--price-text'>Price:</span>";
      }
    if ($new_price && $new_price > 0) {
      echo "<span class='course-card--old-price'>$$price</span>";
      echo "<span class='course-card--price'>$$new_price</span>";
    } else {
      echo "<span class='course-card--price'>$price</span>";
    } ?>
  </div>
  <div class="course-card--button">
    <button type="button" class="btn white open-popup-details">View Details</button>
    <button type="button" class="btn yellow">Submit application</button>
  </div>
</div>