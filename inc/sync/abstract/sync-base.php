<?php

/**
 * Base abstract class for all Altegio synchronizers
 *
 * @package AltegioSync
 */

if (!defined('ABSPATH')) {
  exit(); // Exit if accessed directly
}

/**
 * Logger utility for Altegio operations
 */
class AltegioLogger {
  /**
   * Log levels
   */
  const ERROR = 'error';
  const WARNING = 'warning';
  const INFO = 'info';
  const DEBUG = 'debug';

  /**
   * Add message to log
   *
   * @param string $message Message to log
   * @param string $level Log level
   * @param array $context Additional context
   */
  public function log($message, $level = self::INFO, $context = []) {
    if ($level === self::ERROR) {
      error_log(
        'ALTEGIO ERROR: ' . $message . (!empty($context) ? ' | ' . wp_json_encode($context) : ''),
      );
    } elseif ($level === self::WARNING) {
      error_log(
        'ALTEGIO WARNING: ' . $message . (!empty($context) ? ' | ' . wp_json_encode($context) : ''),
      );
    } elseif ($level === self::INFO) {
      error_log(
        'ALTEGIO INFO: ' . $message . (!empty($context) ? ' | ' . wp_json_encode($context) : ''),
      );
    } elseif ($level === self::DEBUG && WP_DEBUG) {
      error_log(
        'ALTEGIO DEBUG: ' . $message . (!empty($context) ? ' | ' . wp_json_encode($context) : ''),
      );
    }
  }
}

/**
 * Field mapper utility for Altegio data
 */
class AltegioFieldsMapper {
  /**
   * Get field mapping for services (Altegio API -> ACF)
   */
  public static function getServiceFieldsMap() {
    return [
      // API key => ACF field name
      'id' => 'altegio_id',
      'base_price' => 'base_price',
      'price_min' => 'price_min',
      'price_max' => 'price_max',
      'comment' => 'description',
      'duration' => 'duration',
      'discount' => 'discount',
      'is_online' => 'is_online',
      'weight' => 'weight',
      'service_type' => 'service_type',
      'booking_title' => 'booking_title',
      'print_title' => 'print_title',
      'original_title' => 'original_title',
      'category_id' => 'local_category',
      'title' => 'post_title',
    ];
  }

  /**
   * Get field mapping for masters (Altegio API -> ACF)
   */
  public static function getMasterFieldsMap() {
    return [
      // API key => ACF field name
      'id' => 'altegio_id',
      'position' => 'master_level',
      'information' => 'description',
      'instagram_url' => 'instagram_url',
      'specialization' => 'specialization',
      'services_links' => 'service_ids',
      'avatar_big' => 'avatar_url',
      'name' => 'post_title',
    ];
  }

  /**
   * Get field mapping for categories (Altegio API -> ACF)
   */
  public static function getCategoryFieldsMap() {
    return [
      // API key => ACF field name
      'category_id' => 'altegio_category_id',
      'parent_id' => 'parent_category_id',
      'title' => 'name',
      'description' => 'description',
      'weight' => 'weight',
    ];
  }

  /**
   * Convert API data to ACF format
   *
   * @param array $api_data API data
   * @param array $field_map Field mapping
   * @return array Prepared data for ACF
   */
  public static function mapApiDataToAcf($api_data, $field_map) {
    $acf_data = [];

    foreach ($field_map as $api_key => $acf_key) {
      if (isset($api_data[$api_key])) {
        $acf_data[$acf_key] = $api_data[$api_key];
      }
    }

    return $acf_data;
  }

  /**
   * Prepare service data for storage
   *
   * @param array $service_data Service data from API
   * @return array Data for WP storage
   */
  public static function prepareServiceData($service_data) {
    $field_map = self::getServiceFieldsMap();
    $acf_data = self::mapApiDataToAcf($service_data, $field_map);

    // Strip wear_time from comment
    $cleaned_comment = $service_data['comment'] ?? '';
    $wear_time = null;

    if (
      !empty($cleaned_comment) &&
      preg_match('/Wear time:?\s*(.+)/i', $cleaned_comment, $matches)
    ) {
      $wear_time = trim($matches[1]);
      $acf_data['wear_time'] = $wear_time;

      // Remove wear time line from comment
      $cleaned_comment = preg_replace('/Wear time:?\s*.+/i', '', $cleaned_comment);
    }

    $post_data = [
      'post_title' => sanitize_text_field($service_data['title'] ?? ''),
      'post_content' => wp_kses_post(trim($cleaned_comment)),
      'post_status' => 'publish',
      'post_type' => 'service',
    ];

    if (isset($service_data['duration'])) {
      $acf_data['duration_minutes'] = round($service_data['duration'] / 60);
    }

    return [
      'post_data' => $post_data,
      'acf_data' => $acf_data,
    ];
  }

  /**
   * Prepare master data for storage
   *
   * @param array $master_data Master data from API
   * @return array Data for WP storage
   */
  public static function prepareMasterData($master_data) {
    $field_map = self::getMasterFieldsMap();
    $acf_data = self::mapApiDataToAcf($master_data, $field_map);

    // Create post data
    $post_data = [
      'post_title' => sanitize_text_field($master_data['name'] ?? ''),
      'post_content' => sanitize_textarea_field($master_data['information'] ?? ''),
      'post_status' => 'publish',
      'post_type' => 'master',
    ];

    // Process service links
    if (isset($master_data['services_links']) && is_array($master_data['services_links'])) {
      $service_ids = array_map(function ($link) {
        return isset($link['service_id']) ? $link['service_id'] : null;
      }, $master_data['services_links']);

      $acf_data['service_ids'] = array_filter($service_ids);
    }

    return [
      'post_data' => $post_data,
      'acf_data' => $acf_data,
    ];
  }

  /**
   * Prepare category data for storage
   *
   * @param array $category_data Category data from API
   * @return array Data for WP storage
   */
  public static function prepareCategoryData($category_data) {
    $field_map = self::getCategoryFieldsMap();
    $acf_data = self::mapApiDataToAcf($category_data, $field_map);

    // Create term data
    $term_data = [
      'name' => sanitize_text_field($category_data['title'] ?? ''),
      'description' => sanitize_textarea_field($category_data['description'] ?? ''),
    ];

    return [
      'term_data' => $term_data,
      'acf_data' => $acf_data,
    ];
  }
}

/**
 * Base abstract class for all synchronizers
 */
abstract class AltegioSyncBase {
  /**
   * @var AltegioClient
   */
  protected $api_client;

  /**
   * @var AltegioLogger
   */
  protected $logger;

  /**
   * Synchronization statistics
   */
  protected $stats = [
    'created' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0,
  ];

  /**
   * Constructor
   *
   * @param AltegioClient $api_client API client
   * @param AltegioLogger $logger Logger (optional)
   */
  public function __construct($api_client = null, $logger = null) {
    if ($api_client === null) {
      if (!class_exists('AltegioClient')) {
        require_once dirname(dirname(__DIR__)) . '/api/altegio-client.php';
      }
      $this->api_client = new AltegioClient();
    } else {
      $this->api_client = $api_client;
    }

    $this->logger = $logger ?: new AltegioLogger();
  }

  /**
   * Start synchronization process
   *
   * @return array Synchronization results
   */
  abstract public function sync();

  /**
   * Fetch data from API
   *
   * @return array|false API data or false on error
   */
  abstract protected function fetchApiData();

  /**
   * Process one data item
   *
   * @param array $item_data Item data
   * @return bool Operation success
   */
  abstract protected function processItem($item_data);

  /**
   * Find existing post by Altegio ID
   *
   * @param int $altegio_id ID from Altegio API
   * @param string $meta_key Meta key name for search
   * @param string $post_type Post type (CPT)
   * @return int|false Post ID or false if not found
   */
  protected function findExistingPostByAltegioId($altegio_id, $meta_key, $post_type) {
    $args = [
      'post_type' => $post_type,
      'post_status' => 'publish',
      'posts_per_page' => 1,
      'meta_query' => [
        [
          'key' => $meta_key,
          'value' => $altegio_id,
          'compare' => '=',
        ],
      ],
    ];

    $query = new WP_Query($args);

    if ($query->have_posts()) {
      return $query->posts[0]->ID;
    }

    return false;
  }

  /**
   * Find existing term by Altegio ID
   *
   * @param int $altegio_id ID from Altegio API
   * @param string $meta_key Meta key name for search
   * @param string $taxonomy Taxonomy
   * @return int|false Term ID or false if not found
   */
  protected function findExistingTermByAltegioId($altegio_id, $meta_key, $taxonomy) {
    $terms = get_terms([
      'taxonomy' => $taxonomy,
      'hide_empty' => false,
      'meta_query' => [
        [
          'key' => $meta_key,
          'value' => $altegio_id,
        ],
      ],
    ]);

    if (!is_wp_error($terms) && !empty($terms)) {
      return $terms[0]->term_id;
    }

    return false;
  }

  /**
   * Get synchronization statistics
   *
   * @return array Statistics data
   */
  public function getStats() {
    return $this->stats;
  }
}
