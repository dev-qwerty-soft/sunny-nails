<?php
// Add this code to your WordPress theme's functions.php or a dedicated plugin file

/**
 * Register and enqueue booking system assets
 */
function altegio_booking_enqueue_scripts()
{
    // Enqueue main CSS file
    wp_enqueue_style(
        'altegio-booking-styles',
        get_template_directory_uri() . '/assets/css/booking.css',
        array(),
        filemtime(get_template_directory() . '/assets/css/booking.css')
    );

    // Enqueue main JS file
    wp_enqueue_script(
        'altegio-booking-script',
        get_template_directory_uri() . '/assets/js/booking.js',
        array('jquery'),
        filemtime(get_template_directory() . '/assets/js/booking.js'),
        true
    );

    // Localize script with necessary data for AJAX calls
    wp_localize_script(
        'altegio-booking-script',
        'booking_params',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('booking_nonce'),
            'site_url' => site_url(),
            'date_format' => get_option('date_format'),
            'time_format' => get_option('time_format')
        )
    );
}
add_action('wp_enqueue_scripts', 'altegio_booking_enqueue_scripts');

/**
 * AJAX handler for getting time slots
 * This function handles both logged-in and non-logged-in users
 */
function altegio_get_time_slots()
{
    // Check nonce for security
    check_ajax_referer('booking_nonce', 'nonce');

    // Get parameters
    $staff_id = isset($_POST['staff_id']) ? sanitize_text_field($_POST['staff_id']) : '';
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';

    // Validate required parameters
    if (empty($staff_id) || empty($date)) {
        wp_send_json_error(array('message' => 'Missing required parameters'));
        return;
    }

    // Try to get time slots from Altegio API
    if (class_exists('AltegioClient')) {
        try {
            $time_slots = AltegioClient::getTimeSlots($staff_id, $date);

            // Check if we got valid data
            if (!empty($time_slots) && isset($time_slots['success']) && $time_slots['success']) {
                wp_send_json_success(array('slots' => $time_slots['data'] ?? array()));
                return;
            }
        } catch (Exception $e) {
            // Log error but don't expose details to front-end
            error_log('Altegio API error: ' . $e->getMessage());
        }
    }

    // If we reach here, use fallback data
    $slots = altegio_generate_fallback_time_slots($date);
    wp_send_json_success(array('slots' => $slots));
}

// Register AJAX handlers for both logged-in and non-logged-in users
add_action('wp_ajax_get_time_slots', 'altegio_get_time_slots');
add_action('wp_ajax_nopriv_get_time_slots', 'altegio_get_time_slots');

/**
 * Generate fallback time slots when API fails
 */
function altegio_generate_fallback_time_slots($date)
{
    $date_obj = new DateTime($date);
    $day_of_week = $date_obj->format('w'); // 0 = Sunday, 6 = Saturday

    // No slots on Sundays for example
    if ($day_of_week == 0) {
        return array();
    }

    // Reduced slots on Saturdays
    if ($day_of_week == 6) {
        $all_slots = array('10:00:00', '12:00:00');
    } else {
        // Regular weekday slots
        $morning_slots = array('10:00:00');
        $day_slots = array('12:00:00', '14:00:00', '16:00:00');
        $evening_slots = array('18:00:00');
        $all_slots = array_merge($morning_slots, $day_slots, $evening_slots);
    }

    // Randomly disable some slots to make it realistic
    $disable_count = mt_rand(1, 2);

    for ($i = 0; $i < $disable_count; $i++) {
        if (count($all_slots) > 1) { // Keep at least one slot
            $random_index = array_rand($all_slots);
            unset($all_slots[$random_index]);
        }
    }

    // Format slots with date
    $formatted_slots = array();
    foreach ($all_slots as $time) {
        $formatted_slots[] = $date . ' ' . $time;
    }

    return $formatted_slots;
}

/**
 * AJAX handler для перевірки доступності місяця
 */
function handle_check_month_availability()
{
    // Перевірка nonce
    if (!wp_verify_nonce($_POST['nonce'], 'booking_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    $staff_id = isset($_POST['staff_id']) ? sanitize_text_field($_POST['staff_id']) : '';
    $dates_json = isset($_POST['dates']) ? sanitize_text_field($_POST['dates']) : '';
    $service_ids_json = isset($_POST['service_ids']) ? sanitize_text_field($_POST['service_ids']) : '';

    if (empty($staff_id)) {
        wp_send_json_error(['message' => 'Missing staff_id parameter']);
        return;
    }

    $dates = json_decode($dates_json, true);
    $service_ids = json_decode($service_ids_json, true);

    if (!is_array($dates) || !is_array($service_ids)) {
        wp_send_json_error(['message' => 'Invalid JSON data format']);
        return;
    }

    if (empty($dates)) {
        wp_send_json_success([
            'available_dates' => [],
            'unavailable_dates' => [],
            'total_checked' => 0,
            'available_count' => 0,
            'unavailable_count' => 0
        ]);
        return;
    }

    $available_dates = [];
    $unavailable_dates = [];

    // Простий алгоритм для демонстрації
    foreach ($dates as $date_str) {
        $date_obj = new DateTime($date_str);
        $day_of_week = $date_obj->format('w'); // 0 = Sunday, 6 = Saturday

        // Неділя недоступна
        if ($day_of_week == 0) {
            $unavailable_dates[] = $date_str;
        }
        // Випадково робимо деякі дати недоступними (для демонстрації)
        else if (rand(1, 10) <= 2) { // 20% шанс що дата недоступна
            $unavailable_dates[] = $date_str;
        } else {
            $available_dates[] = $date_str;
        }
    }

    wp_send_json_success([
        'available_dates' => $available_dates,
        'unavailable_dates' => $unavailable_dates,
        'total_checked' => count($dates),
        'available_count' => count($available_dates),
        'unavailable_count' => count($unavailable_dates)
    ]);
}

// Реєструємо AJAX обробники
add_action('wp_ajax_check_month_availability', 'handle_check_month_availability');
add_action('wp_ajax_nopriv_check_month_availability', 'handle_check_month_availability');

/**
 * AJAX handler for clearing availability cache
 */
function altegio_clear_availability_cache()
{
    check_ajax_referer('booking_nonce', 'nonce');

    // Clear all availability cache
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_altegio_availability_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_altegio_availability_%'");

    wp_send_json_success(array('message' => 'Availability cache cleared'));
}

// Register AJAX handlers
add_action('wp_ajax_clear_availability_cache', 'altegio_clear_availability_cache');
add_action('wp_ajax_nopriv_clear_availability_cache', 'altegio_clear_availability_cache');
