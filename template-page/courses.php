<?php
/**
 * Template Name: Courses
 */
get_header();
$courses = new WP_Query([
  'post_type' => 'course',
  'posts_per_page' => -1,
  'order' => 'ASC',
]);
$posts = $courses->posts;
$categories = get_terms(['taxonomy' => 'course_cat', 'hide_empty' => false]);
?>
<main>
  <?php foreach ($posts as $post) {
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
    $date = get_field('date', $id);
    $content = get_field('modal_content', $id);
    $is_discount = $discount && $discount > 0;
    $discount_type_symbol = $discount_type == 'percent' ? '%' : "$";
    $is_certificated = get_field('is_certificated', $id);
    $url_image = get_the_post_thumbnail_url($id, 'large');
    $certificate = '';
    $discount_html = '';
    if ($is_certificated) {
      $class = 'popup-details--certificated';
      if ($is_discount) {
        $class .= ' is_discount';
      }
      $certificate = "<span class='$class'>Certificate included</span>";
    }
    if ($is_discount) {
      $discount_html = "<span class='popup-details--discount'>-$discount$discount_type_symbol</span>";
    }
    $master_id = $master ? $master->ID : null;
    $master_level = max((int) get_field('master_level', $master_id), -1);
    $master_levelName = get_master_level_title($master_level, true);
    $master_starsCount = get_master_level_stars($master_level);
    $master_id_altegio = get_field('altegio_id', $master_id);
    $master_image = get_the_post_thumbnail_url($master_id, 'large') ?: '';
    $master_name = isset($master->post_title) ? $master->post_title : '';
    $days = get_field('duration_days', $id);
    $seats = get_field('seats_available', $id);
    $master_html = "<div data-id='$master_id' data-altegio-id='$master_id_altegio' class='popup-details__master'>";
    $master_html .= "<div class='popup-details__master--wrapper'>";
    $master_html .= "<img src='$master_image' class='popup-details__master--image' width='50' height='50' decoding='async' loading='eager' fetchpriority='high' alt='$master_name'>";
    $master_html .= "<span class='popup-details__master--name'>$master_name</span>";
    $master_html .=
      "<div class='popup-details--rate'>
            <div class='stars yellow'>
              " .
      str_repeat("<div class='star'></div>", $master_starsCount) .
      "
              <span>($master_levelName)</span>
            </div>
          </div>";
    $master_html .= '</div>';
    $master_html .= '</div>';
    $description = get_field('description', $id);
    $price_html = '';
    if ($new_price && $new_price > 0) {
      $price_html = "<span class='popup-details--old-price'>$$price</span>";
      $price_html .= "<span class='popup-details--price'>$$new_price</span>";
    } else {
      $price_html = "<span class='popup-details--price'>$price</span>";
    }
    $info = get_field('info', $id);
    echo "<div class='popup-details' data-id='$id'>
        <button type='button' aria-label='Close' class='popup-details__close'></button>
        <div class='popup-details__text'>
          <div class='popup-details--date'>$date</div>
          <h2 class='popup-details--title'>$title</h2>
          <div class='popup-details--content'>$content</div>
          <button type='button' class='btn yellow'>Submit application</button>
        </div>
        <div class='popup-details__card'>
          <div class='popup-details__card--header'>
            $certificate
            $discount_html
            <div class='popup-details__card--image'>
              <img loading='eager' fetchpriority='high' decoding='async' width='380' height='200' src='$url_image' alt='$title'>
            </div>
          </div>
          <div class='popup-details--days'>
            <span>Duration:</span>
            <b>$days</b>
          </div>
          <div class='popup-details--seats'>
            <span>Seats available:</span>
            <b>$seats</b>
          </div>
          <div class='popup-details--trainer'>
            <span class='popup-details--trainer-label'>Trainer:</span>
            $master_html
            <p class='popup-details--description'>$description</p>
            <div class='popup-details--bottom'>
              <div class='popup-details--price-wrapper'>
                <span class='popup-details--price-label'>Price:</span>
                $price_html
              </div>
              <span class='popup-details--info'>$info</span>
            </div>
          </div>
        </div>
      </div>";
    wp_reset_postdata();
  } ?>
  <section class="courses-section">
    <div class="container">
      <div class="courses-section__top">
        <h1 class="title"><?= get_field('courses_page_title') ?></h1>
        <p class="paragraph"><?= get_field('courses_page_text') ?></p>
      </div>
      <div class="courses-section__filters">
        <?php if ($categories && is_array($categories) && !empty($categories)) {
          echo "<button class='filter active' data-slug='all'>All</button>";
          foreach ($categories as $category) {
            $name = $category->name;
            $slug = $category->slug;
            echo "<button class='filter' data-slug='$slug'>$name</button>";
          }
        } ?>
      </div>
      <div class="courses-section__grid">
        <?php if ($posts && is_array($posts) && !empty($posts)) {
          foreach ($posts as $post) {
            get_template_part('template-parts/shared/course-card', null, ['post' => $post]);
            wp_reset_postdata();
          }
        } ?>
      </div>
    </div>
  </section>
</main>
<?php get_footer(); ?>
