<?php

/**
 * Simple fix for Altegio synchronization
 * Add this code to functions.php
 */

/**
 * Enhanced service synchronization with forced update
 */
function enhanced_sync_altegio_services()
{
    if (!class_exists('AltegioClient')) {
        return ['created' => 0, 'updated' => 0, 'errors' => 0];
    }

    $services = AltegioClient::getServices();

    if (!isset($services['success']) || !$services['success']) {
        error_log('Failed to fetch services from Altegio API');
        return ['created' => 0, 'updated' => 0, 'errors' => 0];
    }

    $stats = ['created' => 0, 'updated' => 0, 'errors' => 0];
    $altegio_service_ids = []; // To track existing services in Altegio

    foreach ($services['data'] as $service_data) {
        $altegio_id = $service_data['id'];
        $altegio_service_ids[] = $altegio_id;

        // Find existing post
        $existing_posts = get_posts([
            'post_type' => 'service',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'altegio_id',
                    'value' => $altegio_id,
                    'compare' => '='
                ],
                [
                    'key' => '_altegio_id',
                    'value' => $altegio_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1
        ]);

        // Prepare post data
        $post_data = [
            'post_title' => sanitize_text_field($service_data['title'] ?? ''),
            'post_content' => wp_kses_post($service_data['comment'] ?? ''),
            'post_type' => 'service',
            'post_status' => 'publish'
        ];

        // Extract wear time from content
        $wear_time = '';
        if (!empty($service_data['comment'])) {
            if (preg_match('/wear\s+time:?\s*([^\.]+)/i', $service_data['comment'], $matches)) {
                $wear_time = trim($matches[1]);
                $post_data['post_content'] = preg_replace('/wear\s+time:?\s*[^\.]+\.?/i', '', $post_data['post_content']);
                $post_data['post_content'] = trim($post_data['post_content']);
            }
        }

        if (!empty($existing_posts)) {
            // UPDATE existing post (FORCED)
            $post_id = $existing_posts[0]->ID;
            $post_data['ID'] = $post_id;

            $result = wp_update_post($post_data, true);

            if (!is_wp_error($result)) {
                $stats['updated']++;
                error_log("Service FORCE UPDATED: ID {$post_id}, Title: {$service_data['title']}");
            } else {
                $stats['errors']++;
                error_log("Failed to update service: " . $result->get_error_message());
            }
        } else {
            // CREATE new post
            $post_id = wp_insert_post($post_data, true);

            if (!is_wp_error($post_id)) {
                $stats['created']++;
                error_log("Service CREATED: ID {$post_id}, Title: {$service_data['title']}");
            } else {
                $stats['errors']++;
                error_log("Failed to create service: " . $post_id->get_error_message());
            }
        }

        if (!is_wp_error($post_id) && $post_id) {
            // FORCE update all meta fields
            update_post_meta($post_id, 'altegio_id', $altegio_id);
            update_post_meta($post_id, '_altegio_id', $altegio_id);
            update_post_meta($post_id, 'price_min', floatval($service_data['price_min'] ?? 0));
            update_post_meta($post_id, 'price_max', floatval($service_data['price_max'] ?? 0));
            update_post_meta($post_id, 'base_price', floatval($service_data['price_min'] ?? 0));
            update_post_meta($post_id, 'currency', 'SGD');
            update_post_meta($post_id, 'description', sanitize_textarea_field($service_data['comment'] ?? ''));

            if (!empty($wear_time)) {
                update_post_meta($post_id, 'wear_time', sanitize_text_field($wear_time));
            }

            if (isset($service_data['duration'])) {
                $duration_seconds = intval($service_data['duration']);
                $duration_minutes = round($duration_seconds / 60);
                update_post_meta($post_id, 'duration_minutes', $duration_minutes);
                update_post_meta($post_id, '_duration', $duration_seconds);
            }

            // Update via ACF if available
            if (function_exists('update_field')) {
                update_field('price_min', floatval($service_data['price_min'] ?? 0), $post_id);
                update_field('price_max', floatval($service_data['price_max'] ?? 0), $post_id);
                update_field('currency', 'SGD', $post_id);
                if (!empty($wear_time)) {
                    update_field('wear_time', $wear_time, $post_id);
                }
                if (isset($service_data['duration'])) {
                    update_field('duration_minutes', round($service_data['duration'] / 60), $post_id);
                }
            }
        }
    }

    // DELETE services that no longer exist in Altegio
    $wp_services_with_altegio_id = get_posts([
        'post_type' => 'service',
        'posts_per_page' => -1,
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => 'altegio_id',
                'compare' => 'EXISTS'
            ],
            [
                'key' => '_altegio_id',
                'compare' => 'EXISTS'
            ]
        ]
    ]);

    foreach ($wp_services_with_altegio_id as $wp_service) {
        $wp_altegio_id = get_post_meta($wp_service->ID, 'altegio_id', true) ?: get_post_meta($wp_service->ID, '_altegio_id', true);

        if ($wp_altegio_id && !in_array($wp_altegio_id, $altegio_service_ids)) {
            wp_delete_post($wp_service->ID, true);
            error_log("Service DELETED: ID {$wp_service->ID} (Altegio ID: {$wp_altegio_id}) - no longer exists in Altegio");
        }
    }

    return $stats;
}

/**
 * Enhanced masters synchronization with forced photo update
 */
function enhanced_sync_altegio_masters()
{
    if (!class_exists('AltegioClient')) {
        return ['created' => 0, 'updated' => 0, 'errors' => 0];
    }

    $staff = AltegioClient::getStaff();

    if (!isset($staff['success']) || !$staff['success']) {
        error_log('Failed to fetch staff from Altegio API');
        return ['created' => 0, 'updated' => 0, 'errors' => 0];
    }

    $stats = ['created' => 0, 'updated' => 0, 'errors' => 0];
    $altegio_master_ids = [];

    foreach ($staff['data'] as $master_data) {
        $altegio_id = $master_data['id'];
        $altegio_master_ids[] = $altegio_id;

        // Find existing post
        $existing_posts = get_posts([
            'post_type' => 'master',
            'meta_query' => [
                [
                    'key' => 'altegio_id',
                    'value' => $altegio_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1
        ]);

        // Prepare post data
        $post_data = [
            'post_title' => sanitize_text_field($master_data['name'] ?? ''),
            'post_content' => wp_kses_post($master_data['information'] ?? ''),
            'post_type' => 'master',
            'post_status' => 'publish'
        ];

        if (!empty($existing_posts)) {
            // UPDATE existing post (FORCED)
            $post_id = $existing_posts[0]->ID;
            $post_data['ID'] = $post_id;

            $result = wp_update_post($post_data, true);

            if (!is_wp_error($result)) {
                $stats['updated']++;
                error_log("Master FORCE UPDATED: ID {$post_id}, Name: {$master_data['name']}");
            } else {
                $stats['errors']++;
                error_log("Failed to update master: " . $result->get_error_message());
            }
        } else {
            // CREATE new post
            $post_id = wp_insert_post($post_data, true);

            if (!is_wp_error($post_id)) {
                $stats['created']++;
                error_log("Master CREATED: ID {$post_id}, Name: {$master_data['name']}");
            } else {
                $stats['errors']++;
                error_log("Failed to create master: " . $post_id->get_error_message());
            }
        }

        if (!is_wp_error($post_id) && $post_id) {
            // FORCE update all meta fields
            update_post_meta($post_id, 'altegio_id', $altegio_id);
            update_post_meta($post_id, 'description', sanitize_textarea_field($master_data['information'] ?? ''));
            update_post_meta($post_id, 'master_level', sanitize_text_field($master_data['specialization'] ?? '1'));
            update_post_meta($post_id, 'is_bookable', !empty($master_data['is_bookable']));

            // FORCE update photo even if it already exists
            $avatar_url = $master_data['avatar_big'] ?? '';
            if (!empty($avatar_url)) {
                // Delete old photo
                $existing_thumbnail_id = get_post_thumbnail_id($post_id);
                if ($existing_thumbnail_id) {
                    wp_delete_attachment($existing_thumbnail_id, true);
                }

                // Upload new photo
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');

                $attachment_id = media_sideload_image($avatar_url, $post_id, null, 'id');

                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($post_id, $attachment_id);
                    error_log("Master avatar FORCE UPDATED: Post ID {$post_id}, Attachment ID {$attachment_id}");
                } else {
                    error_log("Failed to update master avatar: " . $attachment_id->get_error_message());
                }
            }

            // Update via ACF if available
            if (function_exists('update_field')) {
                update_field('master_level', $master_data['specialization'] ?? '1', $post_id);
                update_field('is_bookable', !empty($master_data['is_bookable']), $post_id);
            }
        }
    }

    // DELETE masters that no longer exist in Altegio
    $wp_masters_with_altegio_id = get_posts([
        'post_type' => 'master',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'altegio_id',
                'compare' => 'EXISTS'
            ]
        ]
    ]);

    foreach ($wp_masters_with_altegio_id as $wp_master) {
        $wp_altegio_id = get_post_meta($wp_master->ID, 'altegio_id', true);

        if ($wp_altegio_id && !in_array($wp_altegio_id, $altegio_master_ids)) {
            wp_delete_post($wp_master->ID, true);
            error_log("Master DELETED: ID {$wp_master->ID} (Altegio ID: {$wp_altegio_id}) - no longer exists in Altegio");
        }
    }

    return $stats;
}

/**
 * Enhanced categories synchronization
 */
function enhanced_sync_altegio_categories()
{
    if (!class_exists('AltegioClient')) {
        return ['created' => 0, 'updated' => 0, 'errors' => 0];
    }

    $categories = AltegioClient::getServiceCategories();

    if (!isset($categories['success']) || !$categories['success']) {
        error_log('Failed to fetch categories from Altegio API');
        return ['created' => 0, 'updated' => 0, 'errors' => 0];
    }

    $stats = ['created' => 0, 'updated' => 0, 'errors' => 0];
    $altegio_category_ids = [];

    foreach ($categories['data'] as $category_data) {
        $altegio_id = $category_data['id'];
        $altegio_category_ids[] = $altegio_id;
        $title = $category_data['title'] ?? '';

        if (empty($title)) continue;

        // Find existing term
        $existing_terms = get_terms([
            'taxonomy' => 'service_category',
            'meta_key' => '_altegio_category_id',
            'meta_value' => $altegio_id,
            'hide_empty' => false,
        ]);

        if (!empty($existing_terms) && !is_wp_error($existing_terms)) {
            // UPDATE existing term
            $term_id = $existing_terms[0]->term_id;

            $result = wp_update_term($term_id, 'service_category', [
                'name' => sanitize_text_field($title),
                'description' => sanitize_textarea_field($category_data['description'] ?? '')
            ]);

            if (!is_wp_error($result)) {
                $stats['updated']++;
                error_log("Category FORCE UPDATED: ID {$term_id}, Title: {$title}");
            } else {
                $stats['errors']++;
                error_log("Failed to update category: " . $result->get_error_message());
            }
        } else {
            // CREATE new term
            $result = wp_insert_term(sanitize_text_field($title), 'service_category', [
                'description' => sanitize_textarea_field($category_data['description'] ?? '')
            ]);

            if (!is_wp_error($result)) {
                $term_id = $result['term_id'];
                update_term_meta($term_id, '_altegio_category_id', $altegio_id);
                $stats['created']++;
                error_log("Category CREATED: ID {$term_id}, Title: {$title}");
            } else {
                $stats['errors']++;
                error_log("Failed to create category: " . $result->get_error_message());
            }
        }
    }

    // DELETE categories that no longer exist in Altegio
    $wp_categories_with_altegio_id = get_terms([
        'taxonomy' => 'service_category',
        'hide_empty' => false,
        'meta_query' => [
            [
                'key' => '_altegio_category_id',
                'compare' => 'EXISTS'
            ]
        ]
    ]);

    foreach ($wp_categories_with_altegio_id as $wp_category) {
        $wp_altegio_id = get_term_meta($wp_category->term_id, '_altegio_category_id', true);

        if ($wp_altegio_id && !in_array($wp_altegio_id, $altegio_category_ids)) {
            wp_delete_term($wp_category->term_id, 'service_category');
            error_log("Category DELETED: ID {$wp_category->term_id} (Altegio ID: {$wp_altegio_id}) - no longer exists in Altegio");
        }
    }

    return $stats;
}

/**
 * Replace existing synchronization functions
 */
function run_enhanced_complete_altegio_sync()
{
    $results = [
        'categories' => enhanced_sync_altegio_categories(),
        'services' => enhanced_sync_altegio_services(),
        'masters' => enhanced_sync_altegio_masters()
    ];

    error_log('Enhanced Altegio Sync completed: ' . json_encode($results));
    return $results;
}

/**
 * Add new page to admin menu
 */
add_action('admin_menu', function () {
    add_submenu_page(
        'tools.php',
        'Enhanced Altegio Sync',
        'Enhanced Sync',
        'manage_options',
        'enhanced-altegio-sync',
        'enhanced_altegio_sync_page'
    );
});

/**
 * Admin page for enhanced synchronization
 */
function enhanced_altegio_sync_page()
{
    echo '<div class="wrap">';
    echo '<h1>Enhanced Altegio Synchronization</h1>';
    echo '<p>This enhanced sync will force update all data, including photos and prices, and remove items deleted from Altegio.</p>';

    if (isset($_POST['enhanced_sync_now']) && check_admin_referer('enhanced_altegio_sync_nonce')) {
        $sync_type = sanitize_text_field($_POST['sync_type'] ?? 'all');

        if ($sync_type === 'categories') {
            $result = enhanced_sync_altegio_categories();
            echo '<div class="notice notice-success"><p>Categories sync completed: ' .
                $result['created'] . ' created, ' . $result['updated'] . ' updated, ' . $result['errors'] . ' errors.</p></div>';
        } elseif ($sync_type === 'services') {
            $result = enhanced_sync_altegio_services();
            echo '<div class="notice notice-success"><p>Services sync completed: ' .
                $result['created'] . ' created, ' . $result['updated'] . ' updated, ' . $result['errors'] . ' errors.</p></div>';
        } elseif ($sync_type === 'masters') {
            $result = enhanced_sync_altegio_masters();
            echo '<div class="notice notice-success"><p>Masters sync completed: ' .
                $result['created'] . ' created, ' . $result['updated'] . ' updated, ' . $result['errors'] . ' errors.</p></div>';
        } else {
            $result = run_enhanced_complete_altegio_sync();
            echo '<div class="notice notice-success"><p>Complete sync finished!</p>';
            echo '<ul>';
            echo '<li>Categories: ' . $result['categories']['created'] . ' created, ' . $result['categories']['updated'] . ' updated</li>';
            echo '<li>Services: ' . $result['services']['created'] . ' created, ' . $result['services']['updated'] . ' updated</li>';
            echo '<li>Masters: ' . $result['masters']['created'] . ' created, ' . $result['masters']['updated'] . ' updated</li>';
            echo '</ul></div>';
        }
    }

    echo '<form method="post">';
    wp_nonce_field('enhanced_altegio_sync_nonce');

    echo '<h2>Enhanced Sync Options</h2>';
    echo '<p><strong>⚠️ Warning:</strong> This will force update ALL data and may overwrite manual changes!</p>';

    echo '<p>';
    echo '<label><input type="radio" name="sync_type" value="all" checked> 🔄 Complete Enhanced Sync (Recommended)</label><br>';
    echo '<label><input type="radio" name="sync_type" value="categories"> 📂 Categories Only</label><br>';
    echo '<label><input type="radio" name="sync_type" value="services"> 🛠️ Services Only (with force price update)</label><br>';
    echo '<label><input type="radio" name="sync_type" value="masters"> 👥 Masters Only (with force photo update)</label>';
    echo '</p>';

    echo '<p><input type="submit" name="enhanced_sync_now" class="button button-primary" value="🚀 Start Enhanced Sync" onclick="return confirm(\'This will force update all data and may take several minutes. Continue?\')"></p>';
    echo '</form>';

    echo '</div>';
}

/**
 * Replace functions in old admin page
 */
add_action('wp_ajax_enhanced_altegio_sync_services', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    $result = enhanced_sync_altegio_services();
    wp_send_json_success(['result' => $result]);
});

add_action('wp_ajax_enhanced_altegio_sync_masters', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    $result = enhanced_sync_altegio_masters();
    wp_send_json_success(['result' => $result]);
});

add_action('wp_ajax_enhanced_altegio_sync_categories', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    $result = enhanced_sync_altegio_categories();
    wp_send_json_success(['result' => $result]);
});

/**
 * Automatic synchronization once a day
 */
if (!wp_next_scheduled('enhanced_altegio_daily_sync')) {
    wp_schedule_event(time(), 'daily', 'enhanced_altegio_daily_sync');
}

add_action('enhanced_altegio_daily_sync', 'run_enhanced_complete_altegio_sync');

/**
 * Quick sync button in admin bar
 */
add_action('admin_bar_menu', function ($wp_admin_bar) {
    if (!current_user_can('manage_options')) return;

    $wp_admin_bar->add_node([
        'id' => 'enhanced-altegio-sync',
        'title' => '🔄 Enhanced Sync',
        'href' => admin_url('tools.php?page=enhanced-altegio-sync'),
        'meta' => ['title' => 'Enhanced Altegio Synchronization']
    ]);
}, 100);
