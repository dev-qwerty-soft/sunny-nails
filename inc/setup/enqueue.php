<?php
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('style', cache_busted_url('dist/main.css'));

  wp_enqueue_script(
    'index',
    cache_busted_url('dist/main.bundle.js'),
    ['jquery'],
    null,
    true,
  );

  wp_enqueue_script(
    'booking-js',
    cache_busted_url('ajax-booking.js'),
    ['jquery'],
    null,
    true,
  );

  wp_enqueue_script(
    'ajax-services-teem',
    cache_busted_url('ajax-services-teem.js'),
    ['jquery'],
    null,
    true,
  );

  wp_enqueue_script(
    'partners-form-js',
    cache_busted_url('assets/js/partners-form.js'),
    ['jquery'],
    null,
    true,
  );

  wp_localize_script('ajax-services-teem', 'services_page_params', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('services_page_nonce'),
  ]);

  wp_localize_script('booking-js', 'booking_params', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('booking_nonce'),
  ]);
});
