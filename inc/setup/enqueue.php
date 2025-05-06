<?php
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('style', get_template_directory_uri() . '/dist/main.css');
    wp_enqueue_script('index', get_template_directory_uri() . '/dist/main.bundle.js', [], null, true);
    wp_enqueue_script('ajax-search', get_template_directory_uri() . '/ajax-search.js', [], null, true);
});
