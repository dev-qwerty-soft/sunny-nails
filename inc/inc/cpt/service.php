<?php
add_action('init', function () {
    // CPT "service"
    register_post_type('service', [
        'labels' => [
            'name' => __('Services'),
            'singular_name' => __('Service'),
        ],
        'public' => false,
        'show_ui' => true,
        'has_archive' => false,
        'rewrite' => false,
        'supports' => ['title', 'editor'],
        'menu_position' => 21,
        'menu_icon' => 'dashicons-hammer',
        'show_in_rest' => false,
    ]);

    // Taxonomy "service_category"
    register_taxonomy('service_category', ['service'], [
        'labels' => [
            'name' => __('Service Categories'),
            'singular_name' => __('Service Category'),
        ],
        'public' => false,
        'show_ui' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);
});
