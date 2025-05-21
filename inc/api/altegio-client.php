<?php
class AltegioClient
{
    public const BASE_URL = 'https://api.alteg.io/api/v1/';
    public const PARTNER_TOKEN = 'becwbyhjwdf2s37fcmze';
    public const USER_TOKEN = '24b2f3cc652a7c7574290d8426823404';
    public const COMPANY_ID = '1275515';

    private static function request(string $endpoint, array $params = [], string $method = 'GET', array $body = []): array
    {
        $url = self::BASE_URL . ltrim($endpoint, '/');

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $args = [
            'headers' => [
                'Accept' => 'application/vnd.api.v2+json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . self::PARTNER_TOKEN,
            ],
            'timeout' => 20,
            'method' => $method,
        ];

        if ($method === 'POST' && !empty($body)) {
            $args['body'] = json_encode($body); // ✅ тіло у форматі JSON
        }

        error_log("Altegio API Request: $method $url");
        if (!empty($body)) {
            error_log("Request body: " . json_encode($body));
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        error_log("Altegio API Response (HTTP $code): " . $body);

        return [
            'success' => in_array($code, [200, 201]),
            'data' => $data,
            'body' => $body,
            'status' => $code
        ];
    }


    public static function getServices(): array
    {
        return self::request('company/' . self::COMPANY_ID . '/services');
    }

    public static function getStaff($serviceId = null): array
    {
        $params = [];

        if ($serviceId) {
            $params['service_id'] = $serviceId;
        }

        return self::request('company/' . self::COMPANY_ID . '/staff', $params);
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

    public static function getTimeSlots(string $staffId, string $date, array $serviceIds = []): array
    {
        $params = [
            'company_id' => self::COMPANY_ID,
            'staff_id' => $staffId,
            'date' => $date
        ];

        if (!empty($serviceIds)) {
            if (is_array($serviceIds)) {
                $params['service_ids'] = implode(',', $serviceIds);
            } else {
                $params['service_ids'] = $serviceIds;
            }
        }

        return self::request('book_times', $params);
    }

    /**
     * Submit booking to Altegio API
     * 
     * @param array $data Booking data
     * @return array API response
     */
    public static function makeBooking(array $data)
    {
        error_log('Making booking with data: ' . json_encode($data));

        // Валідація обов’язкових полів
        $required = ['phone', 'fullname', 'appointments'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'error' => "Missing required field: {$field}"
                ];
            }
        }

        // Формування datetime якщо передано окремо date + time
        if (isset($data['date'], $data['time']) && empty($data['datetime'])) {
            $data['datetime'] = $data['date'] . 'T' . $data['time'] . ':00';
            unset($data['date'], $data['time']);
        }

        // Видалити зайві поля
        unset($data['company_id'], $data['action'], $data['nonce']);

        // Визначити endpoint
        $endpoint = 'book_record/' . self::COMPANY_ID;

        // Відправити запит
        return self::request($endpoint, [], 'POST', $data);
    }





    public static function getBookingForm(int $formId = null): array
    {
        if ($formId === null) {
            $formId = self::COMPANY_ID;
        }

        return self::request('bookform/' . $formId);
    }

    public static function getI18n(string $langCode = 'en-US'): array
    {
        return self::request('i18n', ['lang' => $langCode]);
    }
    public static function getBookingDates(int $companyId, array $params = []): array
    {

        $endpoint = 'book_dates/' . $companyId;


        if (isset($params['service_ids']) && is_array($params['service_ids'])) {
            $serviceIds = $params['service_ids'];
            unset($params['service_ids']);
            foreach ($serviceIds as $i => $id) {
                $params['service_ids[' . $i . ']'] = $id;
            }
        }

        return self::request($endpoint, $params);
    }
    public static function getBookTimes(string $staffId, string $date, array $serviceIds = []): array
    {
        $endpoint = 'book_times/' . self::COMPANY_ID . '/' . $staffId . '/' . $date;
        $query = [];

        if (!empty($serviceIds)) {
            foreach ($serviceIds as $i => $serviceId) {
                $query['service_ids[' . $i . ']'] = $serviceId;
            }
        }

        return self::request($endpoint, $query);
    }
    public static function getServiceCategories(): array
    {
        return self::request('company/' . self::COMPANY_ID . '/service_categories');
    }
}
