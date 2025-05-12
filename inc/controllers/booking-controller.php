<?php
class BookingController
{
    public static function init()
    {
        add_action('wp_ajax_get_services', [self::class, 'ajaxGetServices']);
        add_action('wp_ajax_nopriv_get_services', [self::class, 'ajaxGetServices']);

        add_action('wp_ajax_get_staff', [self::class, 'ajaxGetStaff']);
        add_action('wp_ajax_nopriv_get_staff', [self::class, 'ajaxGetStaff']);

        add_action('wp_ajax_get_time_slots', [self::class, 'ajaxGetTimeSlots']);
        add_action('wp_ajax_nopriv_get_time_slots', [self::class, 'ajaxGetTimeSlots']);

        add_action('wp_ajax_submit_booking', [self::class, 'ajaxSubmitBooking']);
        add_action('wp_ajax_nopriv_submit_booking', [self::class, 'ajaxSubmitBooking']);

        add_action('wp_ajax_get_categories', [self::class, 'ajaxGetCategories']);
        add_action('wp_ajax_nopriv_get_categories', [self::class, 'ajaxGetCategories']);

        add_action('wp_ajax_get_booking_settings', [self::class, 'ajaxGetBookingSettings']);
        add_action('wp_ajax_nopriv_get_booking_settings', [self::class, 'ajaxGetBookingSettings']);

        add_action('wp_ajax_get_i18n', [self::class, 'ajaxGetI18n']);
        add_action('wp_ajax_nopriv_get_i18n', [self::class, 'ajaxGetI18n']);
    }



    private static function getTranslations()
    {
        return [
            'select_services' => 'Select services',
            'choose_master' => 'Choose a master',
            'choose_date_time' => 'Choose date & time',
            'contact_details' => 'Your information',
            'confirmation' => 'Confirmation',
            'next' => 'Next',
            'back' => 'Back',
            'book_appointment' => 'Book appointment',
            'loading' => 'Loading...',
            'no_services' => 'No services available',
            'no_staff' => 'No staff available',
            'no_slots' => 'No available time slots for this date',
            'retry' => 'Retry',
            'booking_confirmed' => 'Booking confirmed',
            'booking_failed' => 'Booking failed',
            'all_categories' => 'All categories',
            'name' => 'Name',
            'phone' => 'Phone',
            'email' => 'Email',
            'comment' => 'Comments (optional)',
            'service' => 'Service',
            'master' => 'Master',
            'date_time' => 'Date & time',
            'price' => 'Price',
            'close' => 'Close'
        ];
    }

    public static function ajaxGetServices()
    {
        check_ajax_referer('booking_nonce', '_wpnonce');
        $services = AltegioClient::getServices();

        if (isset($services['error'])) {
            wp_send_json_error([
                'message' => 'Failed to fetch services',
                'details' => $services['error']
            ]);
        }

        wp_send_json_success($services);
    }

    public static function ajaxGetStaff()
    {
        check_ajax_referer('booking_nonce', '_wpnonce');
        $serviceId = isset($_POST['service_id']) ? sanitize_text_field($_POST['service_id']) : '';

        $staff = AltegioClient::getStaff($serviceId);

        if (isset($staff['error'])) {
            wp_send_json_error([
                'message' => 'Failed to fetch staff',
                'details' => $staff['error']
            ]);
        }

        wp_send_json_success($staff);
    }

    public static function ajaxGetCategories()
    {
        check_ajax_referer('booking_nonce', '_wpnonce');

        $categories = AltegioClient::getCategories();

        if (isset($categories['error'])) {
            wp_send_json_error([
                'message' => 'Failed to fetch categories',
                'details' => $categories['error']
            ]);
        }

        wp_send_json_success($categories);
    }

    public static function ajaxGetBookingSettings()
    {
        check_ajax_referer('booking_nonce', '_wpnonce');

        $settings = AltegioClient::getBookingFormSettings();

        if (isset($settings['error'])) {
            $defaultSettings = [
                'success' => true,
                'data' => [
                    'steps' => [
                        [
                            'step' => 'service',
                            'title' => 'Select service',
                            'number' => 1,
                            'hidden' => false
                        ],
                        [
                            'step' => 'master',
                            'title' => 'Choose a master',
                            'number' => 2,
                            'hidden' => false
                        ],
                        [
                            'step' => 'datetime',
                            'title' => 'Choose date and time',
                            'number' => 3,
                            'hidden' => false
                        ],
                        [
                            'step' => 'contact',
                            'title' => 'Contact information',
                            'number' => 4,
                            'hidden' => false
                        ]
                    ],
                    'style' => [
                        'primaryPalette' => 'amber',
                        'accentPalette' => 'white',
                        'warnPalette' => 'orange',
                        'backgroundPalette' => 'white'
                    ],
                    'phone_confirmation' => false,
                    'comment_required' => false,
                    'is_show_privacy_policy' => true
                ]
            ];
            wp_send_json_success($defaultSettings);
        }

        wp_send_json_success($settings);
    }

    public static function ajaxGetI18n()
    {
        check_ajax_referer('booking_nonce', '_wpnonce');

        $i18n = AltegioClient::getI18n();

        if (isset($i18n['error'])) {
            $defaultI18n = [
                'button' => [
                    'select' => 'Select',
                    'select_time' => 'Choose time',
                    'back' => 'Back',
                    'continue' => 'Continue',
                    'confirm' => 'Confirm',
                    'canceling' => 'Cancel'
                ],
                'steps' => [
                    'date_and_time' => 'date and time',
                    'staff' => [
                        'nominative' => 'Staff',
                        'genitive' => 'staff'
                    ],
                    'service' => 'Service',
                    'time' => 'Time'
                ],
                'master' => [
                    'master' => 'Specialist',
                    'skip_select_master' => 'Skip specialist selection',
                    'skip_select' => 'Skip selection',
                    'no_record' => 'No free sessions for the selected day',
                    'no_record_new' => 'No free time for this date. Choose another date or specialist',
                    'record_is_available' => 'You can sign up'
                ],
                'service' => [
                    'selected' => 'Selected',
                    'search' => 'Search...',
                    'services' => 'Services',
                    'add' => 'Add service'
                ],
                'confirm' => [
                    'confirm' => 'Book',
                    'first_name' => 'Name',
                    'phone' => 'Phone',
                    'comment' => 'Comment',
                    'email' => 'Email',
                    'recording' => 'Book'
                ]
            ];
            wp_send_json_success($defaultI18n);
        }

        wp_send_json_success($i18n);
    }

    public static function ajaxGetTimeSlots()
    {
        check_ajax_referer('booking_nonce', '_wpnonce');

        $staff_id = isset($_POST['staff_id']) ? sanitize_text_field($_POST['staff_id']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';

        if (empty($staff_id) || empty($date)) {
            wp_send_json_error([
                'message' => 'Staff ID and date are required'
            ]);
        }

        $timeSlots = AltegioClient::getTimeSlots($staff_id, $date);

        if (isset($timeSlots['error'])) {
            $simulatedSlots = self::getSimulatedTimeSlots($date);
            wp_send_json_success([
                'data' => [
                    'slots' => $simulatedSlots
                ]
            ]);
        }

        wp_send_json_success($timeSlots);
    }

    private static function getSimulatedTimeSlots($date)
    {
        $slots = [];
        $start_time = strtotime('9:00');
        $end_time = strtotime('19:00');
        $interval = 30 * 60; // 30 minutes

        for ($time = $start_time; $time <= $end_time; $time += $interval) {
            $slots[] = $date . ' ' . date('H:i:s', $time);
        }

        return $slots;
    }

    public static function ajaxSubmitBooking()
    {
        check_ajax_referer('booking_nonce', 'booking_nonce');

        $required_fields = ['service_id', 'staff_id', 'date', 'time', 'client_name', 'client_phone', 'client_email'];
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
        }

        if (isset($_POST['client_comment'])) {
            $booking_data['client_comment'] = sanitize_textarea_field($_POST['client_comment']);
        }

        $result = AltegioClient::submitBooking($booking_data);

        if (!$result['success']) {
            wp_send_json_error([
                'message' => $result['message'],
                'details' => $result['details'] ?? ''
            ]);
        }

        wp_send_json_success([
            'message' => $result['message'],
            'booking' => $result['booking']
        ]);
    }

    public static function addBookingPopupToFooter()
    {
?>

<?php
    }
}
