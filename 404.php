<?php
/**
 * The template for displaying 404 pages (not found)
 */
  get_header();
?>
<main>
  <section class="section-not-found">
    <div class="container">
      <span class="section-not-found__number"></span>
      <h1 class="title">Oops! Page not found.</h1>
      <p class="paragraph">We couldn’t find the page you’re looking for. It might have been moved or doesn’t exist anymore.</p>
      <div class="section-not-found__links">
        <a href="javascript:history.back()" class="btn">Go back</a>
        <a href="<?= esc_url(home_url('/')); ?>" class="btn yellow">Go to home page</a>
      </div>
    </div>
  </section>
</main>
<?php get_footer(); ?>