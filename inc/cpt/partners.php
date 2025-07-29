<?php
add_action('init', function () {
  register_post_type('partner', [
    'labels' => [
      'name' => 'Partners',
      'singular_name' => 'Partner',
    ],
    'public' => false,
    'show_ui' => true,
    'has_archive' => false,
    'rewrite' => false,
    'show_in_rest' => true,
    'supports' => ['title', 'thumbnail'],
    'menu_icon' => 'dashicons-groups',
  ]);
  register_taxonomy('partner_cat', 'partner', [
    'label' => 'Categories',
    'hierarchical' => true,
    'show_in_rest' => true,
  ]);
});
