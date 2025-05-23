<?php

/**
 * Enhanced Altegio Sync with FORCED ACF Field Updates
 * 
 * This code ensures ALL ACF fields are force updated even if they already exist
 */

/**
 * Enhanced services sync with FORCED ACF field updates
 */
function enhanced_force_sync_altegio_services()
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
    $altegio_service_ids = [];

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
            // UPDATE existing post
            $post_id = $existing_posts[0]->ID;
            $post_data['ID'] = $post_id;

            $result = wp_update_post($post_data, true);

            if (!is_wp_error($result)) {
                $stats['updated']++;
                error_log("Service FORCE UPDATED: ID {$post_id}, Title: {$service_data['title']}");
            } else {
                $stats['errors']++;
                error_log("Failed to update service: " . $result->get_error_message());
                continue;
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
                continue;
            }
        }

        if ($post_id) {
            // FORCE UPDATE ALL FIELDS (both meta and ACF)

            // 1. Base meta fields
            $meta_fields = [
                'altegio_id' => $altegio_id,
                '_altegio_id' => $altegio_id,
                'price_min' => floatval($service_data['price_min'] ?? 0),
                'price_max' => floatval($service_data['price_max'] ?? 0),
                'base_price' => floatval($service_data['price_min'] ?? 0), // This field wasn't updating!
                'currency' => 'SGD',
                'description' => sanitize_textarea_field($service_data['comment'] ?? ''),
                'local_category' => sanitize_text_field($service_data['category_id'] ?? ''),
            ];

            if (!empty($wear_time)) {
                $meta_fields['wear_time'] = sanitize_text_field($wear_time);
            }

            if (isset($service_data['duration'])) {
                $duration_seconds = intval($service_data['duration']);
                $duration_minutes = round($duration_seconds / 60);
                $meta_fields['duration_minutes'] = $duration_minutes;
                $meta_fields['_duration'] = $duration_seconds;
            }

            // FORCE UPDATE all meta fields - delete and recreate to ensure update
            foreach ($meta_fields as $key => $value) {
                delete_post_meta($post_id, $key);
                update_post_meta($post_id, $key, $value);

                error_log("Meta field FORCE UPDATED: {$key} = {$value} for post {$post_id}");
            }

            // 2. FORCE UPDATE ACF fields (even if they exist)
            if (function_exists('update_field')) {
                $acf_fields = [
                    'altegio_id' => $altegio_id,
                    'price_min' => floatval($service_data['price_min'] ?? 0),
                    'price_max' => floatval($service_data['price_max'] ?? 0),
                    'base_price' => floatval($service_data['price_min'] ?? 0), // Force update base_price!
                    'currency' => 'SGD',
                    'description' => sanitize_textarea_field($service_data['comment'] ?? ''),
                    'local_category' => sanitize_text_field($service_data['category_id'] ?? ''),
                ];

                if (!empty($wear_time)) {
                    $acf_fields['wear_time'] = sanitize_text_field($wear_time);
                }

                if (isset($service_data['duration'])) {
                    $duration_seconds = intval($service_data['duration']);
                    $duration_minutes = round($duration_seconds / 60);
                    $acf_fields['duration_minutes'] = $duration_minutes;
                    $acf_fields['_duration'] = $duration_seconds;
                }

                // Force update each ACF field
                foreach ($acf_fields as $field_name => $field_value) {
                    // Delete field first to ensure update
                    if (function_exists('delete_field')) {
                        delete_field($field_name, $post_id);
                    }

                    // Then update
                    $result = update_field($field_name, $field_value, $post_id);

                    if ($result) {
                        error_log("ACF field FORCE UPDATED: {$field_name} = {$field_value} for post {$post_id}");
                    } else {
                        error_log("ACF field UPDATE FAILED: {$field_name} for post {$post_id}");
                    }
                }
            }

            // 3. Update categories
            if (isset($service_data['category_id']) && !empty($service_data['category_id'])) {
                $category_id = sanitize_text_field($service_data['category_id']);

                $category_terms = get_terms([
                    'taxonomy' => 'service_category',
                    'meta_key' => '_altegio_category_id',
                    'meta_value' => $category_id,
                    'hide_empty' => false,
                ]);

                if (!empty($category_terms) && !is_wp_error($category_terms)) {
                    wp_set_object_terms($post_id, $category_terms[0]->term_id, 'service_category');
                    error_log("Service category updated for post {$post_id}");
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
 * Enhanced masters sync with FORCED ACF field updates
 */
function enhanced_force_sync_altegio_masters()
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
            // UPDATE existing post
            $post_id = $existing_posts[0]->ID;
            $post_data['ID'] = $post_id;

            $result = wp_update_post($post_data, true);

            if (!is_wp_error($result)) {
                $stats['updated']++;
                error_log("Master FORCE UPDATED: ID {$post_id}, Name: {$master_data['name']}");
            } else {
                $stats['errors']++;
                error_log("Failed to update master: " . $result->get_error_message());
                continue;
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
                continue;
            }
        }

        if ($post_id) {
            // FORCE UPDATE all meta fields
            $meta_fields = [
                'altegio_id' => $altegio_id,
                'description' => sanitize_textarea_field($master_data['information'] ?? ''),
                'master_level' => sanitize_text_field($master_data['specialization'] ?? '1'),
                'is_bookable' => !empty($master_data['is_bookable']) ? 1 : 0,
                'schedule_until' => sanitize_text_field($master_data['schedule_till'] ?? ''),
            ];

            // Extract Instagram from patronymic if available
            if (
                isset($master_data['employee']['patronymic']) &&
                strpos($master_data['employee']['patronymic'], 'instagram') !== false
            ) {
                $meta_fields['instagram_url'] = esc_url_raw($master_data['employee']['patronymic']);
            }

            // FORCE UPDATE all meta fields
            foreach ($meta_fields as $key => $value) {
                delete_post_meta($post_id, $key);
                update_post_meta($post_id, $key, $value);

                error_log("Master meta field FORCE UPDATED: {$key} = {$value} for post {$post_id}");
            }

            // FORCE UPDATE ACF fields
            if (function_exists('update_field')) {
                $acf_fields = [
                    'altegio_id' => $altegio_id,
                    'description' => sanitize_textarea_field($master_data['information'] ?? ''),
                    'master_level' => sanitize_text_field($master_data['specialization'] ?? '1'),
                    'is_bookable' => !empty($master_data['is_bookable']),
                    'schedule_until' => sanitize_text_field($master_data['schedule_till'] ?? ''),
                ];

                if (
                    isset($master_data['employee']['patronymic']) &&
                    strpos($master_data['employee']['patronymic'], 'instagram') !== false
                ) {
                    $acf_fields['instagram_url'] = esc_url_raw($master_data['employee']['patronymic']);
                }

                foreach ($acf_fields as $field_name => $field_value) {
                    if (function_exists('delete_field')) {
                        delete_field($field_name, $post_id);
                    }
                    $result = update_field($field_name, $field_value, $post_id);

                    if ($result) {
                        error_log("Master ACF field FORCE UPDATED: {$field_name} = {$field_value} for post {$post_id}");
                    } else {
                        error_log("Master ACF field UPDATE FAILED: {$field_name} for post {$post_id}");
                    }
                }
            }

            // FORCE UPDATE photo even if it exists
            $avatar_url = $master_data['avatar_big'] ?? '';
            if (!empty($avatar_url)) {
                // Delete existing featured image
                $existing_thumbnail_id = get_post_thumbnail_id($post_id);
                if ($existing_thumbnail_id) {
                    wp_delete_attachment($existing_thumbnail_id, true);
                }

                // Download new avatar
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

            // Update service relationships
            if (isset($master_data['services_links']) && is_array($master_data['services_links'])) {
                $service_altegio_ids = array_map(function ($link) {
                    return isset($link['service_id']) ? $link['service_id'] : null;
                }, $master_data['services_links']);

                $service_altegio_ids = array_filter($service_altegio_ids);
                $wp_service_ids = [];

                foreach ($service_altegio_ids as $service_altegio_id) {
                    $services = get_posts([
                        'post_type' => 'service',
                        'meta_query' => [
                            'relation' => 'OR',
                            [
                                'key' => 'altegio_id',
                                'value' => $service_altegio_id,
                                'compare' => '='
                            ],
                            [
                                'key' => '_altegio_id',
                                'value' => $service_altegio_id,
                                'compare' => '='
                            ]
                        ],
                        'posts_per_page' => 1,
                        'fields' => 'ids'
                    ]);

                    if (!empty($services)) {
                        $wp_service_ids[] = $services[0];
                    }
                }

                // FORCE UPDATE service relationships
                update_post_meta($post_id, 'service_ids', $service_altegio_ids);

                if (function_exists('update_field')) {
                    if (function_exists('delete_field')) {
                        delete_field('related_services', $post_id);
                    }
                    update_field('related_services', $wp_service_ids, $post_id);
                    error_log("Master service relationships FORCE UPDATED for post {$post_id}");
                }
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
 * Enhanced categories sync with FORCED ACF field updates
 */
function enhanced_force_sync_altegio_categories()
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

                // FORCE UPDATE term meta
                delete_term_meta($term_id, '_altegio_category_id');
                update_term_meta($term_id, '_altegio_category_id', $altegio_id);

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
 * Run complete enhanced sync with FORCED updates
 */
function run_enhanced_force_complete_altegio_sync()
{
    $results = [
        'categories' => enhanced_force_sync_altegio_categories(),
        'services' => enhanced_force_sync_altegio_services(),
        'masters' => enhanced_force_sync_altegio_masters()
    ];

    error_log('Enhanced Altegio Sync completed: ' . json_encode($results));
    return $results;
}

/**
 * Add admin page for FORCED sync
 */
add_action('admin_menu', function () {
    add_submenu_page(
        'tools.php',
        'Altegio Sync',
        'Altegio Sync',
        'manage_options',
        'force-altegio-sync',
        'force_altegio_sync_page'
    );
});

/**
 * Admin page for FORCED sync
 */
function force_altegio_sync_page()
{
    if (isset($_POST['force_sync_now']) && check_admin_referer('force_altegio_sync_nonce')) {
        $sync_type = sanitize_text_field($_POST['sync_type'] ?? 'all');

        if ($sync_type === 'categories') {
            $result = enhanced_force_sync_altegio_categories();
            $notice = "Categories FORCE sync completed: {$result['created']} created, {$result['updated']} updated, {$result['errors']} errors.";
        } elseif ($sync_type === 'services') {
            $result = enhanced_force_sync_altegio_services();
            $notice = "Services FORCE sync completed: {$result['created']} created, {$result['updated']} updated, {$result['errors']} errors.";
        } elseif ($sync_type === 'masters') {
            $result = enhanced_force_sync_altegio_masters();
            $notice = "Masters FORCE sync completed: {$result['created']} created, {$result['updated']} updated, {$result['errors']} errors.";
        } else {
            $result = run_enhanced_force_complete_altegio_sync();
            $notice = <<<HTML
<div>Complete FORCE sync finished!</div>
<ul>
  <li>Categories: {$result['categories']['created']} created, {$result['categories']['updated']} updated</li>
  <li>Services: {$result['services']['created']} created, {$result['services']['updated']} updated</li>
  <li>Masters: {$result['masters']['created']} created, {$result['masters']['updated']} updated</li>
</ul>
HTML;
        }
    }

    $cat_count = wp_count_terms('service_category', ['hide_empty' => false]);
    $service_count = wp_count_posts('service');
    $master_count = wp_count_posts('master');

    $test_service = get_posts(['post_type' => 'service', 'posts_per_page' => 1]);
    $test_post_id = !empty($test_service) ? $test_service[0]->ID : 0;
    $test_post_title = $test_post_id ? get_the_title($test_post_id) : '';

    $fields_to_check = ['altegio_id', 'price_min', 'price_max', 'base_price', 'currency', 'wear_time', 'duration_minutes'];
?>
    <div class="wrap">
        <h1>🚀 Altegio Synchronization</h1>
        <?php if (!empty($notice)) : ?>
            <div class="notice notice-success">
                <p><?php echo $notice; ?></p>
            </div>
        <?php endif; ?>
        <div class="notice notice-warning">
            <p><strong>⚠️ WARNING:</strong> This will FORCE update only fields synchronized with Altegio API. Manual changes in other fields will not be touched.</p>
        </div>
        <form method="post">
            <?php wp_nonce_field('force_altegio_sync_nonce'); ?>
            <h2>🔥 FORCE Sync Options</h2>
            <p><strong>This sync will:</strong></p>
            <ul>
                <li>✅ Delete and overwrite only meta and ACF fields synced with Altegio API</li>
                <li>✅ Force update all such synced fields including base_price, currency, etc.</li>
                <li>✅ Force replace photos even if they exist</li>
                <li>✅ Delete WordPress content not present in Altegio</li>
                <li><em>Other ACF fields or custom meta fields <strong>not managed</strong> by this sync will remain untouched.</em></li>
            </ul>
            <p>
                <label><input type="radio" name="sync_type" value="all" checked> 🔄 Complete FORCE Sync (Recommended)</label><br>
                <label><input type="radio" name="sync_type" value="categories"> 📂 Categories FORCE Sync</label><br>
                <label><input type="radio" name="sync_type" value="services"> 🛠️ Services FORCE Sync (with base_price fix)</label><br>
                <label><input type="radio" name="sync_type" value="masters"> 👥 Masters FORCE Sync (with photo replacement)</label>
            </p>
            <p>
                <input type="submit" name="force_sync_now" class="button button-primary" value="🚀 Altegio Sync"
                    onclick="return confirm('This will FORCE UPDATE all synced data and DELETE/RECREATE corresponding ACF fields. Manual changes to those fields will be lost. Continue?')">
            </p>
        </form>
        <div class="altegio-stats" style="margin-top: 30px; padding: 20px; background: #f8f8f8; border-radius: 5px;">
            <h2>Current Data Statistics</h2>
            <p><strong>Categories:</strong> <?php echo is_wp_error($cat_count) ? '0' : $cat_count; ?></p>
            <p><strong>Services:</strong> <?php echo isset($service_count->publish) ? $service_count->publish : '0'; ?></p>
            <p><strong>Masters:</strong> <?php echo isset($master_count->publish) ? $master_count->publish : '0'; ?></p>
        </div>
        <?php if ($test_post_id) : ?>
            <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 5px;">
                <h3>🔍 Debug ACF Fields</h3>
                <p>Test Service ID: <strong><?php echo $test_post_id; ?></strong> - <?php echo esc_html($test_post_title); ?></p>
                <table border="1" style="border-collapse: collapse;">
                    <tr>
                        <th>Field</th>
                        <th>Meta Value</th>
                        <th>ACF Value</th>
                    </tr>
                    <?php foreach ($fields_to_check as $field):
                        $meta_value = get_post_meta($test_post_id, $field, true);
                        $acf_value = function_exists('get_field') ? get_field($field, $test_post_id) : 'ACF not available';
                    ?>
                        <tr>
                            <td><?php echo esc_html($field); ?></td>
                            <td><?php echo $meta_value !== '' ? esc_html($meta_value) : 'empty'; ?></td>
                            <td><?php echo $acf_value !== '' ? esc_html($acf_value) : 'empty'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php
}


/**
 * AJAX handlers for FORCE sync
 */
add_action('wp_ajax_force_altegio_sync_services', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    $result = enhanced_force_sync_altegio_services();
    wp_send_json_success(['result' => $result]);
});

add_action('wp_ajax_force_altegio_sync_masters', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    $result = enhanced_force_sync_altegio_masters();
    wp_send_json_success(['result' => $result]);
});

add_action('wp_ajax_force_altegio_sync_categories', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    $result = enhanced_force_sync_altegio_categories();
    wp_send_json_success(['result' => $result]);
});


/**
 * Individual field force update function for testing
 */
function force_update_specific_field($post_id, $field_name, $field_value)
{
    // Delete both meta and ACF field first
    delete_post_meta($post_id, $field_name);

    if (function_exists('delete_field')) {
        delete_field($field_name, $post_id);
    }

    // Then update both
    update_post_meta($post_id, $field_name, $field_value);

    if (function_exists('update_field')) {
        $result = update_field($field_name, $field_value, $post_id);
        error_log("FORCE UPDATE field {$field_name} = {$field_value} for post {$post_id}: " . ($result ? 'SUCCESS' : 'FAILED'));
        return $result;
    }

    return true;
}

/**
 * Scheduled FORCE sync (use with caution)
 */
function schedule_force_sync()
{
    if (!wp_next_scheduled('force_altegio_daily_sync')) {
        wp_schedule_event(time(), 'daily', 'force_altegio_daily_sync');
    }
}

add_action('force_altegio_daily_sync', 'run_enhanced_force_complete_altegio_sync');

/**
 * Manual trigger for developers
 */
function trigger_force_sync_now()
{
    if (is_admin() && current_user_can('manage_options')) {
        return run_enhanced_force_complete_altegio_sync();
    }
    return false;
}

/**
 * Debug function to check specific service fields
 */
function debug_service_fields($service_id)
{
    if (!current_user_can('manage_options')) {
        return false;
    }

    $fields = ['altegio_id', 'price_min', 'price_max', 'base_price', 'currency', 'wear_time', 'duration_minutes'];
    $debug_info = [];

    foreach ($fields as $field) {
        $meta_value = get_post_meta($service_id, $field, true);
        $acf_value = function_exists('get_field') ? get_field($field, $service_id) : null;

        $debug_info[$field] = [
            'meta' => $meta_value ?: 'empty',
            'acf' => $acf_value ?: 'empty'
        ];
    }

    error_log("DEBUG Service {$service_id} fields: " . json_encode($debug_info));
    return $debug_info;
}

/**
 * Test single field update
 */
function test_single_field_update($post_id, $field_name, $new_value)
{
    if (!current_user_can('manage_options')) {
        return false;
    }

    error_log("TESTING field update: {$field_name} = {$new_value} for post {$post_id}");

    // Get current values
    $old_meta = get_post_meta($post_id, $field_name, true);
    $old_acf = function_exists('get_field') ? get_field($field_name, $post_id) : null;

    error_log("BEFORE - Meta: {$old_meta}, ACF: {$old_acf}");

    // Force update
    $result = force_update_specific_field($post_id, $field_name, $new_value);

    // Check new values
    $new_meta = get_post_meta($post_id, $field_name, true);
    $new_acf = function_exists('get_field') ? get_field($field_name, $post_id) : null;

    error_log("AFTER - Meta: {$new_meta}, ACF: {$new_acf}");

    return [
        'success' => $result,
        'before' => ['meta' => $old_meta, 'acf' => $old_acf],
        'after' => ['meta' => $new_meta, 'acf' => $new_acf]
    ];
}
