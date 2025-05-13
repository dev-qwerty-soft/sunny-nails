<?php

/**
 * Template Name: Team
 */
get_header();
?>
<main>
  <?php
    get_template_part('template-parts/sections/team', null, ["page" => true]);
  ?>
</main>
<?php get_footer(); ?>