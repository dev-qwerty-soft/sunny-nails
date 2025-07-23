<?php
class AltegioClient {
  public const BASE_URL = 'https://api.alteg.io/api/v1/';
  public const PARTNER_TOKEN = 'becwbyhjwdf2s37fcmze';
  public const USER_TOKEN = '24b2f3cc652a7c7574290d8426823404';
  public const USER_ID = '24b2f3cc652a7c7574290d8426823404';
  public const COMPANY_ID = '1275515';

  private static function request(
    string $endpoint,
    array $params = [],
    string $method = 'GET',
    array $body = [],
  ): array {
    $url = self::BASE_URL . ltrim($endpoint, '/');

    // Add required authorization parameters
    $defaultParams = [
      'company_id' => self::COMPANY_ID,
      'user_id' => self::USER_ID,
    ];

    $params = array_merge($defaultParams, $params);

    if (!empty($params) && $method === 'GET') {
      $url .= '?' . http_build_query($params);
    }

    $args = [
      'headers' => [
        'Accept' => 'application/vnd.api.v2+json',
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . self::PARTNER_TOKEN . ', User ' . self::USER_TOKEN,
      ],
      'timeout' => 30,
      'method' => $method,
    ];

    if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($body)) {
      $args['body'] = json_encode($body);
    }

    error_log("Altegio API Request: $method $url");
    if (!empty($body)) {
      error_log('Request body: ' . json_encode($body));
    }

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
      return [
        'success' => false,
        'error' => $response->get_error_message(),
      ];
    }

    $code = wp_remote_retrieve_response_code($response);
    $responseBody = wp_remote_retrieve_body($response);
    $data = json_decode($responseBody, true);

    error_log("Altegio API Response (HTTP $code): " . $responseBody);

    // Check response structure
    if ($data && isset($data['success']) && $data['success'] === false) {
      return [
        'success' => false,
        'error' => $data['meta']['message'] ?? 'Unknown API error',
        'data' => null,
      ];
    }

    // For successful requests
    return [
      'success' =>
        in_array($code, [200, 201, 204]) &&
        (!isset($data['success']) || $data['success'] !== false),
      'data' => $data['data'] ?? ($data ?? null),
      'body' => $responseBody,
      'status' => $code,
    ];
  }

  // ========== READ METHODS ==========

  public static function getServices(): array {
    $response = self::request('company/' . self::COMPANY_ID . '/services');

    if ($response['success'] && isset($response['data'])) {
      return [
        'success' => 1,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => 0,
      'data' => [],
      'error' => $response['error'] ?? 'Failed to fetch services',
    ];
  }

  public static function getService($service_id): array {
    $response = self::request('company/' . self::COMPANY_ID . '/services/' . $service_id);

    if ($response['success'] && isset($response['data'])) {
      return [
        'success' => 1,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => 0,
      'data' => null,
      'error' => $response['error'] ?? 'Failed to fetch service',
    ];
  }

  public static function getStaff($serviceId = null): array {
    $params = [];
    if ($serviceId) {
      $params['service_id'] = $serviceId;
    }

    $response = self::request('company/' . self::COMPANY_ID . '/staff', $params);

    if ($response['success'] && isset($response['data'])) {
      return [
        'success' => 1,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => 0,
      'data' => [],
      'error' => $response['error'] ?? 'Failed to fetch staff',
    ];
  }

  public static function getStaffMember($staff_id): array {
    $response = self::request('company/' . self::COMPANY_ID . '/staff/' . $staff_id);

    if ($response['success'] && isset($response['data'])) {
      return [
        'success' => 1,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => 0,
      'data' => null,
      'error' => $response['error'] ?? 'Failed to fetch staff member',
    ];
  }

  public static function getServiceCategories(): array {
    $response = self::request('company/' . self::COMPANY_ID . '/service_categories');

    if ($response['success'] && isset($response['data'])) {
      return [
        'success' => 1,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => 0,
      'data' => [],
      'error' => $response['error'] ?? 'Failed to fetch categories',
    ];
  }

  public static function getServiceCategory($category_id): array {
    $response = self::request(
      'company/' . self::COMPANY_ID . '/service_categories/' . $category_id,
    );

    if ($response['success'] && isset($response['data'])) {
      return [
        'success' => 1,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => 0,
      'data' => null,
      'error' => $response['error'] ?? 'Failed to fetch category',
    ];
  }

  // ========== CREATE METHODS ==========

  public static function createService(array $data): array {
    // Validate required fields
    if (empty($data['title'])) {
      return [
        'success' => false,
        'error' => 'Service title is required',
      ];
    }

    // Prepare service data for Altegio API
    $serviceData = [
      'title' => $data['title'],
      'comment' => $data['comment'] ?? '',
      'price_min' => floatval($data['price_min'] ?? 0),
      'price_max' => floatval($data['price_max'] ?? 0),
      'duration' => intval($data['duration'] ?? 0),
      'category_id' => intval($data['category_id'] ?? 0),
      'is_online' => (bool) ($data['is_online'] ?? false),
      'booking_title' => $data['booking_title'] ?? $data['title'],
    ];

    $response = self::request(
      'company/' . self::COMPANY_ID . '/services',
      [],
      'POST',
      $serviceData,
    );

    if ($response['success']) {
      return [
        'success' => true,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => false,
      'error' => $response['error'] ?? 'Failed to create service',
    ];
  }

  public static function createStaff(array $data): array {
    // Validate required fields
    if (empty($data['name'])) {
      return [
        'success' => false,
        'error' => 'Staff name is required',
      ];
    }

    // Prepare staff data for Altegio API
    $staffData = [
      'name' => $data['name'],
      'information' => $data['information'] ?? '',
      'specialization' => $data['specialization'] ?? '1',
      'position_id' => intval($data['position_id'] ?? 1),
      'is_bookable' => (bool) ($data['is_bookable'] ?? true),
      'avatar_big' => $data['avatar_big'] ?? '',
    ];

    // Add employee data if available
    if (isset($data['employee'])) {
      $staffData['employee'] = $data['employee'];
    }

    $response = self::request('company/' . self::COMPANY_ID . '/staff', [], 'POST', $staffData);

    if ($response['success']) {
      return [
        'success' => true,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => false,
      'error' => $response['error'] ?? 'Failed to create staff member',
    ];
  }

  public static function createServiceCategory(array $data): array {
    // Validate required fields
    if (empty($data['title'])) {
      return [
        'success' => false,
        'error' => 'Category title is required',
      ];
    }

    $categoryData = [
      'title' => $data['title'],
      'description' => $data['description'] ?? '',
      'parent_id' => intval($data['parent_id'] ?? 0),
      'weight' => intval($data['weight'] ?? 0),
    ];

    $response = self::request(
      'company/' . self::COMPANY_ID . '/service_categories',
      [],
      'POST',
      $categoryData,
    );

    if ($response['success']) {
      return [
        'success' => true,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => false,
      'error' => $response['error'] ?? 'Failed to create category',
    ];
  }

  // ========== UPDATE METHODS ==========

  public static function updateService($service_id, array $data): array {
    // Prepare service data for update
    $serviceData = [];

    if (isset($data['title'])) {
      $serviceData['title'] = $data['title'];
    }
    if (isset($data['comment'])) {
      $serviceData['comment'] = $data['comment'];
    }
    if (isset($data['price_min'])) {
      $serviceData['price_min'] = floatval($data['price_min']);
    }
    if (isset($data['price_max'])) {
      $serviceData['price_max'] = floatval($data['price_max']);
    }
    if (isset($data['duration'])) {
      $serviceData['duration'] = intval($data['duration']);
    }
    if (isset($data['category_id'])) {
      $serviceData['category_id'] = intval($data['category_id']);
    }
    if (isset($data['is_online'])) {
      $serviceData['is_online'] = (bool) $data['is_online'];
    }
    if (isset($data['booking_title'])) {
      $serviceData['booking_title'] = $data['booking_title'];
    }

    $response = self::request(
      'company/' . self::COMPANY_ID . '/services/' . $service_id,
      [],
      'PUT',
      $serviceData,
    );

    if ($response['success']) {
      return [
        'success' => true,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => false,
      'error' => $response['error'] ?? 'Failed to update service',
    ];
  }

  public static function updateStaff($staff_id, array $data): array {
    // Prepare staff data for update
    $staffData = [];

    if (isset($data['name'])) {
      $staffData['name'] = $data['name'];
    }
    if (isset($data['information'])) {
      $staffData['information'] = $data['information'];
    }
    if (isset($data['specialization'])) {
      $staffData['specialization'] = $data['specialization'];
    }
    if (isset($data['position_id'])) {
      $staffData['position_id'] = intval($data['position_id']);
    }
    if (isset($data['is_bookable'])) {
      $staffData['is_bookable'] = (bool) $data['is_bookable'];
    }
    if (isset($data['avatar_big'])) {
      $staffData['avatar_big'] = $data['avatar_big'];
    }
    if (isset($data['employee'])) {
      $staffData['employee'] = $data['employee'];
    }

    $response = self::request(
      'company/' . self::COMPANY_ID . '/staff/' . $staff_id,
      [],
      'PUT',
      $staffData,
    );

    if ($response['success']) {
      return [
        'success' => true,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => false,
      'error' => $response['error'] ?? 'Failed to update staff member',
    ];
  }

  public static function updateServiceCategory($category_id, array $data): array {
    $categoryData = [];

    if (isset($data['title'])) {
      $categoryData['title'] = $data['title'];
    }
    if (isset($data['description'])) {
      $categoryData['description'] = $data['description'];
    }
    if (isset($data['parent_id'])) {
      $categoryData['parent_id'] = intval($data['parent_id']);
    }
    if (isset($data['weight'])) {
      $categoryData['weight'] = intval($data['weight']);
    }

    $response = self::request(
      'company/' . self::COMPANY_ID . '/service_categories/' . $category_id,
      [],
      'PUT',
      $categoryData,
    );

    if ($response['success']) {
      return [
        'success' => true,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => false,
      'error' => $response['error'] ?? 'Failed to update category',
    ];
  }

  // ========== DELETE METHODS ==========

  public static function deleteService($service_id): array {
    $response = self::request(
      'company/' . self::COMPANY_ID . '/services/' . $service_id,
      [],
      'DELETE',
    );

    if ($response['success'] || $response['status'] === 204) {
      return [
        'success' => true,
        'message' => 'Service deleted successfully',
      ];
    }

    return [
      'success' => false,
      'error' => $response['error'] ?? 'Failed to delete service',
    ];
  }

  public static function deleteStaff($staff_id): array {
    $response = self::request('company/' . self::COMPANY_ID . '/staff/' . $staff_id, [], 'DELETE');

    if ($response['success'] || $response['status'] === 204) {
      return [
        'success' => true,
        'message' => 'Staff member deleted successfully',
      ];
    }

    return [
      'success' => false,
      'error' => $response['error'] ?? 'Failed to delete staff member',
    ];
  }

  public static function deleteServiceCategory($category_id): array {
    $response = self::request(
      'company/' . self::COMPANY_ID . '/service_categories/' . $category_id,
      [],
      'DELETE',
    );

    if ($response['success'] || $response['status'] === 204) {
      return [
        'success' => true,
        'message' => 'Category deleted successfully',
      ];
    }

    return [
      'success' => false,
      'error' => $response['error'] ?? 'Failed to delete category',
    ];
  }

  // ========== EXISTING BOOKING METHODS ==========

  // ========== EXISTING BOOKING METHODS ==========

  public static function getCategories(): array {
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

  public static function getTimeSlots(
    string $staffId,
    string $date,
    array $serviceIds = [],
  ): array {
    $params = [
      'staff_id' => $staffId,
      'date' => $date,
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

  public static function makeBooking(array $data) {
    error_log('Making booking with data: ' . json_encode($data));

    // Validate required fields
    $required = ['phone', 'fullname', 'appointments'];
    foreach ($required as $field) {
      if (empty($data[$field])) {
        return [
          'success' => false,
          'error' => "Missing required field: {$field}",
        ];
      }
    }

    // Format datetime if passed separately as date + time
    if (isset($data['date'], $data['time']) && empty($data['datetime'])) {
      $data['datetime'] = $data['date'] . 'T' . $data['time'] . ':00';
      unset($data['date'], $data['time']);
    }

    // Remove extra fields
    unset($data['company_id'], $data['action'], $data['nonce']);

    // Determine endpoint
    $endpoint = 'book_record/' . self::COMPANY_ID;

    // Send request
    return self::request($endpoint, [], 'POST', $data);
  }

  public static function getBookingForm(int $formId = null): array {
    if ($formId === null) {
      $formId = self::COMPANY_ID;
    }

    return self::request('bookform/' . $formId);
  }

  public static function getI18n(string $langCode = 'en-US'): array {
    return self::request('i18n', ['lang' => $langCode]);
  }

  public static function getBookingDates(int $companyId, array $params = []): array {
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

  public static function getBookTimes(
    string $staffId,
    string $date,
    array $serviceIds = [],
  ): array {
    $endpoint = 'book_times/' . self::COMPANY_ID . '/' . $staffId . '/' . $date;
    $query = [];

    if (!empty($serviceIds)) {
      foreach ($serviceIds as $i => $serviceId) {
        $query['service_ids[' . $i . ']'] = $serviceId;
      }
    }

    return self::request($endpoint, $query);
  }

  // ========== BOOKING MANAGEMENT METHODS ==========

  public static function getBookings($date_from = null, $date_to = null, $staff_id = null): array {
    $params = [];

    if ($date_from) {
      $params['date_from'] = $date_from;
    }
    if ($date_to) {
      $params['date_to'] = $date_to;
    }
    if ($staff_id) {
      $params['staff_id'] = $staff_id;
    }

    $response = self::request('company/' . self::COMPANY_ID . '/records', $params);

    if ($response['success'] && isset($response['data'])) {
      return [
        'success' => 1,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => 0,
      'data' => [],
      'error' => $response['error'] ?? 'Failed to fetch bookings',
    ];
  }

  public static function getBooking($record_id): array {
    $response = self::request('company/' . self::COMPANY_ID . '/records/' . $record_id);

    if ($response['success'] && isset($response['data'])) {
      return [
        'success' => 1,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => 0,
      'data' => null,
      'error' => $response['error'] ?? 'Failed to fetch booking',
    ];
  }

  public static function updateBooking($record_id, array $data): array {
    $response = self::request(
      'company/' . self::COMPANY_ID . '/records/' . $record_id,
      [],
      'PUT',
      $data,
    );

    if ($response['success']) {
      return [
        'success' => true,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => false,
      'error' => $response['error'] ?? 'Failed to update booking',
    ];
  }

  public static function deleteBooking($record_id): array {
    $response = self::request(
      'company/' . self::COMPANY_ID . '/records/' . $record_id,
      [],
      'DELETE',
    );

    if ($response['success'] || $response['status'] === 204) {
      return [
        'success' => true,
        'message' => 'Booking deleted successfully',
      ];
    }

    return [
      'success' => false,
      'error' => $response['error'] ?? 'Failed to delete booking',
    ];
  }

  // ========== UTILITY METHODS ==========

  public static function testConnection(): array {
    try {
      $response = self::getServices();

      if (isset($response['success']) && $response['success']) {
        return [
          'success' => true,
          'message' => 'Connection successful',
          'company_id' => self::COMPANY_ID,
        ];
      } else {
        return [
          'success' => false,
          'error' => 'API returned error: ' . ($response['error'] ?? 'Unknown error'),
        ];
      }
    } catch (Exception $e) {
      return [
        'success' => false,
        'error' => 'Connection failed: ' . $e->getMessage(),
      ];
    }
  }

  public static function getCompanyInfo(): array {
    $response = self::request('company/' . self::COMPANY_ID);

    if ($response['success'] && isset($response['data'])) {
      return [
        'success' => 1,
        'data' => $response['data'],
      ];
    }

    return [
      'success' => 0,
      'data' => null,
      'error' => $response['error'] ?? 'Failed to fetch company info',
    ];
  }

  // ========== BATCH OPERATIONS ==========

  public static function batchCreateServices(array $services): array {
    $results = [];
    $success_count = 0;
    $error_count = 0;

    foreach ($services as $service_data) {
      $result = self::createService($service_data);
      $results[] = $result;

      if ($result['success']) {
        $success_count++;
      } else {
        $error_count++;
      }
    }

    return [
      'success' => $error_count === 0,
      'results' => $results,
      'summary' => [
        'total' => count($services),
        'success' => $success_count,
        'errors' => $error_count,
      ],
    ];
  }

  public static function batchUpdateServices(array $services): array {
    $results = [];
    $success_count = 0;
    $error_count = 0;

    foreach ($services as $service_id => $service_data) {
      $result = self::updateService($service_id, $service_data);
      $results[$service_id] = $result;

      if ($result['success']) {
        $success_count++;
      } else {
        $error_count++;
      }
    }

    return [
      'success' => $error_count === 0,
      'results' => $results,
      'summary' => [
        'total' => count($services),
        'success' => $success_count,
        'errors' => $error_count,
      ],
    ];
  }

  public static function batchCreateStaff(array $staff_members): array {
    $results = [];
    $success_count = 0;
    $error_count = 0;

    foreach ($staff_members as $staff_data) {
      $result = self::createStaff($staff_data);
      $results[] = $result;

      if ($result['success']) {
        $success_count++;
      } else {
        $error_count++;
      }
    }

    return [
      'success' => $error_count === 0,
      'results' => $results,
      'summary' => [
        'total' => count($staff_members),
        'success' => $success_count,
        'errors' => $error_count,
      ],
    ];
  }

  public static function batchUpdateStaff(array $staff_members): array {
    $results = [];
    $success_count = 0;
    $error_count = 0;

    foreach ($staff_members as $staff_id => $staff_data) {
      $result = self::updateStaff($staff_id, $staff_data);
      $results[$staff_id] = $result;

      if ($result['success']) {
        $success_count++;
      } else {
        $error_count++;
      }
    }

    return [
      'success' => $error_count === 0,
      'results' => $results,
      'summary' => [
        'total' => count($staff_members),
        'success' => $success_count,
        'errors' => $error_count,
      ],
    ];
  }

  public static function getAvailableBookingDays($companyId, $staffId = null, $serviceIds = []) {
    $params = [];
    if ($staffId) {
      $params['staff_id'] = $staffId;
    }
    if (!empty($serviceIds)) {
      $params['service_ids'] = $serviceIds;
    }
    return self::request("book_services/{$companyId}", $params);
  }

  // Пример метода для AltegioClient
  public static function getBookDates($company_id, $staff_id, $service_ids, $date_from, $date_to) {
    $url = "https://api.alteg.io/api/v1/book_dates/{$company_id}";
    $params = [
      'staff_id' => $staff_id,
      'service_ids' => implode(',', $service_ids),
      'date_from' => $date_from,
      'date_to' => $date_to,
    ];
    $query = http_build_query($params);
    $response = wp_remote_get($url . '?' . $query, [
      'headers' => [
        'Authorization' => 'Bearer ' . self::getToken(),
        'Content-Type' => 'application/json',
      ],
    ]);
    if (is_wp_error($response)) {
      return ['success' => false, 'error' => $response->get_error_message()];
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return [
      'success' => isset($body['success']) ? $body['success'] : true,
      'data' => $body,
    ];
  }
}
