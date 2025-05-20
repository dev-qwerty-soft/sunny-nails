<?php
/**
 * The template for displaying 404 pages (not found)
 */
  get_header();
?>
<main>
  <section class="section-not-found">
    <div class="container">
      <h1 class="title">Whoops...</h1>
      <span class="section-not-found__number">404</span>
      <p class="paragraph">The requested URL <b><?= esc_html($_SERVER["REQUEST_URI"]); ?></b> was not found on this server.</p>
      <div class="section-not-found__links">
        <a href="javascript:history.back()" class="btn">Go back</a>
        <a href="<?= esc_url(home_url('/')); ?>" class="btn yellow">Go to home page</a>
      </div>
    </div>
  </section>
</main>
<?php get_footer(); ?>