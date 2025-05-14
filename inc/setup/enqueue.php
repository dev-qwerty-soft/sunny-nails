<?php
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('style', get_template_directory_uri() . '/dist/main.css');

    wp_enqueue_script(
        'index',
        get_template_directory_uri() . '/dist/main.bundle.js',
        ['jquery'],
        null,
        true
    );

    wp_localize_script('index', 'booking_params', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('booking_nonce'),
    ]);
});
