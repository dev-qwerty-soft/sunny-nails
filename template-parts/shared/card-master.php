<?php
$post = $args['post'] ?? null;
$isPage = $args['isPage'] ?? false;
if (!$post) {
  return;
}

$level = max((int) get_field('master_level', $post->ID), -1);

// Use helper functions to get data from ACF fields
$levelName = get_master_level_title($level, true); // Include additional info
$starsCount = get_master_level_stars($level);

$id = get_field('altegio_id', $post->ID);
$image = get_the_post_thumbnail_url($post->ID, 'large') ?: '';
$images = get_field('master_images_work', $post->ID);
$name = isset($post->post_title) ? $post->post_title : '';
$instagram = get_field('instagram_url', $post->ID);
?>

<div data-altegio-id='<?= esc_attr($id) ?>' class='team-card'>
  <?php if ($image): ?>
    <img class='team-card__image' src='<?= esc_url($image) ?>' alt='<?= esc_attr($name) ?>'>
  <?php else: ?>
    <div class='team-card__image-placeholder'>No Image</div>
  <?php endif; ?>
  <div class='team-card__text'>
    <span class='team-card__name'><?= esc_html($name) ?></span>
    <!-- <?php if ($instagram): ?>
      <a href='<?= esc_url(
                  $instagram,
                ) ?>' aria-label='Instagram' target='_blank' class='team-card__instagram'></a>
    <?php endif; ?> -->
    <div class='team-card__rate'>
      <div class='stars yellow'>
        <?= str_repeat("<div class='star'></div>", $starsCount) ?>
        <?php if ($levelName): ?>
          <span>(<?= esc_html($levelName) ?>)</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class='swiper mini-swiper'>
    <div class='swiper-wrapper'>
      <?php if (!empty($images)) {
        foreach ($images as $item) {
          $work_image = $item['image'];
          $tag = $item['master_image_work_tag'];

          $tagName = '';
          if (is_object($tag) && isset($tag->name)) {
            $tagName = $tag->name;
          } elseif (is_array($tag) && isset($tag[0]->name)) {
            $tagName = $tag[0]->name;
          }

          $work_image_url = '';
          if (is_array($work_image)) {
            $work_image_url = $work_image['url'] ?? '';
          } elseif (is_numeric($work_image)) {
            $work_image_url = wp_get_attachment_image_url($work_image, 'large');
          }

          if ($work_image_url) {
            echo "<div class='swiper-slide'>
                    <img src='" .
              esc_url($work_image_url) .
              "' alt='" .
              esc_attr($tagName) .
              "'>
                  </div>";
          }
        }
      } ?>
    </div>
    <div class='swiper-scrollbar'></div>
  </div>

  <?php
  // Check if Learn More button should be shown
  $show_learn_more = false;
  if (!$isPage) {
    $details_popup = get_field('details_pop_up', $post->ID);
    $master_description = $details_popup['description'] ?? null;
    $master_achievements = $details_popup['masters_achievements'] ?? null;
    $show_learn_more = ($master_description && !empty(trim($master_description))) ||
      ($master_achievements && is_array($master_achievements) && !empty($master_achievements));
  }
  ?>

  <div class='team-card__buttons page<?= $show_learn_more ? ' two-buttons' : '' ?>'>
    <button data-staff-id="<?= esc_attr(
                              $id,
                            ) ?>" class='btn yellow book-tem'>Book an Appointment</button>
    <?php if ($show_learn_more): ?>
      <button data-master-id="<?= esc_attr($post->ID) ?>" class="btn master-details-btn">Learn More</button>
    <?php endif; ?>
  </div>
</div>