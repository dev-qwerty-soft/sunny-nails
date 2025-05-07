<?php

/**
 * Enhanced Altegio Services Integration
 * 
 * This file provides functions for synchronizing categories, services, and masters with WordPress taxonomies and custom post types.
 */


/**
 * Synchronize categories from Altegio API
 * 
 * @return int Number of categories synchronized
 */
function sync_altegio_categories()
{
    // Load Altegio client if not already loaded
    if (!class_exists('AltegioClient')) {
        require_once __DIR__ . '/../api/altegio-client.php';
    }

    // Get categories from API
    $categories = AltegioClient::getCategories();

    // Check if request was successful
    if (!isset($categories['success']) || $categories['success'] != 1) {
        error_log('Failed to fetch categories from Altegio API');
        return 0;
    }

    // Check if data exists
    if (!isset($categories['data']) || !is_array($categories['data'])) {
        error_log('No category data found in Altegio API response');
        return 0;
    }

    $counter = 0;
    $category_mapping = [];

    // Process each category
    foreach ($categories['data'] as $category) {
        // Extract necessary data with safe defaults
        $altegio_id = isset($category['category_id']) ? sanitize_text_field($category['category_id']) : '';
        $name = isset($category['title']) ? sanitize_text_field($category['title']) : '';
        $parent_id = isset($category['parent_id']) ? sanitize_text_field($category['parent_id']) : '';

        // Skip if no ID or name
        if (empty($altegio_id) || empty($name)) {
            continue;
        }

        // Check if this category already exists
        $existing_terms = get_terms([
            'taxonomy' => 'service_category',
            'meta_key' => '_altegio_category_id',
            'meta_value' => $altegio_id,
            'hide_empty' => false,
        ]);

        if (!empty($existing_terms) && !is_wp_error($existing_terms)) {
            // Update existing term
            $term_id = $existing_terms[0]->term_id;
            wp_update_term($term_id, 'service_category', [
                'name' => $name,
            ]);
        } else {
            // Create new term
            $result = wp_insert_term($name, 'service_category');

            if (!is_wp_error($result)) {
                $term_id = $result['term_id'];

                // Store the Altegio ID as term meta
                update_term_meta($term_id, '_altegio_category_id', $altegio_id);

                // Store parent ID for later processing
                if (!empty($parent_id)) {
                    $category_mapping[$altegio_id] = [
                        'term_id' => $term_id,
                        'parent_id' => $parent_id
                    ];
                }

                $counter++;
            } else {
                error_log('Error creating category term: ' . $result->get_error_message());
            }
        }
    }

    // Now process parent-child relationships after all terms are created
    foreach ($category_mapping as $altegio_id => $data) {
        $parent_altegio_id = $data['parent_id'];

        // Find the WordPress term ID for the parent
        $parent_terms = get_terms([
            'taxonomy' => 'service_category',
            'meta_key' => '_altegio_category_id',
            'meta_value' => $parent_altegio_id,
            'hide_empty' => false,
        ]);

        if (!empty($parent_terms) && !is_wp_error($parent_terms)) {
            $parent_term_id = $parent_terms[0]->term_id;

            // Update term with parent
            wp_update_term($data['term_id'], 'service_category', [
                'parent' => $parent_term_id
            ]);
        }
    }

    error_log('Altegio category sync completed. Processed ' . $counter . ' categories.');

    return $counter;
}

/**
 * Synchronize masters from Altegio API
 * 
 * @return int Number of masters synchronized
 */
function sync_altegio_masters()
{
    if (!class_exists('AltegioClient')) {
        require_once __DIR__ . '/../api/altegio-client.php';
    }

    $staff = AltegioClient::getStaff();
    if (!isset($staff['success']) || !$staff['success'] || !isset($staff['data'])) {
        error_log('Failed to fetch staff from Altegio API');
        return 0;
    }

    $counter = 0;

    foreach ($staff['data'] as $member) {
        $altegio_id = sanitize_text_field($member['id'] ?? '');
        $name = sanitize_text_field($member['name'] ?? '');
        $photo = esc_url_raw($member['avatar_big'] ?? '');
        $level = sanitize_text_field($member['position'] ?? '');
        $description = sanitize_textarea_field($member['information'] ?? '');
        $instagram = esc_url_raw($member['instagram_url'] ?? '');
        $specialization = sanitize_text_field($member['specialization'] ?? '');
        $services_links = isset($member['services_links']) ? $member['services_links'] : [];

        if (empty($altegio_id) || empty($name)) continue;

        $existing = get_posts([
            'post_type' => 'master',
            'meta_key' => 'altegio_id',
            'meta_value' => $altegio_id,
            'posts_per_page' => 1,
        ]);

        if (!empty($existing)) {
            $post_id = $existing[0]->ID;
            wp_update_post(['ID' => $post_id, 'post_title' => $name]);
        } else {
            $post_id = wp_insert_post([
                'post_type' => 'master',
                'post_title' => $name,
                'post_status' => 'publish',
            ]);
        }

        if (!is_wp_error($post_id) && $post_id > 0) {
            update_post_meta($post_id, 'altegio_id', $altegio_id);
            update_post_meta($post_id, 'master_level', $level);
            update_post_meta($post_id, 'description', $description);
            update_post_meta($post_id, 'instagram_url', $instagram);
            update_post_meta($post_id, 'specialization', $specialization);

            if (!empty($services_links)) {
                $service_ids = array_map(function ($link) {
                    return isset($link['service_id']) ? $link['service_id'] : null;
                }, $services_links);
                $service_ids = array_filter($service_ids);
                update_post_meta($post_id, 'service_ids', $service_ids);
            }

            if (!empty($photo)) {
                $attachment_id = media_sideload_image($photo, $post_id, null, 'id');
                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($post_id, $attachment_id);
                }
            }

            $counter++;
        }
    }

    error_log("Altegio master sync completed. Total: $counter");
    return $counter;
}

/**
 * Synchronize services from Altegio API
 * 
 * @return int Number of services synchronized
 */
function sync_altegio_services()
{
    if (!class_exists('AltegioClient')) {
        require_once __DIR__ . '/../api/altegio-client.php';
    }

    $services = AltegioClient::getServices();
    if (!isset($services['success']) || $services['success'] != 1) {
        error_log('Failed to fetch services from Altegio API');
        return 0;
    }

    if (!isset($services['data']) || !is_array($services['data'])) {
        error_log('No data found in Altegio API response');
        return 0;
    }

    $counter = 0;

    foreach ($services['data'] as $item) {
        $altegio_id = isset($item['id']) ? sanitize_text_field($item['id']) : '';
        $title = isset($item['title']) ? sanitize_text_field($item['title']) : '';
        $description = isset($item['comment']) ? wp_kses_post($item['comment']) : '';
        $price_min = isset($item['price_min']) ? floatval($item['price_min']) : 0;
        $price_max = isset($item['price_max']) ? floatval($item['price_max']) : 0;
        $duration = isset($item['duration']) ? intval($item['duration']) : 0;
        $category_id = isset($item['category_id']) ? sanitize_text_field($item['category_id']) : '';
        $master_photo = isset($item['master_photo']) ? esc_url_raw($item['master_photo']) : '';
        $master_level = isset($item['master_level']) ? sanitize_text_field($item['master_level']) : '';
        $instagram_url = isset($item['instagram_url']) ? esc_url_raw($item['instagram_url']) : '';

        if (empty($title) || empty($altegio_id)) {
            continue;
        }

        $existing_posts = get_posts([
            'post_type' => 'service',
            'meta_key' => '_altegio_id',
            'meta_value' => $altegio_id,
            'posts_per_page' => 1,
        ]);

        if (!empty($existing_posts)) {
            $post_id = $existing_posts[0]->ID;
            $update_data = [
                'ID' => $post_id,
                'post_title' => wp_strip_all_tags($title),
                'post_content' => $description,
            ];
            wp_update_post($update_data);
        } else {
            $post_id = wp_insert_post([
                'post_type' => 'service',
                'post_title' => wp_strip_all_tags($title),
                'post_content' => $description,
                'post_status' => 'publish',
            ]);
        }

        if (!is_wp_error($post_id) && $post_id > 0) {
            update_post_meta($post_id, '_altegio_id', $altegio_id);
            update_post_meta($post_id, 'base_price', $price_min);
            update_post_meta($post_id, 'price_min', $price_min);
            update_post_meta($post_id, 'price_max', $price_max);
            update_post_meta($post_id, 'description', $description);
            update_post_meta($post_id, 'local_category', $category_id);
            update_post_meta($post_id, 'master_photo', $master_photo);
            update_post_meta($post_id, 'master_level', $master_level);
            update_post_meta($post_id, 'instagram_url', $instagram_url);

            $duration_minutes = round($duration / 60);
            update_post_meta($post_id, '_duration', $duration);
            update_post_meta($post_id, 'duration_minutes', $duration_minutes);

            update_post_meta($post_id, 'is_online', isset($item['is_online']) ? (int)$item['is_online'] : 0);
            update_post_meta($post_id, 'discount', isset($item['discount']) ? (float)$item['discount'] : 0);
            update_post_meta($post_id, 'weight', isset($item['weight']) ? (int)$item['weight'] : 0);
            update_post_meta($post_id, 'service_type', isset($item['service_type']) ? (int)$item['service_type'] : 0);

            update_post_meta($post_id, 'booking_title', isset($item['booking_title']) ? sanitize_text_field($item['booking_title']) : '');
            update_post_meta($post_id, 'print_title', isset($item['print_title']) ? sanitize_text_field($item['print_title']) : '');
            update_post_meta($post_id, 'original_title', isset($item['original_title']) ? sanitize_text_field($item['original_title']) : '');

            update_post_meta($post_id, 'currency', 'SGD');

            if (isset($item['staff']) && is_array($item['staff'])) {
                $master_ids = array_map(function ($staff) {
                    return isset($staff['id']) ? $staff['id'] : null;
                }, $item['staff']);
                $master_ids = array_filter($master_ids);
                update_post_meta($post_id, 'master_ids', $master_ids);
            }

            if (!empty($category_id)) {
                $category_terms = get_terms([
                    'taxonomy' => 'service_category',
                    'meta_key' => '_altegio_category_id',
                    'meta_value' => $category_id,
                    'hide_empty' => false,
                ]);

                if (!empty($category_terms) && !is_wp_error($category_terms)) {
                    wp_set_object_terms($post_id, $category_terms[0]->term_id, 'service_category');
                }
            }

            $counter++;
        } else {
            if (is_wp_error($post_id)) {
                error_log('Error creating service post: ' . $post_id->get_error_message());
            } else {
                error_log('Unknown error creating service post');
            }
        }
    }

    error_log('Altegio sync completed. Processed ' . $counter . ' services.');

    return $counter;
}

/**
 * Run a complete synchronization (categories, services, and masters)
 * 
 * @return array Synchronization results
 */
function run_complete_altegio_sync()
{
    $categories_count = sync_altegio_categories();
    $services_count = sync_altegio_services();
    $masters_count = sync_altegio_masters();

    return [
        'categories' => $categories_count,
        'services' => $services_count,
        'masters' => $masters_count
    ];
}

/**
 * Add admin page for synchronization
 */
add_action('admin_menu', 'altegio_sync_menu');
function altegio_sync_menu()
{
    add_submenu_page(
        'tools.php',
        'Altegio Synchronization',
        'Altegio Sync',
        'manage_options',
        'altegio-sync',
        'altegio_sync_page'
    );
}

/**
 * Admin page content for synchronization
 */
function altegio_sync_page()
{
    echo '<div class="wrap">';
    echo '<h1>Altegio Service Synchronization</h1>';

    if (isset($_POST['sync_now']) && check_admin_referer('altegio_sync_nonce')) {
        $sync_type = isset($_POST['sync_type']) ? sanitize_text_field($_POST['sync_type']) : 'all';

        if ($sync_type === 'categories') {
            $count = sync_altegio_categories();
            echo '<div class="notice notice-success"><p>Synchronized ' . $count . ' categories.</p></div>';
        } elseif ($sync_type === 'services') {
            $count = sync_altegio_services();
            echo '<div class="notice notice-success"><p>Synchronized ' . $count . ' services.</p></div>';
        } elseif ($sync_type === 'masters') {
            $count = sync_altegio_masters();
            echo '<div class="notice notice-success"><p>Synchronized ' . $count . ' masters.</p></div>';
        } else {
            $result = run_complete_altegio_sync();
            echo '<div class="notice notice-success"><p>Synchronized ' . $result['categories'] . ' categories, ' . $result['services'] . ' services, and ' . $result['masters'] . ' masters.</p></div>';
        }
    }

    echo '<form method="post">';
    wp_nonce_field('altegio_sync_nonce');

    echo '<h2>Synchronization Options</h2>';
    echo '<p>Select what data you want to synchronize from the Altegio API:</p>';

    echo '<p>';
    echo '<label><input type="radio" name="sync_type" value="all" checked> Synchronize All Data</label><br>';
    echo '<label><input type="radio" name="sync_type" value="categories"> Synchronize Categories Only</label><br>';
    echo '<label><input type="radio" name="sync_type" value="services"> Synchronize Services Only</label><br>';
    echo '<label><input type="radio" name="sync_type" value="masters"> Synchronize Masters Only</label>';
    echo '</p>';

    echo '<p><input type="submit" name="sync_now" class="button button-primary" value="Start Synchronization"></p>';
    echo '</form>';

    $cat_count = wp_count_terms('service_category', ['hide_empty' => false]);
    $service_count = wp_count_posts('service');
    $master_count = wp_count_posts('master');

    echo '<div class="altegio-stats" style="margin-top: 30px; padding: 20px; background: #f8f8f8; border-radius: 5px;">';
    echo '<h2>Current Data Statistics</h2>';
    echo '<p><strong>Categories:</strong> ' . (is_wp_error($cat_count) ? '0' : $cat_count) . '</p>';
    echo '<p><strong>Services:</strong> ' . (isset($service_count->publish) ? $service_count->publish : '0') . '</p>';
    echo '<p><strong>Masters:</strong> ' . (isset($master_count->publish) ? $master_count->publish : '0') . '</p>';
    echo '</div>';

    echo '</div>';
}

/**
 * Register AJAX endpoints for synchronization
 */
add_action('wp_ajax_altegio_sync_categories', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    $count = sync_altegio_categories();
    wp_send_json_success(['count' => $count]);
});

add_action('wp_ajax_altegio_sync_services', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    $count = sync_altegio_services();
    wp_send_json_success(['count' => $count]);
});

add_action('wp_ajax_altegio_sync_masters', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    $count = sync_altegio_masters();
    wp_send_json_success(['count' => $count]);
});

/**
 * Enqueue admin scripts
 */
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'tools_page_altegio-sync') {
        return;
    }

    wp_enqueue_style(
        'altegio-admin-styles',
        plugin_dir_url(__FILE__) . 'css/altegio-admin.css',
        [],
        '1.0.0'
    );
});
