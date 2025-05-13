<?php
/**
 * Template Name: Contact
 */
get_header();
?>
<main>
  <?php
    get_template_part("template-parts/sections/contact", null, ["page" => true]);
  ?>
</main>
<?php get_footer(); ?>