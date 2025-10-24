<?php
/**
 * Template Name: About
 */
get_header(); ?>
<main>
  <section class="about-section">
    <div class="container">
      <?php
      $img = get_field('about_us_image');
      $url = isset($img['url']) ? $img['url'] : null;
      $title = isset($img['title']) ? $img['title'] : null;
      if ($url && $title) {
        echo "<img class='about-section__image' src='$url' alt='$title'>";
      }
      ?>
      <div class="about-section__text">
        <h1 class="title"><?php the_field('about_us_title'); ?></h1>
        <div class="paragraph">
          <?php the_field('about_us_text'); ?>
        </div>
      </div>
    </div>
  </section>
</main>
<?php get_footer(); ?>
