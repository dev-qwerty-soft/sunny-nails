<?php

/**
 * Partner Application AJAX Handler
 * Handles partner application form submissions
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
    if (!wp_verify_nonce($_POST['nonce'], 'sunny_partner_apply')) {
        wp_send_json_error('Security check failed');
    }

    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $partner_title = sanitize_text_field($_POST['partner_title']);

    // Create multiple session keys for stronger duplicate prevention
    $session_key = 'partner_submit_' . md5($user_ip . $partner_title);
    $user_session_key = 'partner_user_' . md5($user_ip);
    $title_session_key = 'partner_title_' . md5($partner_title);

    // Check for any existing submissions
    if (get_transient($session_key) || get_transient($user_session_key) || get_transient($title_session_key)) {
        wp_send_json_error('Please wait before submitting another application');
    }

    // Set multiple locks for 60 seconds
    set_transient($session_key, time(), 60);
    set_transient($user_session_key, time(), 60);
    set_transient($title_session_key, time(), 60);

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
        // Remove locks on validation error
        delete_transient($session_key);
        delete_transient($user_session_key);
        delete_transient($title_session_key);
        wp_send_json_error(implode(', ', $errors));
    }

    global $wpdb;

    // Use database locks to prevent race conditions
    $lock_name = 'partner_application_' . md5($partner_title);
    $lock_acquired = $wpdb->get_var($wpdb->prepare("SELECT GET_LOCK(%s, 10)", $lock_name));

    if (!$lock_acquired) {
        delete_transient($session_key);
        delete_transient($user_session_key);
        delete_transient($title_session_key);
        wp_send_json_error('System is busy, please try again');
    }

    // Start database transaction
    $wpdb->query('START TRANSACTION');

    try {
        // Double-check for existing posts with same title
        $existing_posts = $wpdb->get_results($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'partner' AND post_status IN ('pending', 'publish', 'draft')",
            $partner_title
        ));

        if ($existing_posts) {
            // If posts exist, we might be dealing with a duplicate
            foreach ($existing_posts as $post) {
                $post_time = get_post_time('U', false, $post->ID);
                // If post was created within last 5 minutes, likely duplicate
                if (time() - $post_time < 300) {
                    throw new Exception('Application with this title was recently submitted');
                }
                wp_delete_post($post->ID, true);
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
            throw new Exception('Failed to create post');
        }

        // Handle benefit icon selection
        $benefit_icon_type = sanitize_text_field($_POST['benefit_icon_type']);
        $icon_id = get_benefit_icon_id($benefit_icon_type);

        // Save ACF fields
        $acf_fields = array(
            'partner_description' => sanitize_textarea_field($_POST['partner_description']),
            'partner_benefit_title' => sanitize_text_field($_POST['benefit_title']),
            'partner_benefit_description' => sanitize_textarea_field($_POST['benefit_description']),
            'partner_benefit_icon' => $icon_id,
            'partners_link_card' => sanitize_text_field($_POST['link_card'] ?? ''),
            'partners_link_popup' => sanitize_text_field($_POST['link_popup'] ?? ''),
        );

        foreach ($acf_fields as $key => $value) {
            update_field($key, $value, $post_id);
        }

        // Handle file upload
        if (!empty($_FILES['partner_photo']) && $_FILES['partner_photo']['error'] === UPLOAD_ERR_OK) {
            $image_id = handle_partner_photo_upload($_FILES['partner_photo']);
            if ($image_id) {
                set_post_thumbnail($post_id, $image_id);
            }
        }

        // Commit transaction
        $wpdb->query('COMMIT');

        // Release database lock
        $wpdb->get_var($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $lock_name));

        wp_send_json_success(array(
            'message' => 'Your partner application has been submitted successfully! We will review it and get back to you soon.',
            'post_id' => $post_id
        ));
    } catch (Exception $e) {
        // Rollback transaction on error
        $wpdb->query('ROLLBACK');

        // Release database lock
        $wpdb->get_var($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $lock_name));

        // Remove session locks on error
        delete_transient($session_key);
        delete_transient($user_session_key);
        delete_transient($title_session_key);

        wp_send_json_error('Failed to create partner application: ' . $e->getMessage());
    }
}

/**
 * Get benefit icon ID by type
 */
function get_benefit_icon_id($type)
{
    $icon_mapping = [
        'discount' => get_option('benefit_discount_icon_id'),
        'complimentary' => get_option('benefit_complimentary_icon_id'),
        'gift' => get_option('benefit_gift_icon_id')
    ];

    $icon_id = $icon_mapping[$type] ?? null;

    // If not found in options, search media library
    if (!$icon_id) {
        $icon_posts = get_posts(array(
            'post_type' => 'attachment',
            'meta_query' => array(
                array(
                    'key' => '_wp_attached_file',
                    'value' => 'benefit-' . $type . '-icon.svg',
                    'compare' => 'LIKE'
                )
            ),
            'posts_per_page' => 1
        ));

        if (!empty($icon_posts)) {
            $icon_id = $icon_posts[0]->ID;
            // Cache for future use
            update_option('benefit_' . $type . '_icon_id', $icon_id);
        }
    }

    return $icon_id;
}

/**
 * Handle partner photo upload
 */
function handle_partner_photo_upload($file)
{
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
            return $image_id;
        }
    }

    return false;
}
