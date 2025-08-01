<?php

// ACF: Options page + JSON sync
add_action('acf/init', function () {
  if (function_exists('acf_add_options_page')) {
    acf_add_options_page([
      'page_title' => __('General theme settings'),
      'menu_title' => __('Theme settings'),
      'menu_slug' => 'theme-general-settings',
      'capability' => 'edit_posts',
      'redirect' => false,
    ]);
  }
});
add_filter('acf/settings/save_json', 'my_acf_json_save_point');
function my_acf_json_save_point($path) {
  return get_stylesheet_directory() . '/acf-json';
}

add_filter('acf/settings/load_json', 'my_acf_json_load_point');
function my_acf_json_load_point($paths) {
  $paths[] = get_stylesheet_directory() . '/acf-json';
  return $paths;
}
// Allow SVG upload
add_filter('upload_mimes', function ($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
});

// Setup
require_once get_template_directory() . '/inc/setup/enqueue.php';
require_once get_template_directory() . '/inc/setup/theme-support.php';
require_once get_template_directory() . '/inc/setup/menus.php';

// Altegio integration
require_once get_template_directory() . '/inc/cpt/master.php';
require_once get_template_directory() . '/inc/cpt/service.php';
require_once get_template_directory() . '/inc/cpt/partners.php';
require_once get_template_directory() . '/inc/sync/sync-runner.php';
require_once get_template_directory() . '/inc/admin/altegio-sync-page.php';
require_once get_template_directory() . '/inc/admin/altegio-cron-sync.php';
require_once get_template_directory() . '/inc/api/altegio-client.php';
require_once get_template_directory() . '/inc/helpers/api.php';
require_once get_template_directory() . '/inc/controllers/booking-controller.php';
require_once get_template_directory() . '/inc/controllers/booking-popup-controller.php';
require_once get_template_directory() . '/inc/controllers/booking-filter-controller.php';

require_once get_template_directory() . '/inc/ajax/booking-ajax-handlers.php';
// require_once get_template_directory() . '/inc/ajax/ajax-handlers-php.php';

// google reviews integration
require_once get_template_directory() . '/inc/admin/google.php';

// Initialize controllers
add_action('after_setup_theme', ['BookingController', 'init']);
add_action('after_setup_theme', ['BookingPopupController', 'init']);
add_action('after_setup_theme', ['BookingFilterController', 'init']);

add_action('login_enqueue_scripts', function () {
  wp_enqueue_style('login-css', getUrl('login/style.css'), false, '1.0.0');
});

add_action('admin_head', function () {
  echo '<link rel="icon" href="' .
    getAssetUrlAcf('favicon_black_theme') .
    '" media="(prefers-color-scheme: dark)">';
  echo '<link rel="icon" href="' .
    getAssetUrlAcf('favicon_light_theme') .
    '" media="(prefers-color-scheme: light)">';
});
