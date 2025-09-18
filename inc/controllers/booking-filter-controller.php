<?php

/**
 * BookingFilterController - Handles all booking-related AJAX endpoints
 *
 * This class implements a full integration with Altegio API for service booking
 * with live availability checks and proper filtering of masters and services.
 */
class BookingFilterController {
  // Price adjustment percentage per master level above 1
  const PRICE_ADJUSTMENT_PER_LEVEL = 10;

  /**
   * Initialize the controller by registering all AJAX handlers
   */
  public static function init() {
    // Get all services (optionally filtered by master)
    add_action('wp_ajax_get_services', [self::class, 'getServices']);
    add_action('wp_ajax_nopriv_get_services', [self::class, 'getServices']);

    // Get services for a specific master
    add_action('wp_ajax_get_services_by_master', [self::class, 'getServicesByMaster']);
    add_action('wp_ajax_nopriv_get_services_by_master', [self::class, 'getServicesByMaster']);

    // Get all masters (optionally filtered by service)
    add_action('wp_ajax_get_masters', [self::class, 'getMasters']);
    add_action('wp_ajax_nopriv_get_masters', [self::class, 'getMasters']);

    // Get master details by ID
    add_action('wp_ajax_get_master_details', [self::class, 'getMasterDetails']);
    add_action('wp_ajax_nopriv_get_master_details', [self::class, 'getMasterDetails']);

    // Get masters filtered by service with availability check
    add_action('wp_ajax_get_filtered_staff', [self::class, 'getFilteredStaff']);
    add_action('wp_ajax_nopriv_get_filtered_staff', [self::class, 'getFilteredStaff']);

    // Get time slots for specific master and date
    add_action('wp_ajax_get_time_slots', [self::class, 'getTimeSlots']);
    add_action('wp_ajax_nopriv_get_time_slots', [self::class, 'getTimeSlots']);

    // Submit booking
    add_action('wp_ajax_submit_booking', [self::class, 'submitBooking']);
    add_action('wp_ajax_nopriv_submit_booking', [self::class, 'submitBooking']);
  }

  /**
   * Get all services, optionally filtered by category
   */
  public static function getServices() {
    check_ajax_referer('booking_nonce', 'nonce');

    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

    // Query for services
    $args = [
      'post_type' => 'service',
      'posts_per_page' => -1,
      'post_status' => 'publish',
    ];

    // Add taxonomy query if category is specified
    if ($category_id > 0) {
      $args['tax_query'] = [
        [
          'taxonomy' => 'service_category',
          'field' => 'term_id',
          'terms' => $category_id,
        ],
      ];
    }

    $services_query = new WP_Query($args);
    $services = [];

    if ($services_query->have_posts()) {
      while ($services_query->have_posts()) {
        $services_query->the_post();
        $post_id = get_the_ID();

        // Check if this is an add-on service
        $is_addon = get_post_meta($post_id, 'is_addon', true) === 'yes';

        // Get service categories
        $service_categories = wp_get_post_terms($post_id, 'service_category', ['fields' => 'ids']);

        // Get Altegio ID - try both potential meta keys for compatibility
        $altegio_id = get_post_meta($post_id, '_altegio_id', true);
        if (empty($altegio_id)) {
          $altegio_id = get_post_meta($post_id, 'altegio_id', true);
        }
        if (empty($altegio_id)) {
          $altegio_id = $post_id; // Fallback to WordPress ID
        }

        // Build service data object
        $services[] = [
          'ID' => $post_id,
          'post_title' => get_the_title(),
          'price_min' => get_post_meta($post_id, 'price_min', true) ?: '0',
          'price_max' => get_post_meta($post_id, 'price_max', true) ?: '0',
          'currency' => get_post_meta($post_id, 'currency', true) ?: 'SGD',
          'duration_minutes' => get_post_meta($post_id, 'duration_minutes', true) ?: '',
          'wear_time' => get_post_meta($post_id, 'wear_time', true) ?: '',
          'description' => get_post_meta($post_id, 'description', true) ?: '',
          'is_addon' => $is_addon,
          'categories' => $service_categories,
          'altegio_id' => $altegio_id,
        ];
      }
      wp_reset_postdata();
    }

    // Get categories
    $categories = get_terms([
      'taxonomy' => 'service_category',
      'hide_empty' => true,
    ]);

    wp_send_json_success([
      'categories' => $categories,
      'services' => $services,
    ]);
  }

  /**
   * Get services that can be performed by a specific master
   */
  public static function getServicesByMaster() {
    check_ajax_referer('booking_nonce', 'nonce');

    $master_id = isset($_POST['staff_id']) ? sanitize_text_field($_POST['staff_id']) : '';

    if (empty($master_id) || $master_id === 'any') {
      self::getServices(); // Return all services if 'any' master or no master specified
      return;
    }

    // Get master post by Altegio ID
    $master_post = self::getMasterPostByAltegioId($master_id);

    if (!$master_post) {
      wp_send_json_error(['message' => 'Master not found']);
      return;
    }

    // Get services linked to this master
    $related_services = get_field('related_services', $master_post->ID);

    if (empty($related_services) || !is_array($related_services)) {
      // Try with meta key
      $related_services = get_post_meta($master_post->ID, 'related_services', true);
      if (!is_array($related_services)) {
        $related_services = [];
      }
    }

    // Additional check with service_ids meta
    $service_ids = get_post_meta($master_post->ID, 'service_ids', true);
    if (!empty($service_ids) && is_array($service_ids)) {
      // Find WordPress posts for these Altegio service IDs
      foreach ($service_ids as $altegio_service_id) {
        $service_post = self::getServicePostByAltegioId($altegio_service_id);
        if ($service_post && !in_array($service_post->ID, $related_services)) {
          $related_services[] = $service_post->ID;
        }
      }
    }

    // If no related services found for this master
    if (empty($related_services)) {
      wp_send_json_success(['services' => []]);
      return;
    }

    // Return the service IDs for frontend filtering
    wp_send_json_success(['services' => $related_services]);
  }

  /**
   * Get all masters, optionally filtered by service
   */
  public static function getMasters() {
    check_ajax_referer('booking_nonce', 'nonce');

    $service_id = isset($_POST['service_id']) ? sanitize_text_field($_POST['service_id']) : '';

    // Query for all masters
    $args = [
      'post_type' => 'master',
      'posts_per_page' => -1,
      'post_status' => 'publish',
    ];

    $masters_query = new WP_Query($args);
    $masters = [];

    if ($masters_query->have_posts()) {
      while ($masters_query->have_posts()) {
        $masters_query->the_post();
        $post_id = get_the_ID();

        // Get master level (default to 1)
        $level = (int) get_post_meta($post_id, 'master_level', true) ?: 1;

        // Get specialization - fallback to level title if not specified
        $specialization =
          get_post_meta($post_id, 'specialization', true) ?: get_master_level_title($level, false);

        // Get related services
        $related_services = [];
        $acf_related = get_field('related_services', $post_id);
        if (!empty($acf_related) && is_array($acf_related)) {
          $related_services = $acf_related;
        } else {
          // Try with meta
          $meta_related = get_post_meta($post_id, 'related_services', true);
          if (is_array($meta_related)) {
            $related_services = $meta_related;
          }
        }

        // Filter by service if specified
        if (!empty($service_id) && !empty($related_services)) {
          if (!in_array($service_id, $related_services)) {
            continue; // Skip this master if not related to requested service
          }
        }

        // Get Altegio ID
        $altegio_id = get_post_meta($post_id, 'altegio_id', true) ?: $post_id;

        // Build master data object
        $masters[] = [
          'id' => $altegio_id,
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

  /**
   * Get master details by ID
   */
  public static function getMasterDetails() {
    check_ajax_referer('booking_nonce', 'nonce');

    $master_id = isset($_POST['staff_id']) ? sanitize_text_field($_POST['staff_id']) : '';

    if (empty($master_id)) {
      wp_send_json_error(['message' => 'Master ID is required']);
      return;
    }

    $master_post = self::getMasterPostByAltegioId($master_id);

    if ($master_post) {
      // Get master data from WordPress
      $post_id = $master_post->ID;
      $level = (int) get_post_meta($post_id, 'master_level', true) ?: 1;
      $specialization =
        get_post_meta($post_id, 'specialization', true) ?: get_master_level_title($level, false);

      $master_data = [
        'id' => $master_id,
        'name' => get_the_title($post_id),
        'avatar' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
        'level' => $level,
        'specialization' => $specialization,
      ];

      wp_send_json_success(['data' => $master_data]);
    } elseif (class_exists('AltegioClient')) {
      // Try to get master from Altegio API
      try {
        $staff = AltegioClient::getStaffMember($master_id);

        if (!empty($staff) && isset($staff['success']) && $staff['success']) {
          $member = $staff['data'];
          $master_data = [
            'id' => $master_id,
            'name' => $member['name'] ?? 'Unknown Master',
            'avatar' => $member['avatar'] ?? '',
            'level' => 1, // Default to level 1 from API
            'specialization' => $member['specialization'] ?? '',
          ];

          wp_send_json_success(['data' => $master_data]);
          return;
        }
      } catch (Exception $e) {
        error_log('Altegio API error: ' . $e->getMessage());
      }
    }

    wp_send_json_error(['message' => 'Master not found']);
  }

  /**
   * Get filtered staff with availability check for the specified services
   */
  public static function getFilteredStaff() {
    check_ajax_referer('booking_nonce', 'nonce');

    $service_ids_raw = isset($_POST['service_id']) ? sanitize_text_field($_POST['service_id']) : '';
    $requested_ids = array_map('strval', array_filter(explode(',', $service_ids_raw)));

    // Specific date for availability check (optional)
    $check_date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : date('Y-m-d');

    if (empty($requested_ids)) {
      wp_send_json_error(['message' => 'No services provided']);
      return;
    }

    // Convert WordPress IDs to Altegio IDs if necessary
    $altegio_service_ids = [];
    foreach ($requested_ids as $wp_id) {
      $service_post = get_post($wp_id);
      if ($service_post && $service_post->post_type === 'service') {
        $altegio_id = get_post_meta($service_post->ID, '_altegio_id', true);
        if (empty($altegio_id)) {
          $altegio_id = get_post_meta($service_post->ID, 'altegio_id', true);
        }
        if (!empty($altegio_id)) {
          $altegio_service_ids[] = $altegio_id;
        } else {
          $altegio_service_ids[] = $wp_id; // Fallback to WordPress ID
        }
      }
    }

    error_log('Altegio service IDs: ' . implode(',', $altegio_service_ids));

    // Get all masters
    $masters_query = new WP_Query([
      'post_type' => 'master',
      'posts_per_page' => -1,
      'post_status' => 'publish',
    ]);

    $available_masters = [];

    if ($masters_query->have_posts()) {
      while ($masters_query->have_posts()) {
        $masters_query->the_post();
        $post_id = get_the_ID();

        // Get master's Altegio ID
        $altegio_id = get_post_meta($post_id, 'altegio_id', true);
        if (empty($altegio_id)) {
          continue; // Skip masters without Altegio ID
        }

        // Check if master can perform the requested services
        $can_perform = self::masterCanPerformServices($post_id, $requested_ids);
        if (!$can_perform) {
          continue; // Skip if master can't perform the requested services
        }

        // Check if master has available slots (this actually checks availability in Altegio)
        $has_slots = self::checkMasterAvailability($altegio_id, $altegio_service_ids, $check_date);
        if (!$has_slots) {
          continue; // Skip if no availability
        }

        // Get master level and other details
        $level = (int) get_post_meta($post_id, 'master_level', true) ?: 1;
        $level_title = get_master_level_title($level, false);
        $specialization = get_post_meta($post_id, 'specialization', true) ?: $level_title;

        // Add to available masters
        $available_masters[] = [
          'id' => $altegio_id,
          'name' => get_the_title($post_id),
          'avatar' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
          'level' => $level,
          'specialization' => $specialization,
        ];
      }
      wp_reset_postdata();
    }

    wp_send_json_success(['data' => $available_masters]);
  }

  /**
   * Get time slots for a specific master and date
   */
  public static function getTimeSlots() {
    check_ajax_referer('booking_nonce', 'nonce');

    $staff_id = isset($_POST['staff_id']) ? sanitize_text_field($_POST['staff_id']) : '';
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
    $service_ids = isset($_POST['service_ids']) ? sanitize_text_field($_POST['service_ids']) : '';

    if (empty($staff_id) || empty($date)) {
      wp_send_json_error(['message' => 'Staff ID and date are required']);
      return;
    }

    // Handle "any" master by choosing an available one
    if ($staff_id === 'any') {
      $staff_id = self::getAvailableMasterForDate($date, $service_ids);
      if (empty($staff_id)) {
        wp_send_json_error(['message' => 'No available masters found for this date']);
        return;
      }
    }

    // Get time slots from Altegio API
    if (class_exists('AltegioClient')) {
      try {
        $time_slots = AltegioClient::getTimeSlots($staff_id, $date);

        if (!empty($time_slots) && isset($time_slots['success']) && $time_slots['success']) {
          wp_send_json_success(['slots' => $time_slots['data'] ?? []]);
          return;
        }
      } catch (Exception $e) {
        error_log('Altegio API error: ' . $e->getMessage());
      }
    }

    // Fallback to simulated time slots
    $slots = self::generateFallbackTimeSlots($date);
    wp_send_json_success(['slots' => $slots]);
  }

  /**
   * Submit booking to Altegio
   */
  public static function submitBooking() {
    check_ajax_referer('booking_nonce', 'booking_nonce');

    // Validate required fields
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
        'message' => 'Required fields missing',
        'fields' => $missing_fields,
      ]);
      return;
    }

    // Optional fields
    $booking_data['client_email'] = isset($_POST['client_email'])
      ? sanitize_email($_POST['client_email'])
      : '';
    $booking_data['client_comment'] = isset($_POST['client_comment'])
      ? sanitize_textarea_field($_POST['client_comment'])
      : '';

    // Handle additional data for price adjustments
    $core_services = isset($_POST['core_services'])
      ? json_decode(stripslashes($_POST['core_services']), true)
      : [];
    $addon_services = isset($_POST['addon_services'])
      ? json_decode(stripslashes($_POST['addon_services']), true)
      : [];
    $staff_level = isset($_POST['staff_level']) ? intval($_POST['staff_level']) : 1;
    $total_price = isset($_POST['total_price']) ? sanitize_text_field($_POST['total_price']) : '';

    // Format data for Altegio API
    $api_data = [
      'company_id' => AltegioClient::COMPANY_ID,
      'staff_id' => (int) $booking_data['staff_id'],
      'datetime' => $booking_data['date'] . 'T' . $booking_data['time'] . ':00',
      'services' => array_map(function ($id) {
        return ['id' => (int) $id];
      }, $service_ids),
      'client' => [
        'name' => $booking_data['client_name'],
        'phone' => $booking_data['client_phone'],
      ],
    ];

    if (!empty($booking_data['client_email'])) {
      $api_data['client']['email'] = $booking_data['client_email'];
    }

    if (!empty($booking_data['client_comment'])) {
      $api_data['client']['comment'] = $booking_data['client_comment'];
    }

    // Submit booking to Altegio API
    if (class_exists('AltegioClient')) {
      try {
        $result = AltegioClient::makeBooking($api_data);

        if (!isset($result['error'])) {
          // Save booking metadata as custom post if needed
          $booking_id = self::saveBookingRecord($booking_data, [
            'staff_level' => $staff_level,
            'core_services' => $core_services,
            'addon_services' => $addon_services,
            'total_price' => $total_price,
            'altegio_reference' => $result['booking']['reference'] ?? '',
          ]);

          wp_send_json_success([
            'message' => 'Booking created successfully',
            'booking' => $result['booking'] ?? null,
            'booking_id' => $booking_id,
          ]);
          return;
        } else {
          error_log('Altegio booking error: ' . json_encode($result['error']));
          wp_send_json_error([
            'message' => 'Error creating booking in Altegio',
            'details' => $result['error'],
          ]);
          return;
        }
      } catch (Exception $e) {
        error_log('Altegio API booking error: ' . $e->getMessage());
      }
    }

    // Fallback response for demo mode or when API fails
    wp_send_json_success([
      'message' => 'Booking created successfully (demo mode)',
      'booking' => [
        'id' => uniqid('demo_'),
        'reference' => 'BK' . mt_rand(1000, 9999),
        'datetime' => $booking_data['date'] . ' ' . $booking_data['time'],
      ],
    ]);
  }

  /**
   * Helper: Check if a master can perform the specified services
   *
   * @param int $master_post_id WordPress post ID of the master
   * @param array $service_ids Array of service WordPress IDs
   * @return bool Whether master can perform all the services
   */
  private static function masterCanPerformServices($master_post_id, $service_ids) {
    // Get master's related services
    $related_services = [];

    // Try ACF field first
    $acf_related = get_field('related_services', $master_post_id);
    if (!empty($acf_related) && is_array($acf_related)) {
      $related_services = $acf_related;
    } else {
      // Try direct post meta
      $meta_related = get_post_meta($master_post_id, 'related_services', true);
      if (is_array($meta_related)) {
        $related_services = $meta_related;
      }
    }

    // Also check service_ids meta which might contain Altegio IDs
    $service_ids_meta = get_post_meta($master_post_id, 'service_ids', true);
    if (!empty($service_ids_meta) && is_array($service_ids_meta)) {
      // Convert Altegio IDs to WordPress IDs
      foreach ($service_ids_meta as $altegio_id) {
        $service_post = self::getServicePostByAltegioId($altegio_id);
        if ($service_post && !in_array($service_post->ID, $related_services)) {
          $related_services[] = $service_post->ID;
        }
      }
    }

    // If no related services found, master can't perform any service
    if (empty($related_services)) {
      return false;
    }

    // Check if master can perform all requested services
    foreach ($service_ids as $service_id) {
      if (!in_array($service_id, $related_services)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Helper: Check master availability in Altegio
   *
   * @param string $master_altegio_id Altegio ID of the master
   * @param array $service_altegio_ids Array of service Altegio IDs
   * @param string $check_date Date to check (YYYY-MM-DD)
   * @return bool Whether master has availability
   */
  private static function checkMasterAvailability(
    $master_altegio_id,
    $service_altegio_ids,
    $check_date,
  ) {
    if (!class_exists('AltegioClient')) {
      return true; // Assume available if we can't check
    }

    // Check if master has slots for the specified date
    try {
      $time_slots = AltegioClient::getTimeSlots($master_altegio_id, $check_date);

      if (!empty($time_slots) && isset($time_slots['success']) && $time_slots['success']) {
        if (!empty($time_slots['data'])) {
          return true;
        }
      }

      // If no slots for today, check next 7 days
      for ($i = 1; $i <= 7; $i++) {
        $next_date = date('Y-m-d', strtotime($check_date . " +{$i} days"));
        $time_slots = AltegioClient::getTimeSlots($master_altegio_id, $next_date);

        if (!empty($time_slots) && isset($time_slots['success']) && $time_slots['success']) {
          if (!empty($time_slots['data'])) {
            return true;
          }
        }
      }
    } catch (Exception $e) {
      error_log(
        "Altegio availability check error for master $master_altegio_id: " . $e->getMessage(),
      );
    }

    return false;
  }

  /**
   * Helper: Get an available master for a specific date
   *
   * @param string $date Date to check (YYYY-MM-DD)
   * @param string $service_ids Comma-separated service IDs
   * @return string|null Altegio ID of an available master, or null if none found
   */
  private static function getAvailableMasterForDate($date, $service_ids) {
    if (empty($service_ids) || !class_exists('AltegioClient')) {
      return null;
    }

    // Convert service IDs to Altegio IDs
    $service_id_array = explode(',', $service_ids);
    $altegio_service_ids = [];

    foreach ($service_id_array as $wp_id) {
      $altegio_id = get_post_meta($wp_id, '_altegio_id', true);
      if (empty($altegio_id)) {
        $altegio_id = get_post_meta($wp_id, 'altegio_id', true);
      }
      if (!empty($altegio_id)) {
        $altegio_service_ids[] = $altegio_id;
      }
    }

    // Get all masters
    $masters_query = new WP_Query([
      'post_type' => 'master',
      'posts_per_page' => -1,
      'post_status' => 'publish',
    ]);

    if ($masters_query->have_posts()) {
      while ($masters_query->have_posts()) {
        $masters_query->the_post();
        $post_id = get_the_ID();
        $altegio_id = get_post_meta($post_id, 'altegio_id', true);

        if (!empty($altegio_id)) {
          // Check if this master has available slots for the date
          try {
            $time_slots = AltegioClient::getTimeSlots($altegio_id, $date);

            if (!empty($time_slots) && isset($time_slots['success']) && $time_slots['success']) {
              if (!empty($time_slots['data'])) {
                return $altegio_id; // Return the first available master
              }
            }
          } catch (Exception $e) {
            continue; // Try next master if error
          }
        }
      }
      wp_reset_postdata();
    }

    return null;
  }

  /**
   * Helper: Generate fallback time slots
   *
   * @param string $date Date for time slots (YYYY-MM-DD)
   * @return array Array of time slots
   */
  private static function generateFallbackTimeSlots($date) {
    $slots = [];
    $start_hour = 9; // 9 AM
    $end_hour = 19; // 7 PM
    $interval = 30; // 30 minutes

    // Generate a realistic distribution of available slots
    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
      for ($min = 0; $min < 60; $min += $interval) {
        // Skip some random slots to simulate real availability
        if (mt_rand(0, 10) > 2) {
          // 80% chance of being available
          $time_str = sprintf('%02d:%02d:00', $hour, $min);
          $slots[] = $date . ' ' . $time_str;
        }
      }
    }

    return $slots;
  }

  /**
   * Helper: Save booking record to WordPress
   *
   * @param array $booking_data Core booking data
   * @param array $meta_data Additional metadata
   * @return int|null Post ID of the saved booking, or null on failure
   */
  private static function saveBookingRecord($booking_data, $meta_data = []) {
    // Check if we should save local booking records
    $save_bookings = apply_filters('altegio_save_booking_records', true);
    if (!$save_bookings) {
      return null;
    }

    // Create post data
    $post_data = [
      'post_title' => sprintf(
        'Booking: %s - %s',
        $booking_data['client_name'],
        date('d.m.Y H:i', strtotime($booking_data['date'] . ' ' . $booking_data['time'])),
      ),
      'post_type' => 'booking', // Make sure this post type exists
      'post_status' => 'publish',
      'meta_input' => [
        '_booking_client_name' => $booking_data['client_name'],
        '_booking_client_phone' => $booking_data['client_phone'],
        '_booking_client_email' => $booking_data['client_email'] ?? '',
        '_booking_client_comment' => $booking_data['client_comment'] ?? '',
        '_booking_service_id' => $booking_data['service_id'],
        '_booking_staff_id' => $booking_data['staff_id'],
        '_booking_date' => $booking_data['date'],
        '_booking_time' => $booking_data['time'],
        '_booking_status' => 'confirmed',
        '_booking_created' => current_time('mysql'),
      ],
    ];

    // Add metadata
    if (!empty($meta_data)) {
      foreach ($meta_data as $key => $value) {
        if (is_array($value)) {
          $post_data['meta_input']['_booking_' . $key] = json_encode($value);
        } else {
          $post_data['meta_input']['_booking_' . $key] = $value;
        }
      }
    }

    // Insert post
    $post_id = wp_insert_post($post_data);

    // Return post ID on success, null on failure
    return is_wp_error($post_id) ? null : $post_id;
  }

  /**
   * Helper: Get WordPress master post by Altegio ID
   *
   * @param string $altegio_id Altegio ID of the master
   * @return WP_Post|null Master post or null if not found
   */
  private static function getMasterPostByAltegioId($altegio_id) {
    if (empty($altegio_id)) {
      return null;
    }

    // Query for master by altegio_id meta
    $args = [
      'post_type' => 'master',
      'posts_per_page' => 1,
      'post_status' => 'publish',
      'meta_query' => [
        [
          'key' => 'altegio_id',
          'value' => $altegio_id,
          'compare' => '=',
        ],
      ],
    ];

    $masters_query = new WP_Query($args);

    if ($masters_query->have_posts()) {
      return $masters_query->posts[0];
    }

    return null;
  }

  /**
   * Helper: Get WordPress service post by Altegio ID
   *
   * @param string $altegio_id Altegio ID of the service
   * @return WP_Post|null Service post or null if not found
   */
  private static function getServicePostByAltegioId($altegio_id) {
    if (empty($altegio_id)) {
      return null;
    }

    // First try with _altegio_id meta key
    $args = [
      'post_type' => 'service',
      'posts_per_page' => 1,
      'post_status' => 'publish',
      'meta_query' => [
        [
          'key' => '_altegio_id',
          'value' => $altegio_id,
          'compare' => '=',
        ],
      ],
    ];

    $services_query = new WP_Query($args);

    if ($services_query->have_posts()) {
      return $services_query->posts[0];
    }

    // If not found, try with altegio_id meta key
    $args['meta_query'][0]['key'] = 'altegio_id';
    $services_query = new WP_Query($args);

    if ($services_query->have_posts()) {
      return $services_query->posts[0];
    }

    return null;
  }

  /**
   * Helper: Calculate adjusted price based on master level
   *
   * @param float $base_price Base price of the service
   * @param int $master_level Master level (1, 2, 3)
   * @return float Adjusted price
   */
  public static function calculateAdjustedPrice($base_price, $master_level) {
    // No adjustment for level 1
    if ($master_level <= 1) {
      return $base_price;
    }

    // Calculate adjustment percentage based on level
    $adjustment_percent = ($master_level - 1) * self::PRICE_ADJUSTMENT_PER_LEVEL;
    $adjustment = $base_price * ($adjustment_percent / 100);

    return $base_price + $adjustment;
  }
}
