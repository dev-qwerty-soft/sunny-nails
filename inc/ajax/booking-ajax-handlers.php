<?php
function altegio_get_services()
{
    check_ajax_referer('booking_nonce', 'nonce');

    // Get services from WordPress database with add-on flag
    $services_query = new WP_Query([
        'post_type' => 'service',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);

    $services = [];
    $categories = [];

    if ($services_query->have_posts()) {
        while ($services_query->have_posts()) {
            $services_query->the_post();
            $post_id = get_the_ID();
            $is_addon = get_post_meta($post_id, 'is_addon', true) === 'yes';
            $service_categories = wp_get_post_terms($post_id, 'service_category', ['fields' => 'ids']);

            $services[] = [
                'ID' => $post_id,
                'post_title' => get_the_title(),
                'price_min' => get_post_meta($post_id, 'price_min', true) ?: '0',
                'price_max' => get_post_meta($post_id, 'price_max', true) ?: '0',
                'currency' => get_post_meta($post_id, 'currency', true) ?: 'SGD',
                'duration_minutes' => get_post_meta($post_id, 'duration_minutes', true) ?: '',
                'wear_time' => get_post_meta($post_id, 'wear_time', true) ?: '',
                'description' => get_post_meta($post_id, 'description', true) ?: '',
                'is_addon' => $is_addon,
                'categories' => $service_categories,
                'altegio_id' => get_post_meta($post_id, '_altegio_id', true) ?:
                    get_post_meta($post_id, 'altegio_id', true) ?: $post_id,
            ];
        }
        wp_reset_postdata();

        // Get all categories
        $categories = get_terms([
            'taxonomy' => 'service_category',
            'hide_empty' => true,
        ]);
    }

    wp_send_json_success([
        'categories' => $categories,
        'services' => $services
    ]);
}
add_action('wp_ajax_get_services', 'altegio_get_services');
add_action('wp_ajax_nopriv_get_services', 'altegio_get_services');

// AJAX Handler for Getting Masters
function altegio_get_masters()
{
    check_ajax_referer('booking_nonce', 'nonce');

    // Get service ID from request (optional)
    $service_id = isset($_POST['service_id']) ? sanitize_text_field($_POST['service_id']) : '';

    // Query masters
    $masters_query = new WP_Query([
        'post_type' => 'master',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);

    $masters = [];
    $level_titles = [
        1 => "Sunny Ray",
        2 => "Sunny Shine",
        3 => "Sunny Inferno",
    ];

    if ($masters_query->have_posts()) {
        while ($masters_query->have_posts()) {
            $masters_query->the_post();
            $post_id = get_the_ID();
            $level = (int)get_post_meta($post_id, 'master_level', true) ?: 1;
            $specialization = get_post_meta($post_id, 'specialization', true) ?: $level_titles[$level] ?? '';
            $related_services = get_post_meta($post_id, 'related_services', true);

            // Filter by service if specified
            if ($service_id && $related_services) {
                if (!in_array($service_id, (array)$related_services)) {
                    continue;
                }
            }

            $masters[] = [
                'id' => $post_id,
                'altegio_id' => get_post_meta($post_id, 'altegio_id', true) ?: $post_id,
                'name' => get_the_title(),
                'avatar' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
                'level' => $level,
                'specialization' => $specialization,
            ];
        }
        wp_reset_postdata();
    }

    wp_send_json_success(['data' => $masters]);
}
add_action('wp_ajax_get_masters', 'altegio_get_masters');
add_action('wp_ajax_nopriv_get_masters', 'altegio_get_masters');


// AJAX Handler for Submitting Booking
function altegio_submit_booking()
{
    check_ajax_referer('booking_nonce', 'booking_nonce');

    $required_fields = ['service_id', 'staff_id', 'date', 'time', 'client_name', 'client_phone'];
    $missing_fields = [];
    $booking_data = [];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missing_fields[] = $field;
        } else {
            $booking_data[$field] = sanitize_text_field($_POST[$field]);
        }
    }

    if (!empty($missing_fields)) {
        wp_send_json_error([
            'message' => 'Required fields missing',
            'fields' => $missing_fields
        ]);
        return;
    }

    // Optional fields
    $booking_data['client_email'] = isset($_POST['client_email']) ? sanitize_email($_POST['client_email']) : '';
    $booking_data['client_comment'] = isset($_POST['client_comment']) ? sanitize_textarea_field($_POST['client_comment']) : '';

    // Decode additional data
    $core_services = isset($_POST['core_services']) ? json_decode(stripslashes($_POST['core_services']), true) : [];
    $addon_services = isset($_POST['addon_services']) ? json_decode(stripslashes($_POST['addon_services']), true) : [];
    $staff_level = isset($_POST['staff_level']) ? intval($_POST['staff_level']) : 1;

    // Format data for Altegio API
    $api_data = [
        'staff_id' => $booking_data['staff_id'],
        'date' => $booking_data['date'],
        'time' => $booking_data['time'],
        'services' => explode(',', $booking_data['service_id']),
        'client' => [
            'name' => $booking_data['client_name'],
            'phone' => $booking_data['client_phone']
        ]
    ];

    if (!empty($booking_data['client_email'])) {
        $api_data['client']['email'] = $booking_data['client_email'];
    }

    if (!empty($booking_data['client_comment'])) {
        $api_data['client']['comment'] = $booking_data['client_comment'];
    }

    // Submit to Altegio API
    if (class_exists('AltegioClient')) {
        try {
            $result = AltegioClient::makeBooking($api_data);

            if (!isset($result['error'])) {
                // Save additional metadata for price adjustments
                $booking_meta = [
                    'staff_level' => $staff_level,
                    'core_services' => $core_services,
                    'addon_services' => $addon_services,
                    'adjusted_price' => $_POST['total_price'] ?? '',
                    'raw_price' => $_POST['raw_price'] ?? '',
                ];

                // Store booking meta (could be in a custom table or as post meta)
                // This depends on how you want to store booking history

                wp_send_json_success([
                    'message' => 'Booking created successfully',
                    'booking' => $result['booking'] ?? null
                ]);
                return;
            }
        } catch (Exception $e) {
            error_log('Altegio API booking error: ' . $e->getMessage());
        }
    }

    // Fallback response
    wp_send_json_success([
        'message' => 'Booking created successfully (demo mode)',
        'booking' => [
            'id' => uniqid('demo_'),
            'reference' => 'BK' . mt_rand(1000, 9999),
            'datetime' => $booking_data['date'] . ' ' . $booking_data['time']
        ]
    ]);
}
add_action('wp_ajax_submit_booking', 'altegio_submit_booking');
add_action('wp_ajax_nopriv_submit_booking', 'altegio_submit_booking');
function altegio_get_filtered_staff()
{
    check_ajax_referer('booking_nonce', 'nonce');

    $service_ids_raw = isset($_POST['service_id']) ? sanitize_text_field($_POST['service_id']) : '';
    $service_ids = array_filter(array_map('intval', explode(',', $service_ids_raw)));

    if (empty($service_ids)) {
        wp_send_json_error(['message' => 'No services provided']);
    }

    $query = new WP_Query([
        'post_type' => 'master',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);

    $level_titles = [
        1 => "Sunny Ray",
        2 => "Sunny Shine",
        3 => "Sunny Inferno",
    ];

    $available_masters = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            $is_bookable = get_field('is_bookable', $post_id);
            if (!$is_bookable) continue;

            $related_services = get_field('related_services', $post_id, false); // array of IDs
            if (empty($related_services)) continue;

            // Check if at least one service matches
            if (!array_intersect($service_ids, $related_services)) continue;

            $level = (int) get_field('master_level', $post_id);
            $available_masters[] = [
                'id' => get_field('altegio_id', $post_id),
                'name' => get_the_title(),
                'avatar' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
                'level' => $level,
                'specialization' => get_field('master_specialization', $post_id) ?: ($level_titles[$level] ?? ''),
            ];
        }

        wp_reset_postdata();
    }

    wp_send_json_success(['data' => $available_masters]);
}
add_action('wp_ajax_get_filtered_staff', 'altegio_get_filtered_staff');
add_action('wp_ajax_nopriv_get_filtered_staff', 'altegio_get_filtered_staff');
function altegio_get_filtered_services()
{
    check_ajax_referer('booking_nonce', 'nonce');

    $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
    if (!$staff_id) {
        wp_send_json_error(['message' => 'Missing staff_id']);
    }

    // Отримати локальний пост master за altegio_id
    $query = new WP_Query([
        'post_type' => 'master',
        'meta_query' => [
            [
                'key' => 'altegio_id',
                'value' => $staff_id,
            ]
        ],
        'posts_per_page' => 1,
    ]);

    if (!$query->have_posts()) {
        wp_send_json_error(['message' => 'Master not found']);
    }

    $query->the_post();
    $post_id = get_the_ID();
    $related_services = get_field('related_services', $post_id, false); // array of WP post IDs
    wp_reset_postdata();

    if (empty($related_services)) {
        wp_send_json_error(['message' => 'No related services found']);
    }

    // Завантажити з API список дозволених service_id для цього майстра
    $api_service_ids = [];
    if (class_exists('AltegioClient')) {
        $staff_data = AltegioClient::getStaff();
        foreach ($staff_data['data'] ?? [] as $staff) {
            if ((int)$staff['id'] === $staff_id && isset($staff['services_links'])) {
                foreach ($staff['services_links'] as $link) {
                    $api_service_ids[] = (int)$link['service_id'];
                }
                break;
            }
        }
    }

    // Перевірити зв’язок ACF-сервісів з API-сервісами
    $filtered_services = [];
    foreach ($related_services as $service_post_id) {
        $altegio_id = get_post_meta($service_post_id, '_altegio_id', true) ?: get_post_meta($service_post_id, 'altegio_id', true);
        if (in_array((int)$altegio_id, $api_service_ids)) {
            $filtered_services[] = $service_post_id;
        }
    }

    if (empty($filtered_services)) {
        wp_send_json_error(['message' => 'No services available for this master.']);
    }

    // Вивести HTML для доступних сервісів
    ob_start();

    $service_categories = get_terms([
        'taxonomy' => 'service_category',
        'hide_empty' => false,
    ]);

    foreach ($service_categories as $i => $category):
        $services = get_posts([
            'post_type' => 'service',
            'post__in' => $filtered_services,
            'tax_query' => [
                [
                    'taxonomy' => 'service_category',
                    'field' => 'term_id',
                    'terms' => $category->term_id,
                ]
            ],
            'posts_per_page' => -1,
        ]);
        if (empty($services)) continue;
?>
        <div class="category-services" data-category-id="<?php echo esc_attr($category->term_id); ?>" style="<?php echo $i === 0 ? '' : 'display:none'; ?>">
            <?php foreach ($services as $service):
                setup_postdata($service);
                $post_id = $service->ID;
                $price = get_post_meta($post_id, 'price_min', true);
                $currency = get_post_meta($post_id, 'currency', true) ?: 'SGD';
                $duration = get_post_meta($post_id, 'duration_minutes', true);
                $wear_time = get_post_meta($post_id, 'wear_time', true);
                $desc = get_post_meta($post_id, 'description', true);
                $is_addon = get_post_meta($post_id, 'is_addon', true) === 'yes';
                if (empty($wear_time) && !empty($service->post_content)) {
                    preg_match('/wear\s+time:?\s+([^\.]+)/i', $service->post_content, $matches);
                    if (!empty($matches[1])) $wear_time = trim($matches[1]);
                }
            ?>
                <?php if (!$is_addon): ?>
                    <div class="service-item" data-service-id="<?php echo esc_attr($post_id); ?>">
                        <div class="service-info">
                            <div class="service-title">
                                <h4 class="service-name"><?php echo esc_html(get_the_title($post_id)); ?></h4>
                                <div class="service-checkbox-wrapper">
                                    <div class="service-price"><?php echo esc_html($price); ?> <?php echo esc_html($currency); ?></div>
                                    <input type="checkbox"
                                        class="service-checkbox"
                                        data-service-id="<?php echo esc_attr($post_id); ?>"
                                        data-service-title="<?php echo esc_attr(get_the_title($post_id)); ?>"
                                        data-service-price="<?php echo esc_attr($price); ?>"
                                        data-service-currency="<?php echo esc_attr($currency); ?>"
                                        data-is-addon="false"
                                        <?php if ($duration): ?>data-service-duration="<?php echo esc_attr($duration); ?>" <?php endif; ?>
                                        <?php if ($wear_time): ?>data-service-wear-time="<?php echo esc_attr($wear_time); ?>" <?php endif; ?>>
                                </div>
                            </div>
                            <?php if ($duration): ?><div class="service-duration"><strong>Duration:</strong> <?php echo esc_html($duration); ?> min</div><?php endif; ?>
                            <?php if ($wear_time): ?><div class="service-wear-time"><strong>Wear time:</strong> <?php echo esc_html($wear_time); ?></div><?php endif; ?>
                            <?php if ($desc): ?><div class="service-description"><?php echo esc_html($desc); ?></div><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach;
            wp_reset_postdata(); ?>

            <div class="addon-services-container">
                <?php foreach ($services as $service):
                    setup_postdata($service);
                    $post_id = $service->ID;
                    $is_addon = get_post_meta($post_id, 'is_addon', true) === 'yes';
                    if (!$is_addon) continue;
                    $price = get_post_meta($post_id, 'price_min', true);
                    $currency = get_post_meta($post_id, 'currency', true) ?: 'SGD';
                    $duration = get_post_meta($post_id, 'duration_minutes', true);
                    $wear_time = get_post_meta($post_id, 'wear_time', true);
                    $desc = get_post_meta($post_id, 'description', true);
                ?>
                    <div class="service-item addon-item disabled" data-service-id="<?php echo esc_attr($post_id); ?>">
                        <div class="service-info">
                            <div class="service-title">
                                <h4 class="service-name"><?php echo esc_html(get_the_title($post_id)); ?> <span class="addon-label">(add-on)</span></h4>
                                <div class="service-checkbox-wrapper">
                                    <div class="service-price"><?php echo esc_html($price); ?> <?php echo esc_html($currency); ?></div>
                                    <input type="checkbox"
                                        class="service-checkbox"
                                        data-service-id="<?php echo esc_attr($post_id); ?>"
                                        data-service-title="<?php echo esc_attr(get_the_title($post_id)); ?>"
                                        data-service-price="<?php echo esc_attr($price); ?>"
                                        data-service-currency="<?php echo esc_attr($currency); ?>"
                                        data-is-addon="true"
                                        disabled
                                        <?php if ($duration): ?>data-service-duration="<?php echo esc_attr($duration); ?>" <?php endif; ?>
                                        <?php if ($wear_time): ?>data-service-wear-time="<?php echo esc_attr($wear_time); ?>" <?php endif; ?>>
                                </div>
                            </div>
                            <?php if ($duration): ?><div class="service-duration">Duration: <?php echo esc_html($duration); ?> min</div><?php endif; ?>
                            <?php if ($wear_time): ?><div class="service-wear-time">Wear time: <?php echo esc_html($wear_time); ?></div><?php endif; ?>
                            <?php if ($desc): ?><div class="service-description"><?php echo esc_html($desc); ?></div><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach;
                wp_reset_postdata(); ?>
            </div>
        </div>
<?php endforeach;

    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}

add_action('wp_ajax_get_filtered_services', 'altegio_get_filtered_services');
add_action('wp_ajax_nopriv_get_filtered_services', 'altegio_get_filtered_services');


add_action('wp_ajax_get_time_slots', 'ajax_get_time_slots');
add_action('wp_ajax_nopriv_get_time_slots', 'ajax_get_time_slots');

add_action('wp_ajax_get_time_slots', 'ajax_get_time_slots');
add_action('wp_ajax_nopriv_get_time_slots', 'ajax_get_time_slots');

function ajax_get_time_slots()
{
    check_ajax_referer('booking_nonce', 'nonce');

    $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';

    if (!$staff_id || !$date) {
        wp_send_json_error(['message' => 'Missing staff ID or date']);
    }

    try {
        $response = AltegioClient::getBookTimes($staff_id, $date);

        if (!isset($response['data']) || !is_array($response['data'])) {
            wp_send_json_error(['message' => 'Invalid response from Altegio']);
        }

        wp_send_json_success(['slots' => $response['data']]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
function get_services_for_master()
{
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'your_nonce_action')) {
        wp_send_json_error(array('message' => 'Nonce verification failed.'));
        return;
    }

    // Check staff_id
    if (!isset($_POST['staff_id']) || empty($_POST['staff_id'])) {
        wp_send_json_error(array('message' => 'Staff ID is missing.'));
        return;
    }

    // Fetch services for the given staff ID
    $staff_id = sanitize_text_field($_POST['staff_id']);
    $services = get_services_by_staff($staff_id);

    if ($services) {
        wp_send_json_success(array('data' => $services));
    } else {
        wp_send_json_error(array('message' => 'No services found for this staff.'));
    }
}

add_action('wp_ajax_get_services_for_master', 'get_services_for_master');
add_action('wp_ajax_nopriv_get_services_for_master', 'get_services_for_master');
