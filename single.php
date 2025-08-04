<?php
get_header();
if (have_posts()) :
    while (have_posts()) : the_post(); ?>
        <main class="single-post-main">
            <section class="post-hero">
                <div class="post-hero-bg">
                    <div class="post-hero-header">
                        <h1 class="post-hero-title"><?php the_title(); ?></h1>
                        <?php if (get_field('short_text')): ?>
                            <div class="post-hero-short-text"><?php the_field('short_text'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="post-hero-row">
                        <div class="post-hero-social">

                            <div class="icons">
                                <?php displayIcon(); ?>
                            </div>
                            <button class="copy-link-btn" onclick="navigator.clipboard.writeText(window.location.href)">
                                <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10.6654 9.10001V11.9C10.6654 14.2333 9.73203 15.1667 7.3987 15.1667H4.5987C2.26536 15.1667 1.33203 14.2333 1.33203 11.9V9.10001C1.33203 6.76668 2.26536 5.83334 4.5987 5.83334H7.3987C9.73203 5.83334 10.6654 6.76668 10.6654 9.10001Z" stroke="#302F34" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M14.6654 5.10001V7.90001C14.6654 10.2333 13.732 11.1667 11.3987 11.1667H10.6654V9.10001C10.6654 6.76668 9.73203 5.83334 7.3987 5.83334H5.33203V5.10001C5.33203 2.76668 6.26536 1.83334 8.5987 1.83334H11.3987C13.732 1.83334 14.6654 2.76668 14.6654 5.10001Z" stroke="#302F34" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Copy Link
                            </button>
                        </div>
                        <div class="post-hero-author">
                            <?php
                            $author_id = get_the_author_meta('ID');
                            $author_avatar_url = get_avatar_url($author_id, ['size' => 'medium']);
                            $default_avatar = get_template_directory_uri() . '/assets/img/default-avatar.png';
                            $author_name = get_the_author_meta('display_name', $author_id);
                            ?>
                            <span class="author-avatar">
                                <img src="<?php echo esc_url($author_avatar_url ? $author_avatar_url : $default_avatar); ?>" alt="Author" width="32" height="32" style="border-radius:50%;">
                            </span>
                            <div class="author-info">
                                <span class="author-name"><?php echo esc_html($author_name); ?></span>
                                <span class="post-hero-date"><?php echo get_the_date('F j, Y'); ?></span>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
            <section class="single-post-container">
                <article class="single-post-article">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="post-hero-image">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                    <?php endif; ?>
                    <div class="single-post-content">
                        <?php the_content(); ?>
                    </div>
                    <?php
                    $post_categories = get_the_category();
                    ?>
                    <div class="blog-categories">
                        <?php foreach ($post_categories as $cat): ?>
                            <span class="blog-category">
                                <?php echo esc_html($cat->name); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </article>
            </section>
            <?php
            $left_img = get_field('coming_soon_post_left_image', 'option');
            $right_img = get_field('coming_soon_post_right_image', 'option');
            $has_images = $left_img || $right_img;
            ?>
            <section class=" soon-section<?php if ($has_images) echo ' soon-section--with-images'; ?>">
                <div class="container">
                    <h2 class="title"><?php the_field('coming_soon_post_title', 'option'); ?></h2>
                    <div class="paragraph">
                        <div class="buttons">
                            <button type="button" class="btn yellow open-popup mini">Book an Appointment</button>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            $current_id = get_the_ID();
            $categories = wp_get_post_categories($current_id);

            $args = [
                'post__not_in' => [$current_id],
                'posts_per_page' => 3,
                'ignore_sticky_posts' => 1,
            ];

            if (!empty($categories)) {
                $args['category__in'] = $categories;
                $args['orderby'] = 'rand';
            }

            $query = new WP_Query($args);


            if ($query->post_count < 3) {
                $args = [
                    'post__not_in' => [$current_id],
                    'posts_per_page' => 3,
                    'orderby' => 'rand',
                    'ignore_sticky_posts' => 1,
                ];
                $query = new WP_Query($args);
            }
            ?>

            <section class="related-posts-section">
                <div class="related-posts-header">
                    <h2 class="related-posts-title">You may like</h2>

                    <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="show-more-btn btn yellow">
                        Show more <span class="arrow">→</span>
                    </a>
                </div>
                <div class="blog-posts-list">
                    <?php while ($query->have_posts()): $query->the_post(); ?>
                        <a href="<?php the_permalink(); ?>" class="blog-post-card">
                            <div class="blog-post-thumb">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('medium'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="blog-post-meta"><?php echo get_the_date('F j, Y'); ?></div>
                            <div class="blog-post-title"><?php the_title(); ?></div>
                            <div class="blog-post-excerpt">
                                <?php
                                $short_text = get_field('short_text');
                                if ($short_text) {
                                    echo esc_html($short_text);
                                } else {
                                    echo get_the_excerpt();
                                }
                                ?>
                            </div>
                            <div class="blog-post-cats">
                                <?php
                                $cats = get_the_category();
                                foreach ($cats as $cat) {
                                    echo '<span class="blog-post-cat">' . esc_html($cat->name) . '</span> ';
                                }
                                ?>
                            </div>
                        </a>
                    <?php endwhile;
                    wp_reset_postdata(); ?>
                </div>
                <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="show-more-btn mobile btn yellow">
                    Show more <span class="arrow">→</span>
                </a>
            </section>

        </main>
        <style>
            .soon-section--with-images .container::before {
                background-image: url('<?php echo esc_url($left_img['url']); ?>');
                height: 80%;
            }

            .soon-section--with-images .container:after {
                background-image: url('<?php echo esc_url($right_img['url']); ?>');
                height: 80%;
            }
        </style>
<?php
    endwhile;
endif;
get_footer();
