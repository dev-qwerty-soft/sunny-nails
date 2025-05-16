<?php
  $isPage = $args["page"] ?? false;
  $array = getPosts("master");
?>

<section class='team-section<?= $isPage ? ' page' : '' ?>'>
  <div class='container'>
    <div class='team-section__top'>
      <h2 class='title'><?php the_field('team_title', 'option'); ?></h2>
      <p class='paragraph'><?php the_field('team_description', 'option'); ?></p>
      <?php
        $link = get_field('team_link_url', 'option');
        $text = get_field('team_link_text', 'option');
        if ($link && $text) {
          echo "<a href='$link' class='btn yellow'>$text</a>";
        }
      ?>
    </div>
    <?php if ($isPage): ?>
      <div class='team-section__grid'>
        <?php 
          foreach ($array as $post) {
            get_template_part('template-parts/shared/card-master', null, ['post' => $post]);
          };
        ?>
      </div>
    <?php else: ?>
      <div class='team-section__wrapper button-container black'>
        <div class='swiper team-swiper'>
          <div class='swiper-wrapper'>
            <?php 
              foreach ($array as $post) {
                echo "<div class='swiper-slide'>";
                get_template_part('template-parts/shared/card-master', null, ['post' => $post]);
                echo "</div>";
              }
            ?>
          </div>
        </div>
        <button type='button' aria-label='Next slide' class='button swiper-button-next'></button>
        <button type='button' aria-label='Previous slide' class='button swiper-button-prev'></button>
      </div>
    <?php endif; ?>
  </div>
</section>
