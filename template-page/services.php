<?php

/**
 * Template Name: Services
 *
 * @package AltegioSync
 */

get_header();

// Check if ACF is active and the category_selection field exists
$ordered_category_ids = [];
if (function_exists('get_field')) {
    $ordered_category_ids = get_field('category_selection');
}

// If no ACF selection is found, fall back to the original method
if (empty($ordered_category_ids)) {
    $service_categories = get_terms([
        'taxonomy' => 'service_category',
        'hide_empty' => true,
        'order' => 'DESC'
    ]);
} else {
    // Get all selected categories in the exact order selected in ACF
    $service_categories = [];
    foreach ($ordered_category_ids as $cat_id) {
        $term = get_term($cat_id, 'service_category');
        if (!is_wp_error($term) && !empty($term)) {
            $service_categories[] = $term;
        }
    }
}

// Function to get services for a specific category
function get_services_by_category($category_id)
{
    return get_posts([
        'post_type' => 'service',
        'posts_per_page' => -1,
        'tax_query' => [
            [
                'taxonomy' => 'service_category',
                'field' => 'term_id',
                'terms' => $category_id
            ]
        ],
        'meta_key' => 'price_min',
        'orderby' => 'meta_value_num',
        'order' => 'ASC'
    ]);
}
?>

<section class="services-section">
    <div class="container">
        <!-- Page Header with Title and description -->
        <div class="services-header">
            <h1 class="section-title"><?php the_title(); ?></h1>
            <?php
            if (have_posts()) : while (have_posts()) : the_post();
                    $content = get_the_content();
                    $content = wp_strip_all_tags($content);
                    $sentences = preg_split('/(?<=[.!?])\s+/', $content, 2);
                    $description = !empty($sentences[0]) ? $sentences[0] : '';
            ?>
                    <?php if (!empty($description)): ?>
                        <p class="section-description"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
            <?php
                endwhile;
            endif;
            ?>
        </div>

        <div class="services-content">
            <!-- Services by Category -->
            <?php foreach ($service_categories as $category):
                // Get services for this category
                $services = get_services_by_category($category->term_id);

                // Skip if no services
                if (empty($services)) continue;

                // Get ACF image for the category
                $category_image = function_exists('get_field')
                    ? get_field('image', 'service_category_' . $category->term_id)
                    : null;
            ?>
                <div class="category-block">
                    <!-- Categories Overview Section -->
                    <?php if ($category_image): ?>
                        <div class="categories-overview">
                            <div class="category-overview-item">
                                <div class="category-image">
                                    <img src="<?php echo esc_url($category_image['url']); ?>"
                                        alt="<?php echo esc_attr($category_image['alt'] ?: $category->name); ?>"
                                        class="category-img">
                                </div>
                                <h3 class="category-name"><?php echo esc_html($category->name); ?></h3>
                            </div>
                        </div>
                    <?php endif; ?>


                    <?php if (!empty($services)): ?>
                        <div class="services-list">
                            <?php foreach ($services as $service):
                                $post_id = $service->ID;
                                $title = get_the_title($post_id);
                                $price_min = get_post_meta($post_id, 'price_min', true);
                                $currency = get_post_meta($post_id, 'currency', true) ?: 'SGD';
                                $duration = get_post_meta($post_id, 'duration_minutes', true);
                                $wear_time = get_post_meta($post_id, 'wear_time', true);

                                // Try to extract wear time from content if not in meta
                                if (empty($wear_time) && !empty($service->post_content)) {
                                    preg_match('/wear\s+time:?\s+([^\.]+)/i', $service->post_content, $matches);
                                    if (!empty($matches[1])) {
                                        $wear_time = trim($matches[1]);
                                    }
                                }
                            ?>
                                <div class="service-card">
                                    <div class="service-meta">
                                        <h3 class="service-title"><?php echo esc_html($title); ?>
                                            <div class="service-price">
                                                <?php echo esc_html($price_min); ?> <?php echo esc_html($currency); ?>
                                            </div>
                                        </h3>
                                        <?php if ($duration): ?>
                                            <div class="service-duration"><strong>Duration:</strong> <?php echo esc_html($duration); ?> min</div>
                                        <?php endif; ?>

                                        <?php if (!empty($wear_time)): ?>
                                            <div class="service-wear-time"><strong>Wear time:</strong> <?php echo esc_html($wear_time); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php
                                        $acf_description = get_post_meta($post_id, 'description', true);
                                        if (!empty($acf_description)): ?>
                                            <div class="service-description"><?php echo esc_html($acf_description); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <button type="button" class="book-btn">Book this
                                        <span class="book-bt__icon">
                                            <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9.22581 0.773971L9.22581 8.01857M9.22581 0.773971L1.98122 0.773971M9.22581 0.773971L0.773784 9.226" stroke="#302F34" stroke-width="0.838404" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-services">
                            <p>No services found in this category.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>