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
                                <?php
                                if (have_rows('post_social', 'option')):
                                    while (have_rows('post_social', 'option')): the_row();
                                        $icon = get_sub_field('post_social_icon', 'option');
                                        $link = get_sub_field('post_social_link', 'option');

                                        if ($icon):
                                            $current_url = get_permalink();
                                            $current_title = get_the_title();
                                            $share_url = '';

                                            if ($link) {
                                                $link_url = is_array($link) ? $link['url'] : $link;
                                                $link_lower = strtolower($link_url);

                                                if (strpos($link_lower, 'facebook.com') !== false) {
                                                    $share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($current_url);
                                                } elseif (strpos($link_lower, 'linkedin.com') !== false) {
                                                    $share_url = 'https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode($current_url) . '&title=' . urlencode($current_title);
                                                } elseif (strpos($link_lower, 'wa.me') !== false || strpos($link_lower, 'whatsapp') !== false) {
                                                    $share_url = 'https://wa.me/?text=' . urlencode($current_title . ' ' . $current_url);
                                                } elseif (strpos($link_lower, 'twitter') !== false || strpos($link_lower, 'x.com') !== false) {
                                                    $share_url = 'https://twitter.com/intent/tweet?url=' . urlencode($current_url) . '&text=' . urlencode($current_title);
                                                } else {
                                                    $share_url = $link_url;
                                                }
                                            } else {
                                                $share_url = '#';
                                            }
                                ?>
                                            <a href="<?php echo esc_url($share_url); ?>" target="_blank" rel="noopener" class="social-link">
                                                <img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($icon['alt']); ?>" width="32" height="32">
                                            </a>
                                <?php endif;
                                    endwhile;
                                endif;
                                ?>
                            </div>
                            <button class="copy-link-btn" onclick="copyLinkWithFeedback(this)">
                                <svg class="copy-icon" width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10.6654 9.10001V11.9C10.6654 14.2333 9.73203 15.1667 7.3987 15.1667H4.5987C2.26536 15.1667 1.33203 14.2333 1.33203 11.9V9.10001C1.33203 6.76668 2.26536 5.83334 4.5987 5.83334H7.3987C9.73203 5.83334 10.6654 6.76668 10.6654 9.10001Z" stroke="#302F34" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M14.6654 5.10001V7.90001C14.6654 10.2333 13.732 11.1667 11.3987 11.1667H10.6654V9.10001C10.6654 6.76668 9.73203 5.83334 7.3987 5.83334H5.33203V5.10001C5.33203 2.76668 6.26536 1.83334 8.5987 1.83334H11.3987C13.732 1.83334 14.6654 2.76668 14.6654 5.10001Z" stroke="#302F34" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>

                                <svg class="check-icon" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <circle cx="8" cy="8" r="6" fill="#06B569" />
                                    <path d="M7.9987 1.33334C11.6807 1.33334 14.6654 4.31868 14.6654 8.00001C14.6654 11.6813 11.6807 14.6667 7.9987 14.6667C4.3167 14.6667 1.33203 11.6813 1.33203 8.00001C1.33203 4.31868 4.3167 1.33334 7.9987 1.33334ZM7.9987 2.44468C4.93536 2.44468 2.44336 4.93668 2.44336 8.00001C2.44336 11.0633 4.93536 13.5553 7.9987 13.5553C11.062 13.5553 13.554 11.0633 13.554 8.00001C13.554 4.93668 11.062 2.44468 7.9987 2.44468Z" fill="#06B569" />
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11.1882 5.49764C11.2819 5.60276 11.3346 5.7453 11.3346 5.89393C11.3346 6.04256 11.2819 6.18511 11.1882 6.29022L7.43845 10.4915C7.3889 10.547 7.33007 10.5911 7.26532 10.6212C7.20057 10.6512 7.13117 10.6667 7.06108 10.6667C6.991 10.6667 6.9216 10.6512 6.85685 10.6212C6.79209 10.5911 6.73326 10.547 6.68371 10.4915L4.8207 8.4045C4.77291 8.3528 4.7348 8.29095 4.70858 8.22256C4.68237 8.15418 4.66856 8.08062 4.66799 8.0062C4.66741 7.93177 4.68007 7.85796 4.70522 7.78908C4.73038 7.72019 4.76752 7.65761 4.8145 7.60498C4.86147 7.55235 4.91732 7.51073 4.97881 7.48255C5.04029 7.45436 5.10616 7.44018 5.17259 7.44083C5.23901 7.44148 5.30466 7.45694 5.36569 7.48631C5.42673 7.51569 5.48193 7.55839 5.52808 7.61193L7.06092 9.32936L10.4804 5.49764C10.5269 5.44556 10.5821 5.40424 10.6428 5.37604C10.7035 5.34785 10.7686 5.33334 10.8343 5.33334C10.9 5.33334 10.9651 5.34785 11.0258 5.37604C11.0865 5.40424 11.1417 5.44556 11.1882 5.49764Z" fill="white" />
                                </svg>
                                <span class="copy-text">Copy Link</span>
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
                                <?php
                                $author_website = get_the_author_meta('user_url', $author_id);
                                if ($author_website):
                                ?>
                                    <a href="<?php echo esc_url($author_website); ?>" target="_blank" class="author-name-link">
                                        <span class="author-name"><?php echo esc_html($author_name); ?></span>
                                    </a>
                                <?php else: ?>
                                    <span class="author-name"><?php echo esc_html($author_name); ?></span>
                                <?php endif; ?>
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
                            <div class="blog-post-arrow">
                                <svg xmlns="http://www.w3.org/2000/svg" width="21" height="20" viewBox="0 0 21 20" fill="none">
                                    <path d="M14.7594 13.5272L14.8614 5.47025L6.80442 5.57224M14.2948 6.03684L6.02252 14.3091" stroke="#85754F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
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
        <script>
            function copyLinkWithFeedback(button) {
                const copyIcon = button.querySelector('.copy-icon');
                const checkIcon = button.querySelector('.check-icon');
                const textElement = button.querySelector('.copy-text');

                navigator.clipboard.writeText(window.location.href).then(function() {
                    // Hide copy icon, show check icon
                    copyIcon.style.display = 'none';
                    checkIcon.style.display = 'inline-block';
                    textElement.textContent = 'Copied';

                    // Add success class
                    button.classList.add('copied');

                    // Reset after 2 seconds
                    setTimeout(function() {
                        copyIcon.style.display = 'inline-block';
                        checkIcon.style.display = 'none';
                        textElement.textContent = 'Copy Link';
                        button.classList.remove('copied');
                    }, 2000);
                }).catch(function() {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = window.location.href;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);

                    textElement.textContent = 'Copied';
                    button.classList.add('copied');

                    setTimeout(function() {
                        textElement.textContent = 'Copy Link';
                        button.classList.remove('copied');
                    }, 2000);
                });
            }
        </script>
<?php
    endwhile;
endif;
get_footer();
