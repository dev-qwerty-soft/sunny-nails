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
function my_acf_json_save_point($path)
{
  return get_stylesheet_directory() . '/acf-json';
}

add_filter('acf/settings/load_json', 'my_acf_json_load_point');
function my_acf_json_load_point($paths)
{
  $paths[] = get_stylesheet_directory() . '/acf-json';
  return $paths;
}
// Allow SVG upload
add_filter('upload_mimes', function ($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
});

/**
 * Generate cache-busted URL for CSS/JS assets
 *
 * @param string $relative_path Relative path to the asset file.
 * @return string Cache-busted URL with file modification time.
 */
function cache_busted_url($relative_path)
{
  $file_path = get_template_directory() . '/' . ltrim($relative_path, '/');
  $file_url = get_template_directory_uri() . '/' . ltrim($relative_path, '/');

  if (file_exists($file_path)) {
    return $file_url . '?v=' . filemtime($file_path);
  }

  return $file_url;
}

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
require_once get_template_directory() . '/inc/helpers/master-levels.php';
require_once get_template_directory() . '/inc/helpers/benefit-icons.php';
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

add_action('wp_head', function () {
  $favicon_dark = getAssetUrlAcf('favicon_black_theme');
  $favicon_light = getAssetUrlAcf('favicon_light_theme');

  if ($favicon_dark) {
    echo '<link rel="icon" href="' . $favicon_dark . '" media="(prefers-color-scheme: dark)">';
  }
  if ($favicon_light) {
    echo '<link rel="icon" href="' . $favicon_light . '" media="(prefers-color-scheme: light)">';
  }
});

// TEMPORARY: Auto-setup benefit icons (comment back after setup)
/*
add_action('init', function() {
    // Reset setup to allow reconfiguration
    delete_option('benefit_icons_setup_done');
    
    if (!get_option('benefit_icons_setup_done')) {
        // Find icons by filename in uploads
        $discount_icon = get_posts(array(
            'post_type' => 'attachment',
            'meta_query' => array(
                array(
                    'key' => '_wp_attached_file',
                    'value' => 'benefit-discount-icon.svg',
                    'compare' => 'LIKE'
                )
            ),
            'posts_per_page' => 1
        ));
        
        $complimentary_icon = get_posts(array(
            'post_type' => 'attachment',
            'meta_query' => array(
                array(
                    'key' => '_wp_attached_file',
                    'value' => 'benefit-complimentary-icon.svg',
                    'compare' => 'LIKE'
                )
            ),
            'posts_per_page' => 1
        ));
        
        $gift_icon = get_posts(array(
            'post_type' => 'attachment',
            'meta_query' => array(
                array(
                    'key' => '_wp_attached_file',
                    'value' => 'benefit-gift-icon.svg',
                    'compare' => 'LIKE'
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (!empty($discount_icon)) {
            update_option('benefit_discount_icon_id', $discount_icon[0]->ID);
        }
        if (!empty($complimentary_icon)) {
            update_option('benefit_complimentary_icon_id', $complimentary_icon[0]->ID);
        }
        if (!empty($gift_icon)) {
            update_option('benefit_gift_icon_id', $gift_icon[0]->ID);
        }
        
        update_option('benefit_icons_setup_done', true);
        
        // Show debug info
        add_action('wp_footer', function() {
            if (current_user_can('manage_options')) {
                echo '<!-- Benefit Icons Setup Complete -->';
                echo '<!-- Discount: ' . get_option('benefit_discount_icon_id') . ' -->';
                echo '<!-- Complimentary: ' . get_option('benefit_complimentary_icon_id') . ' -->';
                echo '<!-- Gift: ' . get_option('benefit_gift_icon_id') . ' -->';
            }
        });
    }
});
*/

// Enqueue apply form script with localization
add_action('wp_enqueue_scripts', function () {
  // Check if we're on the apply page
  if (is_page() && get_page_template_slug() === 'template-page/apply.php') {
    wp_enqueue_script(
      'apply-form-script',
      get_template_directory_uri() . '/assets/js/apply-form.js',
      [],
      filemtime(get_template_directory() . '/assets/js/apply-form.js'),
      true
    );

    wp_localize_script('apply-form-script', 'apply_ajax', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('sunny_partner_apply')
    ));
  }
});

// AJAX handler for partner application submission
add_action('wp_ajax_submit_partner_application', 'handle_partner_application_submission');
add_action('wp_ajax_nopriv_submit_partner_application', 'handle_partner_application_submission');

function handle_partner_application_submission()
{
  // Check nonce
  if (!wp_verify_nonce($_POST['nonce'], 'sunny_partner_apply')) {
    wp_send_json_error('Security check failed');
  }

  // Validate required fields
  $required_fields = [
    'partner_title' => 'Partner Title',
    'partner_description' => 'Partner Description',
    'benefit_title' => 'Benefit Title',
    'benefit_description' => 'Benefit Description',
    'benefit_icon_type' => 'Benefit Icon Type'
  ];

  $errors = [];
  foreach ($required_fields as $field => $label) {
    if (empty($_POST[$field])) {
      $errors[] = $label . ' is required';
    }
  }

  if (!empty($errors)) {
    wp_send_json_error(implode(', ', $errors));
  }

  // Clean up: Delete ALL existing posts with the same title before creating new one
  $partner_title = sanitize_text_field($_POST['partner_title']);

  // Find all posts with same title and delete them all
  $existing_posts = get_posts(array(
    'post_type' => 'partner',
    'post_status' => array('pending', 'publish', 'draft'),
    'title' => $partner_title,
    'posts_per_page' => -1 // Get all posts
  ));

  // Delete all existing posts with same title to ensure only one will exist
  if (!empty($existing_posts)) {
    foreach ($existing_posts as $existing_post) {
      wp_delete_post($existing_post->ID, true); // Force permanent deletion
    }
  }

  // Create new partner post
  $post_data = array(
    'post_title'    => $partner_title,
    'post_content'  => sanitize_textarea_field($_POST['partner_description']),
    'post_status'   => 'pending',
    'post_type'     => 'partner'
  );

  $post_id = wp_insert_post($post_data);

  if (is_wp_error($post_id)) {
    wp_send_json_error('Failed to create partner application');
  }

  // Save ACF fields
  $benefit_icon_type = sanitize_text_field($_POST['benefit_icon_type']);

  // Get media library ID for the selected icon type
  $icon_id = null;
  switch ($benefit_icon_type) {
    case 'discount':
      $icon_id = get_option('benefit_discount_icon_id');
      break;
    case 'complimentary':
      $icon_id = get_option('benefit_complimentary_icon_id');
      break;
    case 'gift':
      $icon_id = get_option('benefit_gift_icon_id');
      break;
  }

  // If icon not found in options, try to create it from SVG or use fallback
  if (!$icon_id) {
    // Try to find icon in media library directly by filename
    $icon_posts = get_posts(array(
      'post_type' => 'attachment',
      'meta_query' => array(
        array(
          'key' => '_wp_attached_file',
          'value' => 'benefit-' . $benefit_icon_type . '-icon.svg',
          'compare' => 'LIKE'
        )
      ),
      'posts_per_page' => 1
    ));

    if (!empty($icon_posts)) {
      $icon_id = $icon_posts[0]->ID;
      // Cache for future use
      update_option('benefit_' . $benefit_icon_type . '_icon_id', $icon_id);
    }
  }

  $acf_fields = array(
    'partner_description' => sanitize_textarea_field($_POST['partner_description']),
    'partner_benefit_title' => sanitize_text_field($_POST['benefit_title']),
    'partner_benefit_description' => sanitize_textarea_field($_POST['benefit_description']),
    'partner_benefit_icon' => $icon_id, // Save media library ID instead of type
    'partners_link_card' => sanitize_text_field($_POST['link_card'] ?? ''),
    'partners_link_popup' => sanitize_text_field($_POST['link_popup'] ?? ''),
  );

  foreach ($acf_fields as $key => $value) {
    update_field($key, $value, $post_id);
  }

  // Handle file upload
  if (!empty($_FILES['partner_photo']) && $_FILES['partner_photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['partner_photo'];

    // Handle the upload
    $uploaded_image = wp_handle_upload($file, array('test_form' => false));
    if (!is_wp_error($uploaded_image) && !isset($uploaded_image['error'])) {
      $image_id = wp_insert_attachment(array(
        'post_mime_type' => $uploaded_image['type'],
        'post_title' => sanitize_file_name(pathinfo($uploaded_image['file'], PATHINFO_FILENAME)),
        'post_content' => '',
        'post_status' => 'inherit'
      ), $uploaded_image['file']);

      if (!is_wp_error($image_id)) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        wp_generate_attachment_metadata($image_id, $uploaded_image['file']);

        // Set as featured image for the partner post
        set_post_thumbnail($post_id, $image_id);
      }
    }
  }

  wp_send_json_success(array(
    'message' => 'Your partner application has been submitted successfully! We will review it and get back to you soon.',
    'post_id' => $post_id
  ));
}
