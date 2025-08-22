<?php

/**
 * Main template for displaying WooCommerce pages.
 *
 * @package WordPress
 * @subpackage Your_Theme
 * @since Your_Theme 1.0
 */
get_header(); ?>
<main id="primary" class="content-area">
    <div class="container">
        <?php if (have_posts()): ?>
            <?php while (have_posts()):
                the_post(); ?>
                <h1 class="entry-title"><?php the_title(); ?></h1>

                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            <?php
            endwhile; ?>
        <?php else: ?>
            <p><?php esc_html_e('Sorry, nothing found.', 'your-theme'); ?></p>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>