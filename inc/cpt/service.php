<?php
add_action('init', 'register_service_post_type');

function register_service_post_type()
{
    register_post_type('service', [
        'labels' => [
            'name' => __('Services'),
            'singular_name' => __('Service'),
        ],
        'public' => false,
        'show_ui' => true,
        'has_archive' => false,
        'rewrite' => false,
        'supports' => ['title'],
        'menu_position' => 21,
        'menu_icon' => 'dashicons-hammer',
        'show_in_rest' => false,
    ]);
}
