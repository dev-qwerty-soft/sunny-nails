<?php

class AltegioClient
{
    private const BASE_URL = 'https://api.alteg.io/api/v1/';
    private const PARTNER_TOKEN = 'becwbyhjwdf2s37fcmze';
    private const USER_TOKEN = '24b2f3cc652a7c7574290d8426823404';
    private const COMPANY_ID = '1275515';

    private static function request(string $endpoint): array
    {
        $url = self::BASE_URL . ltrim($endpoint, '/');
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
}
