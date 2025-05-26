<?php
/**
 * Template Name: Privacy Policy
 */
get_header();
?>
<main>
  <section class="privacy-policy-section">
    <div class="container">
      <div class="privacy-policy-section__top">
        <h1 class="title"><?php the_title(); ?></h1>
        <p class="paragraph">Last updated: <?= get_the_modified_date(); ?></p>
      </div>
      <div class="privacy-policy-section__blocks">
        
      </div>
    </div>
  </section>
</main>
<?php get_footer(); ?>