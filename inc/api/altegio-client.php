<?php
class AltegioClient
{
    // Changed from private to public to allow access from outside the class
    public const BASE_URL = 'https://api.alteg.io/api/v1/';
    public const PARTNER_TOKEN = 'becwbyhjwdf2s37fcmze';
    public const USER_TOKEN = '24b2f3cc652a7c7574290d8426823404';
    public const COMPANY_ID = '1275515';

    private static function request(string $endpoint, array $params = []): array
    {
        $url = self::BASE_URL . ltrim($endpoint, '/');

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $args = [
            'headers' => [
                'Accept'        => 'application/vnd.api.v2+json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . self::PARTNER_TOKEN . ', User ' . self::USER_TOKEN,
            ],
            'timeout' => 15,
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200) {
            return ['error' => 'HTTP ' . $code, 'body' => $data];
        }

        return $data;
    }

    public static function getServices(): array
    {
        return self::request('company/' . self::COMPANY_ID . '/services');
    }

    public static function getStaff(): array
    {
        return self::request('company/' . self::COMPANY_ID . '/staff');
    }

    public static function getCategories(): array
    {
        $response = self::request('goods/search/' . self::COMPANY_ID);

        if (!isset($response['success']) || !$response['success'] || !isset($response['data'])) {
            return ['success' => 0, 'data' => []];
        }

        $categories = array_filter($response['data'], function ($item) {
            return isset($item['is_category']) && $item['is_category'] === true;
        });

        return [
            'success' => 1,
            'data' => array_values($categories),
        ];
    }

    public static function getBookingForm(int $formId = null): array
    {
        if ($formId === null) {
            $formId = self::COMPANY_ID;
        }
        return self::request('bookform/' . $formId);
    }

    // Adding the missing methods

    public static function getI18n(string $langCode = 'en-US'): array
    {
        return self::request('i18n', ['lang' => $langCode]);
    }

    public static function getTimeSlots(string $staffId, string $date): array
    {
        return self::request('book_times', [
            'company_id' => self::COMPANY_ID,
            'staff_id' => $staffId,
            'date' => $date
        ]);
    }

    // Method to make a booking
    public static function makeBooking(array $bookingData): array
    {
        $url = self::BASE_URL . 'book';

        $args = [
            'method' => 'POST',
            'headers' => [
                'Accept'        => 'application/vnd.api.v2+json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . self::PARTNER_TOKEN . ', User ' . self::USER_TOKEN,
            ],
            'body' => json_encode($bookingData),
            'timeout' => 15,
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200) {
            return ['error' => 'HTTP ' . $code, 'body' => $data];
        }

        return $data;
    }
    public static function ajaxGetTimeSlots()
    {
        // Verify the nonce for security
        check_ajax_referer('booking_nonce', 'nonce');

        // Get parameters from POST request
        $staff_id = isset($_POST['staff_id']) ? sanitize_text_field($_POST['staff_id']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';

        // Validate required parameters
        if (empty($staff_id) || empty($date)) {
            wp_send_json_error([
                'message' => 'Staff ID and date are required'
            ]);
            return;
        }

        // Get time slots from Altegio API
        $timeSlots = AltegioClient::getTimeSlots($staff_id, $date);

        // Check if the API call was successful
        if (isset($timeSlots['error'])) {
            error_log('Altegio API Error: ' . print_r($timeSlots['error'], true));

            // For better UX, return empty slots instead of an error
            wp_send_json_success([
                'slots' => [],
                'message' => 'No available time slots for this date.'
            ]);
            return;
        }

        // Process the response to extract time slots in a consistent format
        $slots = [];

        // Handle different possible response structures from Altegio API
        if (isset($timeSlots['data']['slots'])) {
            $slots = $timeSlots['data']['slots'];
        } elseif (isset($timeSlots['slots'])) {
            $slots = $timeSlots['slots'];
        } elseif (isset($timeSlots['data']) && is_array($timeSlots['data'])) {
            $slots = $timeSlots['data'];
        }

        // Return the slots to the frontend
        wp_send_json_success([
            'slots' => $slots,
            'message' => empty($slots) ? 'No available time slots for this date.' : null
        ]);
    }
}
