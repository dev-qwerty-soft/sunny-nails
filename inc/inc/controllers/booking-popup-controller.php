<?php

/**
 * Booking Popup Controller
 * Manages the booking popup and its functionality
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

        // Add popup HTML to footer
        add_action('wp_footer', [self::class, 'render_popup']);

        // Register AJAX handler for time slots
        add_action('wp_ajax_get_time_slots', [self::class, 'ajax_get_time_slots']);
        add_action('wp_ajax_nopriv_get_time_slots', [self::class, 'ajax_get_time_slots']);

        // Register AJAX handler for booking submission
        add_action('wp_ajax_submit_booking', [self::class, 'ajax_submit_booking']);
        add_action('wp_ajax_nopriv_submit_booking', [self::class, 'ajax_submit_booking']);
    }

    /**
     * Register and enqueue assets
     */
    public static function register_assets()
    {
        // Check if CSS file exists before enqueuing
        $css_file = get_template_directory() . '/assets/css/booking-popup.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'booking-popup',
                get_template_directory_uri() . '/assets/css/booking-popup.css',
                [],
                filemtime($css_file)
            );
        }

        // Check if JS file exists before enqueuing
        $js_file = get_template_directory() . '/assets/js/booking-popup.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'booking-popup',
                get_template_directory_uri() . '/assets/js/booking-popup.js',
                ['jquery'],
                filemtime($js_file),
                true
            );

            // Localize script with data
            wp_localize_script('booking-popup', 'bookingPopupData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('booking_popup_nonce'),
                'companyId' => AltegioClient::COMPANY_ID,
            ]);
        }
    }

    /**
     * Render the booking popup HTML
     */
    public static function render_popup()
    {
        // Get necessary data from API
        $staff_list = AltegioClient::getStaff();
        $services = AltegioClient::getServices();

        // Include the template with file existence check
        // ИЗМЕНЕНО: Путь к шаблону теперь включает подпапку booking
        $template_path = get_template_directory() . '/template-parts/booking/booking-popup.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo "<!-- Warning: Booking popup template not found at {$template_path} -->";
            self::generate_inline_popup($staff_list, $services);
        }
    }

    /**
     * Generate a basic inline popup if the template file is missing
     * This is a fallback method to ensure functionality even if file structure is incorrect
     */
    private static function generate_inline_popup($staff_list, $services)
    {
?>
        <!-- Fallback Booking Popup -->
        <style>
            .booking-popup-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.7);
                z-index: 9999;
                overflow-y: auto;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .booking-popup {
                position: relative;
                max-width: 500px;
                width: 100%;
                background-color: #F9F5E7;
                border-radius: 10px;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
                overflow: hidden;
            }

            .booking-popup-close {
                position: absolute;
                top: 20px;
                right: 20px;
                width: 30px;
                height: 30px;
                background: none;
                border: none;
                font-size: 24px;
                line-height: 1;
                cursor: pointer;
                z-index: 10;
                color: #333;
            }

            .booking-popup-content {
                padding: 30px;
            }

            .booking-title {
                text-align: center;
                font-size: 28px;
                margin: 0 0 30px;
                color: #060F07;
            }

            .booking-option-item {
                display: flex;
                align-items: center;
                padding: 20px;
                margin-bottom: 15px;
                background-color: #fff;
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.2s ease;
                position: relative;
            }

            .booking-option-item:first-child {
                background-color: #FFC107;
                color: #060F07;
            }

            .option-text {
                flex: 1;
                font-size: 16px;
                font-weight: 500;
            }

            .next-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 12px 25px;
                background-color: #FFC107;
                color: #060F07;
                border: none;
                border-radius: 30px;
                font-size: 16px;
                font-weight: 500;
                cursor: pointer;
                margin: 30px auto 0;
            }
        </style>

        <div class="booking-popup-overlay">
            <div class="booking-popup">
                <button class="booking-popup-close">&times;</button>

                <div class="booking-popup-content">
                    <h2 class="booking-title">Book an appointment</h2>

                    <div class="booking-steps-container">
                        <div class="booking-step active" data-step="initial">
                            <div class="booking-option-item" data-option="services">
                                <div class="option-text">Select services</div>
                            </div>

                            <div class="booking-option-item" data-option="master">
                                <div class="option-text">Choose a master</div>
                            </div>

                            <button type="button" class="next-btn">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                // Basic popup functionality
                $('.open-popup').on('click', function(e) {
                    e.preventDefault();
                    $('.booking-popup-overlay').fadeIn(300);
                });

                $('.booking-popup-close').on('click', function() {
                    $('.booking-popup-overlay').fadeOut(300);
                });

                $('.booking-popup-overlay').on('click', function(e) {
                    if ($(e.target).is('.booking-popup-overlay')) {
                        $('.booking-popup-overlay').fadeOut(300);
                    }
                });

                // Option selection
                $('.booking-option-item').on('click', function() {
                    $('.booking-option-item').removeClass('active');
                    $(this).addClass('active');
                });

                // Next button
                $('.next-btn').on('click', function() {
                    alert('This is a fallback popup. Please make sure all template files are correctly installed.');
                });
            });
        </script>
<?php
    }

    /**
     * AJAX handler for getting time slots
     */
    public static function ajax_get_time_slots()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'booking_popup_nonce')) {
            wp_send_json_error(['message' => 'Invalid security token']);
            exit;
        }

        // Get parameters
        $staff_id = isset($_POST['staff_id']) ? sanitize_text_field($_POST['staff_id']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';

        if (empty($staff_id) || empty($date)) {
            wp_send_json_error(['message' => 'Missing required parameters']);
            exit;
        }

        // Get time slots from API
        $time_slots = AltegioClient::getTimeSlots($staff_id, $date);

        wp_send_json_success($time_slots);
        exit;
    }

    /**
     * AJAX handler for submitting booking
     */
    public static function ajax_submit_booking()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'booking_popup_nonce')) {
            wp_send_json_error(['message' => 'Invalid security token']);
            exit;
        }

        // Get booking data
        $booking_data = isset($_POST['booking_data']) ? $_POST['booking_data'] : [];

        // Validate required fields
        if (
            empty($booking_data['staffId']) ||
            empty($booking_data['services']) ||
            empty($booking_data['date']) ||
            empty($booking_data['time']) ||
            empty($booking_data['contact']['name']) ||
            empty($booking_data['contact']['phone'])
        ) {

            wp_send_json_error(['message' => 'Missing required booking information']);
            exit;
        }

        // Format booking data for API
        $api_booking_data = [
            'company_id' => AltegioClient::COMPANY_ID,
            'staff_id' => sanitize_text_field($booking_data['staffId']),
            'services' => array_map('absint', array_column($booking_data['services'], 'id')),
            'datetime' => sanitize_text_field($booking_data['date'] . ' ' . $booking_data['time']),
            'client' => [
                'name' => sanitize_text_field($booking_data['contact']['name']),
                'phone' => sanitize_text_field($booking_data['contact']['phone']),
                'email' => sanitize_email($booking_data['contact']['email'] ?? ''),
                'comment' => sanitize_textarea_field($booking_data['contact']['comment'] ?? ''),
            ]
        ];

        // Submit booking to API
        $result = AltegioClient::makeBooking($api_booking_data);

        if (isset($result['error'])) {
            wp_send_json_error([
                'message' => 'Booking failed',
                'error' => $result['error']
            ]);
            exit;
        }

        wp_send_json_success([
            'message' => 'Booking created successfully',
            'booking_id' => $result['data']['id'] ?? '',
            'reference' => $result['data']['reference'] ?? ('BK' . random_int(1000, 9999))
        ]);
        exit;
    }
}
