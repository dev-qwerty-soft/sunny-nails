<?php
  $post = $args["post"] ?? null;
  $isPage = $args["isPage"] ?? false;
  if (!$post) {
    return;
  }
  $id = get_field('altegio_id', $post->ID);
  $image = get_the_post_thumbnail_url($post->ID);
  $images = get_field('master_images_work', $post->ID);
  $name = isset($post->post_title) ? $post->post_title : '';
  $instagram = get_field('instagram_url', $post->ID);
  $level = (int) get_field('master_level', $post->ID);
  $link_team = get_field('team_link_url', 'option');
?>
<div data-altegio-id='<?= $id; ?>' class='team-card'>
  <img class='team-card__image' src='<?= $image; ?>' alt='images'>
  <div class='team-card__text'>
    <span class='team-card__name'><?= $name; ?></span>
    <?php
    if ($instagram) {
      echo "<a href='$instagram' aria-label='Instagram' target='_blank' class='team-card__instagram'></a>";
    };
    ?>
    <div class='team-card__rate'>
      <div class='stars yellow'>
        <?php
        if (!is_array($level)) {
          echo str_repeat("<div class='star'></div>", $level);
        };
        ?>
        <span>(Sunny Inferno)</span>
      </div>
    </div>
  </div>
  <div class='swiper mini-swiper'>
    <div class='swiper-wrapper'>
      <?php
      if (!empty($images)) {
        foreach ($images as $item) {
          $image = $item['master_image_work'];
          $tag = $item['master_image_work_tag'];
          $tagName = isset($tag->name) ? $tag->name : "";
          $url = isset($image['url']) ? $image['url'] : "";
          echo "<div class='swiper-slide'>
              <img src='$url' alt='$tagName'>
            </div>";
        };
      };
      ?>
    </div>
    <div class='swiper-scrollbar'></div>
  </div>
  <div class='team-card__buttons<?= $isPage ? ' page' : ''; ?>'>
    <button class='btn yellow'>Book an Appointment</button>
    <?php
      if(!$isPage && $link_team) {
        echo "<a href='$link_team' class='btn'>Learn More</a>";
      };
    ?>
  </div>
</div>