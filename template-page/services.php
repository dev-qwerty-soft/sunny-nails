<?php

/**
 * Template Name: Services
 *
 * @package AltegioSync
 */

get_header();

$ordered_category_ids = function_exists('get_field') ? get_field('category_selection') : [];

if (empty($ordered_category_ids)) {
    $categories = get_terms([
        'taxonomy' => 'service_category',
        'hide_empty' => true,
        'orderby' => 'name',
    ]);
} else {
    $categories = [];
    foreach ($ordered_category_ids as $cat_id) {
        $term = get_term($cat_id, 'service_category');
        if (!is_wp_error($term) && $term !== null) {
            $categories[] = $term;
        }
    }
}

?>
<main>
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
                            'meta_query' => [
                                [
                                    'key' => 'is_online',
                                    'value' => '1',
                                    'compare' => '='
                                ]
                            ],
                            'orderby' => 'menu_order',
                            'order'   => 'ASC'
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

                            <?php
                            set_query_var('services', $services);
                            get_template_part('template-parts/booking/service');
                            ?>

                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>