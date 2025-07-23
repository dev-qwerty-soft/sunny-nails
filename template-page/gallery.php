<?php

/**
 * Template Name: Gallery
 */
get_header(); ?>
<main>
  <?php get_template_part('template-parts/gallery/gallery-grid', null, [
    'full' => true,
  ]); ?>
</main>
<?php get_footer(); ?>
