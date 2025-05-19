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
                'Accept'        => 'application/vnd.api.v2+json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . self::PARTNER_TOKEN . ', User ' . self::USER_TOKEN,
            ],
            'timeout' => 15,
            'method'  => $method,
        ];

        if ($method === 'POST' && !empty($body)) {
            $args['body'] = json_encode($body);
        }

        $response = ($method === 'GET')
            ? wp_remote_get($url, $args)
            : wp_remote_post($url, $args);

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

    public static function makeBooking(array $bookingData): array
    {
        if (!isset($bookingData['company_id'])) {
            $bookingData['company_id'] = self::COMPANY_ID;
        }

        return self::request('book', [], 'POST', $bookingData);
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
