<?php

/**
 * Template Name: Services
 *
 * @package AltegioSync
 */

get_header();


$categories = get_terms([
    'taxonomy' => 'service_category',
    'hide_empty' => true,
]);

?>

<section class="services-section">
    <div class="container">

        <div class="services-header">
            <h1 class="title"><?php the_title(); ?></h1>
            <?php
            if (have_posts()) : while (have_posts()) : the_post();
                    $content = wp_strip_all_tags(get_the_content());
                    $sentences = preg_split('/(?<=[.!?])\s+/', $content, 2);
                    $description = !empty($sentences[0]) ? $sentences[0] : '';
                    if ($description) : ?>
                        <p class="section-description"><?php echo esc_html($description); ?></p>
            <?php endif;
                endwhile;
            endif;
            ?>
        </div>

        <div class="services-content">
            <?php if (empty($categories)) : ?>
                <p>Категорії не знайдено.</p>
            <?php else : ?>
                <?php foreach ($categories as $category) : ?>
                    <?php
                    $services = get_posts([
                        'post_type' => 'service',
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                        'tax_query' => [[
                            'taxonomy' => 'service_category',
                            'field' => 'term_id',
                            'terms' => $category->term_id,
                        ]],
                        'orderby' => 'meta_value_num',
                        'meta_key' => 'price_min',
                        'order' => 'ASC',
                    ]);

                    if (empty($services)) continue;

                    $category_image = function_exists('get_field') ? get_field('image', 'service_category_' . $category->term_id) : null;
                    ?>
                    <div class="category-block">

                        <?php if ($category_image) : ?>
                            <div class="categories-overview">
                                <div class="category-overview-item">
                                    <div class="category-image">
                                        <img src="<?php echo esc_url($category_image['url']); ?>"
                                            alt="<?php echo esc_attr($category_image['alt'] ?: $category->name); ?>"
                                            class="category-img" />
                                    </div>
                                    <h3 class="category-name"><?php echo esc_html($category->name); ?></h3>
                                </div>
                            </div>
                        <?php else: ?>
                            <h3 class="category-name"><?php echo esc_html($category->name); ?></h3>
                        <?php endif; ?>

                        <div class="services-list">
                            <?php foreach ($services as $service) :
                                $post_id = $service->ID;
                                $title = get_the_title($post_id);
                                $price_min = get_post_meta($post_id, 'price_min', true);
                                $currency = get_post_meta($post_id, 'currency', true) ?: 'SGD';
                                $duration = get_post_meta($post_id, 'duration_minutes', true);
                                $wear_time = get_post_meta($post_id, 'wear_time', true);


                                if (empty($wear_time) && !empty($service->post_content)) {
                                    preg_match('/wear\s+time:?\s+([^\.]+)/i', $service->post_content, $matches);
                                    if (!empty($matches[1])) {
                                        $wear_time = trim($matches[1]);
                                    }
                                }

                                $acf_description = get_post_meta($post_id, 'description', true);
                            ?>
                                <div class="service-card" data-service-id="<?php echo esc_attr($post_id); ?>">
                                    <div class="service-meta">
                                        <h3 class="service-title"><?php echo esc_html($title); ?>
                                            <div class="service-price">
                                                <?php echo esc_html($price_min); ?> <?php echo esc_html($currency); ?>
                                            </div>
                                        </h3>

                                        <?php if ($duration) : ?>
                                            <div class="service-duration"><strong>Duration:</strong> <?php echo esc_html($duration); ?> min</div>
                                        <?php endif; ?>

                                        <?php if ($wear_time) : ?>
                                            <div class="service-wear-time"><strong>Wear time:</strong> <?php echo esc_html($wear_time); ?></div>
                                        <?php endif; ?>

                                        <?php if ($acf_description) : ?>
                                            <div class="service-description"><?php echo esc_html($acf_description); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <button type="button"
                                        class="book-btn"
                                        data-popup-open="true"
                                        data-service-id="<?php echo esc_attr($post_id); ?>">

                                        Book this
                                        <span class="book-bt__icon">
                                            <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9.22581 0.773971L9.22581 8.01857M9.22581 0.773971L1.98122 0.773971M9.22581 0.773971L0.773784 9.226" stroke="#302F34" stroke-width="0.838404" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>


<?php get_footer(); ?>