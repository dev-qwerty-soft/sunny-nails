<?php
$post = $args['post'] ?? null;
$isPage = $args['isPage'] ?? false;
if (!$post) {
  return;
}

// Уніфіковані рівні
$levelTitles = [
  -1 => 'Intern',
  1 => 'Sunny Ray',
  2 => 'Sunny Shine',
  3 => 'Sunny Inferno',
  4 => 'Trainer',
  5 => 'Sunny Inferno, Supervisor',
];

$level = max((int) get_field('master_level', $post->ID), -1);
$levelName = $levelTitles[$level] ?? '';

$starsCount = match (true) {
  $level === -1 => 0,
  $level === 1 => 1,
  $level === 2 => 2,
  $level === 3 => 3,
  $level === 4, $level === 5 => 4,
  default => 0,
};

$id = get_field('altegio_id', $post->ID);
$image = get_the_post_thumbnail_url($post->ID);
$images = get_field('master_images_work', $post->ID);
$name = isset($post->post_title) ? $post->post_title : '';
$instagram = get_field('instagram_url', $post->ID);
?>

<div data-altegio-id='<?= esc_attr($id) ?>' class='team-card'>
  <img class='team-card__image' src='<?= esc_url($image) ?>' alt='<?= esc_attr($name) ?>'>
  <div class='team-card__text'>
    <span class='team-card__name'><?= esc_html($name) ?></span>
    <?php if ($instagram): ?>
      <a href='<?= esc_url(
        $instagram,
      ) ?>' aria-label='Instagram' target='_blank' class='team-card__instagram'></a>
    <?php endif; ?>
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
          $image = $item['image'];
          $tag = $item['master_image_work_tag'];

          $tagName = '';
          if (is_object($tag) && isset($tag->name)) {
            $tagName = $tag->name;
          } elseif (is_array($tag) && isset($tag[0]->name)) {
            $tagName = $tag[0]->name;
          }

          $url = '';
          if (is_array($image)) {
            $url = $image['url'] ?? '';
          } elseif (is_numeric($image)) {
            $url = wp_get_attachment_image_url($image, 'large');
          }

          if ($url) {
            echo "<div class='swiper-slide'>
                    <img src='" .
              esc_url($url) .
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

  <div class='team-card__buttons page'>
    <button data-staff-id="<?= esc_attr(
      $id,
    ) ?>" class='btn yellow book-tem'>Book an Appointment</button>
    <?php
    $link_team = get_field('team_link_url', 'option');
    $url = $link_team['url'] ?? '';
    $target = $link_team['target'] ?? '_self';
    $title = $link_team['title'] ?? 'Learn More';
    ?>

    <?php if (!$isPage && $url): ?>
      <!-- <a href="<?= esc_url($url) ?>" target="<?= esc_attr(
  $target,
) ?>" class="btn"><?= esc_html($title) ?></a> -->
    <?php endif; ?>
  </div>
</div>