<?php
function altegio_get_services()
{
    check_ajax_referer('booking_nonce', 'nonce');

    $services_query = new WP_Query([
        'post_type' => 'service',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'is_online',
                'value' => '1',
                'compare' => '='
            ]
        ]
    ]);

    $services = [];
    if ($services_query->have_posts()) {
        while ($services_query->have_posts()) {
            $services_query->the_post();
            $post_id = get_the_ID();

            // Get category information
            $service_categories = wp_get_post_terms($post_id, 'service_category');
            $category_slugs = array_map(function ($cat) {
                return $cat->slug;
            }, $service_categories);

            // Check if category should exclude master markup
            $exclude_master_markup = in_array('addons', $category_slugs);

            $services[] = [
                'ID' => $post_id,
                'post_title' => get_the_title(),
                'price_min' => get_post_meta($post_id, 'price_min', true) ?: '0',
                'currency' => get_post_meta($post_id, 'currency', true) ?: 'SGD',
                'category_slugs' => $category_slugs,
                'exclude_master_markup' => $exclude_master_markup,
                'duration_minutes' => get_post_meta($post_id, 'duration_minutes', true) ?: '',
                'wear_time' => get_post_meta($post_id, 'wear_time', true) ?: '',
                'description' => get_post_meta($post_id, 'description', true) ?: '',
                'is_addon' => $is_addon,
                'categories' => $service_categories,
                'altegio_id' => get_post_meta($post_id, 'altegio_id', true) ?:
                    get_post_meta($post_id, 'altegio_id', true) ?: $post_id,
            ];
        }
        wp_reset_postdata();
    }

    wp_send_json_success([
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




/**
 * AJAX handler for submitting booking with price adjustment
 */
function altegio_submit_booking()
{
    check_ajax_referer('booking_nonce', 'booking_nonce');

    // Get client data
    $client = isset($_POST['client']) ? $_POST['client'] : [];
    if (is_string($client)) {
        $client = json_decode(stripslashes($client), true);
    }

    // Get core services data
    $core_services = isset($_POST['core_services']) ? $_POST['core_services'] : [];
    if (is_string($core_services)) {
        $core_services = json_decode(stripslashes($core_services), true);
    }

    // Get addon services data
    $addon_services = isset($_POST['addon_services']) ? $_POST['addon_services'] : [];
    if (is_string($addon_services)) {
        $addon_services = json_decode(stripslashes($addon_services), true);
    }

    // Get all services
    $all_services = array_merge($core_services, $addon_services);

    // Get booking details
    $staff_id = isset($_POST['staff_id']) ? sanitize_text_field($_POST['staff_id']) : '';
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
    $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';
    $datetime = $date . 'T' . $time . ':00';

    // Get price adjustment data
    $staff_level = isset($_POST['staff_level']) ? (int)$_POST['staff_level'] : 1;
    $base_price = isset($_POST['base_price']) ? (float)$_POST['base_price'] : 0;
    $adjusted_price = isset($_POST['adjusted_price']) ? (float)$_POST['adjusted_price'] : 0;
    $price_adjustment = isset($_POST['price_adjustment']) ? (float)$_POST['price_adjustment'] : 0;
    $adjustment_percent = isset($_POST['adjustment_percent']) ? (float)$_POST['adjustment_percent'] : 0;
    $total_price = isset($_POST['total_price']) ? (float)$_POST['total_price'] : $adjusted_price;

    // Get client details directly from POST
    $client_name = isset($_POST['client_name']) ? sanitize_text_field($_POST['client_name']) : '';
    $client_phone = isset($_POST['client_phone']) ? sanitize_text_field($_POST['client_phone']) : '';
    $client_email = isset($_POST['client_email']) ? sanitize_email($_POST['client_email']) : '';
    $client_comment = isset($_POST['client_comment']) ? sanitize_textarea_field($_POST['client_comment']) : '';

    // Basic validation
    if (!$client_name || !$client_phone || !$staff_id || !$date || !$time || empty($all_services)) {
        wp_send_json_error(['message' => 'Required fields missing']);
        return;
    }

    // Get service IDs for Altegio
    $service_ids = [];
    foreach ($all_services as $service) {
        $wp_id = isset($service['id']) ? (int)$service['id'] : 0;
        // Try to get Altegio ID from meta
        $altegio_id = (int)get_post_meta($wp_id, 'altegio_id', true);
        if (empty($altegio_id)) {
            // Try alternative meta key
            $altegio_id = (int)get_post_meta($wp_id, '_altegio_id', true);
        }
        // Use WordPress ID as fallback
        $service_ids[] = $altegio_id ?: $wp_id;
    }
    $full_comment = $client_comment;


    // Prepare data for Altegio API
    $api_data = [
        'phone' => preg_replace('/\D+/', '', $client_phone),
        'fullname' => $client_name,
        'email' => $client_email,
        'comment' => $full_comment,
        'type' => 'online',
        'notify_by_sms' => 0,
        'notify_by_email' => 0,
        'appointments' => [[
            'id' => 1,
            'services' => $service_ids,
            'staff_id' => $staff_id,
            'datetime' => $datetime,
            'price' => $total_price,  // Important: Send the final adjusted price
            'custom_fields' => [
                'master_level' => $staff_level,
                'base_price' => $base_price,
                'adjusted_price' => $total_price,
                'price_adjustment' => $price_adjustment,
                'adjustment_percent' => $adjustment_percent
            ]
        ]]
    ];

    // Log the API data
    error_log('Submitting booking to Altegio with price data: ' . json_encode([
        'staff_level' => $staff_level,
        'base_price' => $base_price,
        'adjusted_price' => $total_price,
        'price_adjustment' => $price_adjustment,
        'adjustment_percent' => $adjustment_percent
    ]));

    // Submit to Altegio API
    if (class_exists('AltegioClient')) {
        try {
            $result = AltegioClient::makeBooking($api_data);
            error_log('Altegio API response: ' . json_encode($result));

            if (isset($result['success']) && $result['success']) {
                // Store booking in WordPress for reference
                $booking_id = save_booking_record_with_price([
                    'client_name' => $client_name,
                    'client_phone' => $client_phone,
                    'client_email' => $client_email,
                    'client_comment' => $client_comment,
                    'service_ids' => $service_ids,
                    'staff_id' => $staff_id,
                    'staff_level' => $staff_level,
                    'datetime' => $datetime,
                    'base_price' => $base_price,
                    'adjusted_price' => $total_price,
                    'price_adjustment' => $price_adjustment,
                    'altegio_reference' => isset($result['data'][0]['record_id']) ? $result['data'][0]['record_id'] : '',
                ]);
                if (!empty($_POST['coupon_code'])) {
                    increment_promo_usage(sanitize_text_field($_POST['coupon_code']));
                }
                wp_send_json_success([
                    'message' => 'Booking created successfully',
                    'booking' => [
                        'reference' => isset($result['data'][0]['record_id']) ? 'BK' . $result['data'][0]['record_id'] : 'BK' . mt_rand(10000, 99999),
                        'datetime' => $datetime,
                        'adjusted_price' => $total_price,
                        'booking_id' => $booking_id,
                    ]
                ]);
                return;
            } else {
                $error_message = isset($result['error']) ? $result['error'] : 'Error connecting to booking service';
                error_log('Altegio API error: ' . $error_message);
                wp_send_json_error(['message' => $error_message]);
                return;
            }
        } catch (Exception $e) {
            error_log('Altegio API booking exception: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
            return;
        }
    }

    // Fallback response if AltegioClient is not available
    wp_send_json_success([
        'message' => 'Booking created successfully (demo mode)',
        'booking' => [
            'reference' => 'BK' . mt_rand(10000, 99999),
            'datetime' => $datetime,
            'adjusted_price' => $total_price,
        ]
    ]);
}

/**
 * Save booking record with price adjustment information
 * 
 * @param array $booking_data Booking data including price information
 * @return int|bool Post ID on success, false on failure
 */
function save_booking_record_with_price($booking_data)
{
    // Check if CPT exists, if not, use regular post
    $post_type = post_type_exists('booking') ? 'booking' : 'post';

    // Create post title
    $post_title = sprintf(
        'Booking: %s - %s - %.2f SGD',
        $booking_data['client_name'],
        $booking_data['datetime'],
        $booking_data['adjusted_price']
    );

    // Create post
    $post_id = wp_insert_post([
        'post_title' => $post_title,
        'post_type' => $post_type,
        'post_status' => 'publish',
    ]);

    if (!$post_id || is_wp_error($post_id)) {
        error_log('Failed to create booking record: ' . ($post_id->get_error_message() ?? 'Unknown error'));
        return false;
    }

    // Save metadata
    update_post_meta($post_id, '_booking_client_name', $booking_data['client_name']);
    update_post_meta($post_id, '_booking_client_phone', $booking_data['client_phone']);
    update_post_meta($post_id, '_booking_client_email', $booking_data['client_email']);
    update_post_meta($post_id, '_booking_staff_id', $booking_data['staff_id']);
    update_post_meta($post_id, '_booking_staff_level', $booking_data['staff_level']);
    update_post_meta($post_id, '_booking_datetime', $booking_data['datetime']);
    update_post_meta($post_id, '_booking_service_ids', json_encode($booking_data['service_ids']));

    // Save price information
    update_post_meta($post_id, '_booking_base_price', $booking_data['base_price']);
    update_post_meta($post_id, '_booking_adjusted_price', $booking_data['adjusted_price']);
    update_post_meta($post_id, '_booking_price_adjustment', $booking_data['price_adjustment']);
    update_post_meta($post_id, '_booking_altegio_reference', $booking_data['altegio_reference']);

    return $post_id;
}

/**
 * Save booking record with price adjustment information
 * 
 * @param array $booking_data Booking data including price information
 * @return int|bool Post ID on success, false on failure
 */

// Register AJAX handlers
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
        return;
    }

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
        $query = new WP_Query([
            'post_type' => 'master',
            'posts_per_page' => -1,
        ]);

        $found = false;
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $altegio_id = get_post_meta(get_the_ID(), 'altegio_id', true);
                if ($altegio_id == $staff_id) {
                    $found = true;
                    $post_id = get_the_ID();
                    break;
                }
            }
        }

        if (!$found) {
            wp_send_json_error(['message' => 'Master not found', 'staff_id' => $staff_id]);
            return;
        }
    } else {
        $query->the_post();
        $post_id = get_the_ID();
    }

    $related_services = [];
    $acf_related = get_field('related_services', $post_id);

    if (!empty($acf_related) && is_array($acf_related)) {
        $related_services = $acf_related;
    } else {
        $meta_related = get_post_meta($post_id, 'related_services', true);
        if (is_array($meta_related)) {
            $related_services = $meta_related;
        }
    }

    if (empty($related_services)) {
        wp_send_json_error(['message' => 'No related services found']);
        return;
    }

    ob_start();

    $service_categories = get_terms([
        'taxonomy' => 'service_category',
        'hide_empty' => false,
        'orderby' => 'term_order',
    ]);

    $has_any_service = false;

    foreach ($service_categories as $i => $category):
        $services = get_posts([
            'post_type' => 'service',
            'post__in' => $related_services,
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'service_category',
                    'field' => 'term_id',
                    'terms' => $category->term_id,
                ]
            ],
            'meta_query' => [
                [
                    'key' => 'is_online',
                    'value' => '1',
                    'compare' => '='
                ]
            ],
            'orderby' => 'menu_order',
            'order'   => 'ASC'

        ]);

        $core_services = array_filter($services, function ($service) {
            $is_addon = get_post_meta($service->ID, 'is_addon', true) !== 'yes';
            $is_online = get_post_meta($service->ID, 'is_online', true);
            return $is_addon && $is_online;
        });

        if (empty($core_services)) continue;

        $has_any_service = true;
?>
        <div class="category-services" data-category-id="<?php echo esc_attr($category->term_id); ?>" style="<?php echo $i === 0 ? '' : 'display:none'; ?>">
            <?php
            foreach ($core_services as $service):
                setup_postdata($service);
                $post_id = $service->ID;
                $price = get_post_meta($post_id, 'price_min', true);
                $currency = get_post_meta($post_id, 'currency', true) ?: 'SGD';
                $duration = get_post_meta($post_id, 'duration_minutes', true);
                $wear_time = get_post_meta($post_id, 'wear_time', true);
                $desc = get_post_meta($post_id, 'description', true);
                $altegio_id = get_post_meta($post_id, 'altegio_id', true);

                if (empty($wear_time) && !empty($service->post_content)) {
                    preg_match('/wear\s+time:?\s+([^\.]+)/i', $service->post_content, $matches);
                    if (!empty($matches[1])) $wear_time = trim($matches[1]);
                }
            ?>
                <div class="service-item" data-service-id="<?php echo esc_attr($post_id); ?>">
                    <div class="service-info">
                        <div class="service-title">
                            <h4 class="service-name"><?php echo esc_html(get_the_title($post_id)); ?></h4>
                            <div class="service-checkbox-wrapper">
                                <div class="service-price"><?php echo esc_html($price); ?> <?php echo esc_html($currency); ?></div>
                                <input type="checkbox"
                                    class="service-checkbox"
                                    data-service-id="<?php echo esc_attr($post_id); ?>"
                                    data-altegio-id="<?php echo esc_attr($altegio_id); ?>"
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
                    <?php
                    $related_addons = get_field('addons', $post_id);
                    if (!empty($related_addons)): ?>
                        <div class="core-related-addons" data-core-id="<?php echo esc_attr($post_id); ?>">
                            <?php foreach ($related_addons as $addon):
                                $addon_post = is_object($addon) ? $addon : get_post($addon);
                                if (!$addon_post) continue;

                                $a_id = $addon_post->ID;

                                // Check if addon is online
                                $addon_is_online = get_post_meta($a_id, 'is_online', true);
                                if (!$addon_is_online) continue;

                                $a_title = get_the_title($a_id);
                                $a_price = get_post_meta($a_id, 'price_min', true);
                                $a_currency = get_post_meta($a_id, 'currency', true) ?: 'SGD';
                                $a_duration = get_post_meta($a_id, 'duration_minutes', true);
                                $a_wear = get_post_meta($a_id, 'wear_time', true);
                                $a_desc = get_post_meta($a_id, 'description', true);
                                $a_altegio = get_post_meta($a_id, 'altegio_id', true); ?>

                                <div class="service-item addon-item disabled"
                                    data-service-id="<?php echo esc_attr($a_id); ?>"
                                    data-core-linked="<?php echo esc_attr($post_id); ?>">
                                    <div class="service-info">
                                        <div class="service-title">
                                            <h4 class="service-name"><?php echo esc_html($a_title); ?> <span class="addon-label"></span></h4>
                                            <div class="service-checkbox-wrapper">
                                                <div class="service-price"><?php echo esc_html($a_price); ?> <?php echo esc_html($a_currency); ?></div>
                                                <input type="checkbox"
                                                    class="service-checkbox"
                                                    data-service-id="<?php echo esc_attr($a_id); ?>"
                                                    data-altegio-id="<?php echo esc_attr($a_altegio); ?>"
                                                    data-service-title="<?php echo esc_attr($a_title); ?>"
                                                    data-service-price="<?php echo esc_attr($a_price); ?>"
                                                    data-service-currency="<?php echo esc_attr($a_currency); ?>"
                                                    data-is-addon="true"
                                                    disabled
                                                    <?php if ($a_duration): ?>data-service-duration="<?php echo esc_attr($a_duration); ?>" <?php endif; ?>
                                                    <?php if ($a_wear): ?>data-service-wear-time="<?php echo esc_attr($a_wear); ?>" <?php endif; ?>>
                                            </div>
                                        </div>
                                        <?php if ($a_duration): ?><div class="service-duration"><strong>Duration:</strong> <?php echo esc_html($a_duration); ?> min</div><?php endif; ?>
                                        <?php if ($a_wear): ?><div class="service-wear-time"><strong>Wear time:</strong> <?php echo esc_html($a_wear); ?></div><?php endif; ?>
                                        <?php if ($a_desc): ?><div class="service-description"><?php echo esc_html($a_desc); ?></div><?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>


            <?php
            endforeach;
            wp_reset_postdata();
            ?>
        </div>
<?php endforeach;

    $html = ob_get_clean();

    if (!$has_any_service || empty(trim($html))) {
        wp_send_json_error([
            'message' => 'No services available for this master'
        ]);
        return;
    }

    wp_send_json_success(['html' => $html]);
}

add_action('wp_ajax_get_filtered_services', 'altegio_get_filtered_services');
add_action('wp_ajax_nopriv_get_filtered_services', 'altegio_get_filtered_services');


/**
 * AJAX handler for getting time slots
 */
function ajax_get_time_slots()
{
    check_ajax_referer('booking_nonce', 'nonce');

    $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
    $service_ids = isset($_POST['service_ids']) ? (array)$_POST['service_ids'] : [];

    if (!$staff_id || !$date) {
        wp_send_json_error(['message' => 'Missing staff ID or date']);
        return;
    }

    // Convert WP post IDs to Altegio IDs if needed
    $altegio_service_ids = [];
    foreach ($service_ids as $service_id) {
        $altegio_id = get_post_meta($service_id, 'altegio_id', true);
        if (!empty($altegio_id)) {
            $altegio_service_ids[] = (int)$altegio_id;
        } else {
            // If no Altegio ID, use the WordPress ID as fallback
            $altegio_service_ids[] = (int)$service_id;
        }
    }

    error_log('Sending to API getBookTimes: staff_id=' . $staff_id . ', date=' . $date . ', service_ids=' . json_encode($altegio_service_ids));

    try {
        $response = AltegioClient::getBookTimes($staff_id, $date, $altegio_service_ids);

        if (!isset($response['data']) || !is_array($response['data'])) {
            // Generate fallback time slots
            $fallback_slots = generate_fallback_time_slots($date);
            wp_send_json_success($fallback_slots);
            return;
        }

        // Success - return the time slots array directly
        wp_send_json_success($response['data']);
    } catch (Exception $e) {
        // Log error and send fallback data
        error_log('Error in AltegioClient::getBookTimes: ' . $e->getMessage());
        $fallback_slots = generate_fallback_time_slots($date);
        wp_send_json_success($fallback_slots);
    }
}

/**
 * Generate fallback time slots when API fails
 * 
 * @param string $date Date in YYYY-MM-DD format
 * @return array Array of time slot objects
 */
function generate_fallback_time_slots($date)
{
    $slots = [];
    $start_hour = 9; // 9 AM
    $end_hour = 19; // 7 PM
    $interval = 30; // 30 minutes

    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
        for ($min = 0; $min < 60; $min += $interval) {
            // Skip some slots randomly to make it realistic
            if (mt_rand(0, 4) > 0) { // 80% chance of being available
                $time = sprintf('%02d:%02d', $hour, $min);
                $slots[] = [
                    'time' => $time,
                    'seance_length' => 1800, // 30 minutes in seconds
                    'datetime' => $date . 'T' . $time . ':00'
                ];
            }
        }
    }

    return $slots;
}

// Register AJAX handlers
add_action('wp_ajax_get_time_slots', 'ajax_get_time_slots');
add_action('wp_ajax_nopriv_get_time_slots', 'ajax_get_time_slots');
/**
 * AJAX handler for fetching services for a specific master
 */
function get_services_for_master()
{
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'booking_nonce')) {
        // Nonce verification failed
        wp_send_json_error(array('message' => 'Nonce verification failed.'));
        return;
    }

    // Check staff_id
    if (!isset($_POST['staff_id']) || empty($_POST['staff_id'])) {
        // Staff ID is missing
        wp_send_json_error(array('message' => 'Staff ID is missing.'));
        return;
    }

    /*******  5ce7c6c7-cd23-47e6-a20c-42c30259a775  *******/
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
function get_services_by_staff($staff_id)
{
    error_log('Looking for services for staff_id: ' . $staff_id);

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
        error_log("No master found with altegio_id: $staff_id");
        return [];
    }

    $post = $query->posts[0];
    error_log("Found master post ID: " . $post->ID);

    $related_services = get_field('related_services', $post->ID);

    if (!$related_services || !is_array($related_services)) {
        error_log("No related_services for master post ID: " . $post->ID);
        return [];
    }

    error_log("Related services count: " . count($related_services));

    $services = [];
    foreach ($related_services as $service_id) {
        // Check if service is online
        $is_online = get_post_meta($service_id, 'is_online', true);
        if (!$is_online) continue;

        $services[] = array(
            'id'         => $service_id,
            'altegio_id' => get_field('altegio_id', $service_id),
            'title'      => get_the_title($service_id),
            'price'      => get_field('price_min', $service_id) ?: '0',
            'currency'   => 'SGD',
            'duration'   => get_field('duration_minutes', $service_id),
            'wear_time'  => get_field('wear_time', $service_id),
            'desc'       => get_the_content(null, false, $service_id),
            'is_addon'   => get_field('is_addon', $service_id) === 'yes',
        );
    }


    return $services;
}

/**
 * AJAX handler for checking promo codes
 */

function altegio_check_promo_code()
{
    check_ajax_referer('booking_nonce', 'nonce');

    $promo_code = isset($_POST['promo_code']) ? sanitize_text_field($_POST['promo_code']) : '';

    if (empty($promo_code)) {
        wp_send_json_error(['message' => 'Promo code is required']);
        return;
    }

    // Get promo codes from ACF options
    $promo_codes = get_field('promo_codes', 'option');

    if (!$promo_codes || !is_array($promo_codes)) {
        wp_send_json_error(['message' => 'Invalid promo code']);
        return;
    }

    $current_date = date('Y-m-d');
    $found_promo = null;

    $promo_input_code = trim($promo_code);

    foreach ($promo_codes as $promo) {
        $stored_code = isset($promo['promo_code']) ? trim($promo['promo_code']) : '';
        if (strcasecmp($stored_code, $promo_input_code) !== 0) {
            continue;
        }

        // Check if promo code is active
        if (!isset($promo['is_active']) || !$promo['is_active']) {
            continue;
        }

        // Check expiration date if set
        if (!empty($promo['expiration_date'])) {
            $expiry_date_raw = $promo['expiration_date'];

            // Якщо масив, беремо .date
            if (is_array($expiry_date_raw)) {
                $expiry_date_raw = $expiry_date_raw['date'] ?? '';
            }

            try {
                // Спробуємо розпарсити d/m/Y
                $expiry_date_obj = DateTime::createFromFormat('d/m/Y', $expiry_date_raw);
                if ($expiry_date_obj === false) {
                    // Фолбек: спроба парсити будь-який інший формат
                    $expiry_date_obj = new DateTime($expiry_date_raw);
                }

                $expiry_date = $expiry_date_obj->format('Y-m-d');

                if ($current_date > $expiry_date) {
                    continue; // Expired
                }
            } catch (Exception $e) {
                continue; // Invalid date
            }
        }


        // Check usage limit if set
        if (!empty($promo['max_usages']) && $promo['max_usages'] > 0) {
            $usage_count = get_option('promo_usage_' . $stored_code, 0);
            if ($usage_count >= $promo['max_usages']) {
                wp_send_json_error(['message' => 'Promo code usage limit exceeded']);
                return;
            }
        }

        $found_promo = $promo;
        break;
    }


    if (!$found_promo) {
        wp_send_json_error(['message' => 'Invalid or expired coupon']);
        return;
    }

    // Get discount value
    $discount_value = floatval($found_promo['discount_value'] ?? 0);

    if ($discount_value <= 0) {
        wp_send_json_error(['message' => 'Invalid discount value']);
        return;
    }

    wp_send_json_success([
        'promo_code' => $found_promo['promo_code'],
        'discount_value' => $discount_value,
        'message' => 'Your coupon has been successfully applied!'
    ]);
}

add_action('wp_ajax_check_promo_code', 'altegio_check_promo_code');
add_action('wp_ajax_nopriv_check_promo_code', 'altegio_check_promo_code');

/**
 * Increment promo code usage count after successful booking
 */
function increment_promo_usage($promo_code)
{
    $promo_code = trim($promo_code);

    if (empty($promo_code)) {
        return;
    }

    $usage_count = get_option('promo_usage_' . $promo_code, 0);
    $usage_count++;
    update_option('promo_usage_' . $promo_code, $usage_count);
}

function handle_get_month_availability()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'booking_nonce')) {
        error_log("Month availability: Nonce verification failed");
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }

    $staff_id = isset($_POST['staff_id']) ? (int)$_POST['staff_id'] : 0;
    $start_date_str = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';

    if (empty($staff_id) || empty($start_date_str)) {
        error_log("Month availability: Missing parameters - staff_id={$staff_id}, start_date={$start_date_str}");
        wp_send_json_error(['message' => 'Missing required parameters']);
        return;
    }

    try {
        $start_date = new DateTime($start_date_str);
        $start_date->modify('first day of this month');
    } catch (Exception $e) {
        error_log("Month availability: Invalid date format - {$start_date_str}");
        wp_send_json_error(['message' => 'Invalid date format']);
        return;
    }

    if (!class_exists('AltegioClient')) {
        error_log("Month availability: AltegioClient class not found");
        wp_send_json_error(['message' => 'API client not available']);
        return;
    }

    $month_key = $start_date->format('Y-m');
    $transient_key = "altegio_month_availability_{$staff_id}_{$month_key}";
    $cached_data = get_transient($transient_key);

    if (false !== $cached_data) {
        error_log("Month availability: Returning cached data for {$month_key}");
        wp_send_json_success($cached_data);
        return;
    }

    $end_date = clone $start_date;
    $end_date->modify('last day of this month');

    error_log("Month availability: Calling Altegio API for staff {$staff_id} from {$start_date->format('Y-m-d')} to {$end_date->format('Y-m-d')}");

    $schedule_response = AltegioClient::getSchedule(
        $staff_id,
        $start_date->format('Y-m-d'),
        $end_date->format('Y-m-d')
    );

    if (!$schedule_response['success']) {
        $error_msg = $schedule_response['error'] ?? 'Unknown Altegio API error';
        error_log("Month availability: Altegio API error - {$error_msg}");

        $fallback_data = generate_fallback_month_availability($start_date);
        wp_send_json_success($fallback_data);
        return;
    }

    $working_days = [];
    $schedule_data = $schedule_response['data'] ?? [];

    if (!empty($schedule_data)) {
        foreach ($schedule_data as $day_schedule) {
            $is_working = false;

            if (isset($day_schedule['is_working']) && $day_schedule['is_working'] == 1) {
                $is_working = true;
            } elseif (isset($day_schedule['is_working_day']) && $day_schedule['is_working_day'] == 1) {
                $is_working = true;
            } elseif (isset($day_schedule['working']) && $day_schedule['working'] == true) {
                $is_working = true;
            }

            if ($is_working && isset($day_schedule['date'])) {
                try {
                    $api_date = $day_schedule['date'];

                    if (is_array($api_date) && isset($api_date['date'])) {
                        $api_date = $api_date['date'];
                    }

                    $date_obj = new DateTime($api_date);
                    $working_days[] = $date_obj->format('Y-m-d');
                } catch (Exception $e) {
                    error_log("Month availability: Error parsing date - {$api_date}");
                    continue;
                }
            }
        }
    }

    $available_dates = array_unique($working_days);
    $unavailable_dates = [];

    $current_date = clone $start_date;
    $days_in_month = (int)$start_date->format('t');
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    for ($i = 0; $i < $days_in_month; $i++) {
        $date_str = $current_date->format('Y-m-d');

        if ($current_date < $today) {
            $unavailable_dates[] = $date_str;
        } elseif (!in_array($date_str, $available_dates)) {
            $unavailable_dates[] = $date_str;
        }

        $current_date->modify('+1 day');
    }

    $result_data = [
        'available_dates' => $available_dates,
        'unavailable_dates' => $unavailable_dates,
        'staff_id' => $staff_id,
        'month' => $month_key,
        'total_days' => $days_in_month,
        'working_days_count' => count($available_dates),
        'generated_at' => current_time('mysql')
    ];

    $cache_duration = ($month_key === date('Y-m')) ? 2 * HOUR_IN_SECONDS : 24 * HOUR_IN_SECONDS;
    set_transient($transient_key, $result_data, $cache_duration);

    error_log("Month availability: Successfully processed {$days_in_month} days, {$result_data['working_days_count']} working days");

    wp_send_json_success($result_data);
}

function generate_fallback_month_availability($start_date)
{
    $available_dates = [];
    $unavailable_dates = [];

    $current_date = clone $start_date;
    $days_in_month = (int)$start_date->format('t');
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    for ($i = 0; $i < $days_in_month; $i++) {
        $date_str = $current_date->format('Y-m-d');
        $day_of_week = (int)$current_date->format('w');

        if ($current_date < $today) {
            $unavailable_dates[] = $date_str;
        } elseif ($day_of_week === 0) {
            $unavailable_dates[] = $date_str;
        } elseif (mt_rand(1, 10) > 8) {
            $unavailable_dates[] = $date_str;
        } else {
            $available_dates[] = $date_str;
        }

        $current_date->modify('+1 day');
    }

    return [
        'available_dates' => $available_dates,
        'unavailable_dates' => $unavailable_dates,
        'fallback' => true,
        'month' => $start_date->format('Y-m'),
        'generated_at' => current_time('mysql')
    ];
}

function handle_clear_month_availability_cache()
{
    check_ajax_referer('booking_nonce', 'nonce');

    $staff_id = isset($_POST['staff_id']) ? (int)$_POST['staff_id'] : 0;
    $month = isset($_POST['month']) ? sanitize_text_field($_POST['month']) : '';

    if ($staff_id && $month) {
        $transient_key = "altegio_month_availability_{$staff_id}_{$month}";
        delete_transient($transient_key);

        wp_send_json_success(['message' => "Cache cleared for staff {$staff_id}, month {$month}"]);
    } else {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%altegio_month_availability_%'");
        wp_send_json_success(['message' => 'Cache cleared for all months']);
    }
}
add_action('wp_ajax_clear_month_availability_cache', 'handle_clear_month_availability_cache');
add_action('wp_ajax_nopriv_clear_month_availability_cache', 'handle_clear_month_availability_cache');
