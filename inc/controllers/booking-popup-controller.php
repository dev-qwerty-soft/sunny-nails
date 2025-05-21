<?php

/**
 * Booking Popup Controller
 * Manages all booking popup functionality
 */
class BookingPopupController
{
    /**
     * Initialize the controller
     */
    public static function init()
    {
        // Register assets
        add_action('wp_enqueue_scripts', [self::class, 'register_assets']);

        // Add popup to footer
        add_action('wp_footer', [self::class, 'render_popup']);

        // Register AJAX handlers
        add_action('wp_ajax_get_staff', [self::class, 'ajax_get_staff']);
        add_action('wp_ajax_nopriv_get_staff', [self::class, 'ajax_get_staff']);

        add_action('wp_ajax_get_time_slots', [self::class, 'ajax_get_time_slots']);
        add_action('wp_ajax_nopriv_get_time_slots', [self::class, 'ajax_get_time_slots']);

        add_action('wp_ajax_submit_booking', [self::class, 'ajax_submit_booking']);
        add_action('wp_ajax_nopriv_submit_booking', [self::class, 'ajax_submit_booking']);
    }

    /**
     * Register and enqueue assets
     */
    public static function register_assets()
    {
        // Register CSS
        $css_file = get_template_directory() . '/assets/css/booking.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'booking-styles',
                get_template_directory_uri() . '/assets/css/booking.css',
                [],
                filemtime($css_file)
            );
        }

        // Register JS
        $js_file = get_template_directory() . '/assets/js/booking.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'booking-script',
                get_template_directory_uri() . '/assets/js/booking.js',
                ['jquery'],
                filemtime($js_file),
                true
            );

            // Localize script with data
            wp_localize_script('booking-script', 'booking_params', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('booking_popup_nonce'),
            ]);
        }
    }

    /**
     * Render the booking popup
     */
    public static function render_popup()
    {
        // Get data for popup
        $staff_list = self::get_staff();

        // Include template
        $template_path = get_template_directory() . '/template-parts/booking/booking-popup.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<!-- Booking popup template not found: ' . $template_path . ' -->';
        }
    }

    /**
     * Get staff from API or fallback
     */
    private static function get_staff()
    {
        // Try to get staff from API
        if (class_exists('AltegioClient')) {
            $staff = AltegioClient::getStaff();

            if (!empty($staff['data'])) {
                return $staff;
            }
        }

        // Fallback to sample data
        return [
            'data' => [
                [
                    'id' => '1',
                    'name' => 'Alice Smith',
                    'specialization' => 'Nail Technician',
                    'avatar' => 'https://randomuser.me/api/portraits/women/32.jpg'
                ],
                [
                    'id' => '2',
                    'name' => 'Emma Johnson',
                    'specialization' => 'Senior Nail Artist',
                    'avatar' => 'https://randomuser.me/api/portraits/women/44.jpg'
                ],
                [
                    'id' => '3',
                    'name' => 'Sophia Davis',
                    'specialization' => 'Nail Art Specialist',
                    'avatar' => 'https://randomuser.me/api/portraits/women/68.jpg'
                ]
            ]
        ];
    }

    /**
     * AJAX handler for getting staff (filtered by service if provided)
     */
    public static function ajax_get_staff()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'booking_popup_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            exit;
        }

        // Get service ID if provided
        $service_id = isset($_POST['service_id']) ? sanitize_text_field($_POST['service_id']) : '';

        // Try to get staff from API
        if (class_exists('AltegioClient')) {
            $staff = AltegioClient::getStaff($service_id);

            if (!isset($staff['error'])) {
                wp_send_json_success($staff);
                exit;
            }
        }

        // Fallback to sample data
        $sample_staff = [
            'data' => [
                [
                    'id' => '1',
                    'name' => 'Alice Smith',
                    'specialization' => 'Nail Technician',
                    'avatar' => 'https://randomuser.me/api/portraits/women/32.jpg'
                ],
                [
                    'id' => '2',
                    'name' => 'Emma Johnson',
                    'specialization' => 'Senior Nail Artist',
                    'avatar' => 'https://randomuser.me/api/portraits/women/44.jpg'
                ],
                [
                    'id' => '3',
                    'name' => 'Sophia Davis',
                    'specialization' => 'Nail Art Specialist',
                    'avatar' => 'https://randomuser.me/api/portraits/women/68.jpg'
                ]
            ]
        ];

        wp_send_json_success($sample_staff);
        exit;
    }

    /**
     * Generate fallback time slots for demo purposes
     */
    private static function generate_fallback_time_slots($date)
    {
        $slots = [];
        $start_hour = 9; // 9 AM
        $end_hour = 19; // 7 PM
        $interval = 30; // 30 minutes

        for ($hour = $start_hour; $hour < $end_hour; $hour++) {
            for ($min = 0; $min < 60; $min += $interval) {
                // Add some randomization to make it realistic
                if (mt_rand(0, 4) > 0) { // 80% chance of being available
                    $time = sprintf('%02d:%02d:00', $hour, $min);
                    $slots[] = $date . ' ' . $time;
                }
            }
        }

        return $slots;
    }

    /**
     * AJAX handler for submitting booking
     */
    public static function ajax_submit_booking()
    {
        // Verify nonce
        if (!isset($_POST['booking_nonce']) || !wp_verify_nonce($_POST['booking_nonce'], 'booking_popup_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            exit;
        }

        // Check required fields
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
                'message' => 'Missing required fields',
                'fields' => $missing_fields
            ]);
            exit;
        }

        // Handle optional fields
        if (isset($_POST['client_email'])) {
            $booking_data['client_email'] = sanitize_email($_POST['client_email']);
        }

        if (isset($_POST['client_comment'])) {
            $booking_data['client_comment'] = sanitize_textarea_field($_POST['client_comment']);
        }

        // Parse service IDs
        $wp_service_ids = explode(',', $booking_data['service_id']);
        $service_altegios = array_map(function ($post_id) {
            return (int) get_post_meta($post_id, 'altegio_id', true);
        }, $wp_service_ids);


        // Format data for API
        $api_data = [
            'company_id' => AltegioClient::COMPANY_ID,
            'staff_id' => (int) $booking_data['staff_id'],
            'datetime' => $booking_data['date'] . 'T' . $booking_data['time'] . ':00',
            'services' => array_map(function ($id) {
                return ['id' => (int) $id];
            }, $service_ids),
            'client' => [
                'name' => $booking_data['client_name'],
                'phone' => $booking_data['client_phone']
            ]
        ];


        // Add optional client fields
        if (isset($booking_data['client_email'])) {
            $api_data['client']['email'] = $booking_data['client_email'];
        }

        if (isset($booking_data['client_comment'])) {
            $api_data['client']['comment'] = $booking_data['client_comment'];
        }

        // Try to submit booking to API
        if (class_exists('AltegioClient')) {
            $result = AltegioClient::submitBooking($api_data);

            if (!isset($result['error'])) {
                wp_send_json_success([
                    'message' => 'Booking created successfully',
                    'booking' => $result['booking'] ?? null
                ]);
                exit;
            }
        }

        // Fallback to demo response
        $reference = 'BK' . mt_rand(1000, 9999);

        wp_send_json_success([
            'message' => 'Booking created successfully (demo mode)',
            'booking' => [
                'id' => uniqid('demo_'),
                'reference' => $reference,
                'datetime' => $booking_data['date'] . ' ' . $booking_data['time']
            ]
        ]);
        exit;
    }
}
