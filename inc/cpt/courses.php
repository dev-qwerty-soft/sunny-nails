<?php
add_action('init', function () {
  register_post_type('course', [
    'labels' => [
      'name' => 'Courses',
      'singular_name' => 'Course',
    ],
    'public' => false,
    'show_ui' => true,
    'has_archive' => false,
    'rewrite' => false,
    'show_in_rest' => true,
    'supports' => ['title', 'thumbnail'],
    'menu_icon' => 'dashicons-book-alt',
  ]);
  register_taxonomy('course_cat', 'course', [
    'label' => 'Categories',
    'hierarchical' => true,
    'show_in_rest' => true,
  ]);
});
