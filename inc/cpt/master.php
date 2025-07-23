<?php
add_action('init', 'register_master_post_type');
function register_master_post_type() {
  register_post_type('master', [
    'labels' => [
      'name' => __('Masters'),
      'singular_name' => __('Master'),
    ],
    'public' => false,
    'show_ui' => true,
    'has_archive' => false,
    'rewrite' => false,
    'supports' => ['title', 'thumbnail'],
    'menu_position' => 20,
    'menu_icon' => 'dashicons-id',
    'show_in_rest' => false,
  ]);
}

add_action('init', 'register_gallery_tags_taxonomy');
function register_gallery_tags_taxonomy() {
  register_taxonomy(
    'gallery_tag',
    ['master'],
    [
      'labels' => [
        'name' => __('Gallery Tags'),
        'singular_name' => __('Gallery Tag'),
      ],
      'public' => false,
      'show_ui' => true,
      'hierarchical' => true,
      'show_in_rest' => true,
      'show_admin_column' => true,
    ],
  );
}
