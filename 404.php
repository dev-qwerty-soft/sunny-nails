<?php

/**
 * The template for displaying 404 pages (not found)
 */
get_header();
?>
<style>
  body {
    font-size: 12px;
    height: 100%;
  }

  img {
    max-width: 100%;
  }

  #fof {
    display: block;
    width: 100%;
    line-height: 1.6em;
    text-align: center;
    margin: 5% auto;
  }

  #fof h1 {
    font-size: 5em;
    text-transform: uppercase;
    color: #ff7518;
    margin-bottom: 0;
  }

  #fof .nubber {
    font-size: 250px;
    line-height: 110%;
    font-weight: 900;
    color: #ff7518;
  }

  @media screen and (max-width: 767px) {
    #fof .nubber {
      font-size: 100px;
    }
  }

  #fof img {
    margin: 25px auto;
  }

  #fof p {
    margin: 0 0 25px 0;
    padding: 0;
    font-size: 16px;
  }

  #fof a {
    color: #4dc6ff;
    font-weight: 700;
    text-decoration: none;
  }

  #fof a:hover {
    color: #ff7518;
  }
</style>
<main id="main" class="not__found">
  <div class="container">
    <section id="fof" class="clear">
      <h1><?php _e('Whoops', 'websitelangid'); ?></h1>
      <div class="nubber">404</div>
      <p>
        <?php _e('The requested URL', 'websitelangid'); ?> <b><?php echo esc_html($_SERVER["REQUEST_URI"]); ?></b>
        <?php _e('was not found on this server.', 'websitelangid'); ?>
      </p>
      <p>
        <?php _e('Go back to the', 'websitelangid'); ?>
        <a href="javascript:history.back()"><?php _e('previous page', 'websitelangid'); ?></a>
        <?php _e('or visit our', 'websitelangid'); ?>
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php _e('homepage', 'websitelangid'); ?></a>.
      </p>
    </section>
  </div>
</main>

<?php get_footer(); ?>