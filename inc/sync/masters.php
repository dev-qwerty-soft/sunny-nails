<?php

/**
 * Masters synchronization class for Altegio API
 *
 * @package AltegioSync
 */

if (!defined('ABSPATH')) {
  exit(); // Exit if accessed directly
}

require_once dirname(__FILE__) . '/abstract/sync-base.php';

/**
 * Class for synchronizing masters from Altegio API
 */
class AltegioSyncMasters extends AltegioSyncBase {
  /**
   * @var string
   */
  protected $post_type = 'master';

  /**
   * @var string
   */
  protected $meta_key = 'altegio_id';

  /**
   * Fetch data from API
   *
   * @return array|false API data or false on error
   */
  protected function fetchApiData() {
    $staff = $this->api_client::getStaff();

    if (!isset($staff['success']) || !$staff['success'] || !isset($staff['data'])) {
      $this->logger->log('Failed to fetch staff from Altegio API', AltegioLogger::ERROR);
      return false;
    }

    return $staff['data'];
  }

  /**
   * Process one data item (master)
   *
   * @param array $item_data Item data
   * @return bool Operation success
   */
  protected function processItem($item_data) {
    // Prepare post data
    $post_data = [
      'post_title' => sanitize_text_field($item_data['name']),
      'post_type' => $this->post_type,
      'post_status' => 'publish',
      'post_content' => isset($item_data['information'])
        ? wp_kses_post($item_data['information'])
        : '',
    ];

    // Prepare ACF data with new fields
    $acf_data = [
      'altegio_id' => sanitize_text_field($item_data['id']),
      'description' => isset($item_data['information'])
        ? wp_kses_post($item_data['information'])
        : '',
      'master_level' => sanitize_text_field($item_data['specialization'] ?? '1'),
      'is_bookable' => !empty($item_data['is_bookable']) ? true : false,
      'schedule_until' => !empty($item_data['schedule_till'])
        ? sanitize_text_field($item_data['schedule_till'])
        : '',
      'instagram_url' => '',
    ];

    // Extract Instagram URL from patronymic field if it exists
    if (
      isset($item_data['employee']) &&
      !empty($item_data['employee']['patronymic']) &&
      strpos($item_data['employee']['patronymic'], 'instagram') !== false
    ) {
      $acf_data['instagram_url'] = esc_url_raw($item_data['employee']['patronymic']);
    }

    $altegio_id = $acf_data['altegio_id'] ?? '';

    if (empty($altegio_id) || empty($post_data['post_title'])) {
      $this->logger->log(
        'Missing required master data (id or name)',
        AltegioLogger::WARNING,
        $item_data,
      );
      $this->stats['skipped']++;
      return false;
    }

    // Check if post with this Altegio ID exists
    $existing_post_id = $this->findExistingPostByAltegioId(
      $altegio_id,
      $this->meta_key,
      $this->post_type,
    );

    if ($existing_post_id) {
      // Update existing post
      $post_data['ID'] = $existing_post_id;
      $result = wp_update_post($post_data, true);

      if (is_wp_error($result)) {
        $this->logger->log('Error updating master post', AltegioLogger::ERROR, [
          'error' => $result->get_error_message(),
          'post_id' => $existing_post_id,
        ]);
        $this->stats['errors']++;
        return false;
      }

      $post_id = $result;
      $this->stats['updated']++;
    } else {
      // Create new post
      $result = wp_insert_post($post_data, true);

      if (is_wp_error($result)) {
        $this->logger->log('Error creating master post', AltegioLogger::ERROR, [
          'error' => $result->get_error_message(),
          'title' => $post_data['post_title'],
        ]);
        $this->stats['errors']++;
        return false;
      }

      $post_id = $result;
      $this->stats['created']++;
    }

    // Save meta data
    foreach ($acf_data as $key => $value) {
      update_post_meta($post_id, $key, $value);

      // If ACF exists, also update through ACF
      if (function_exists('update_field')) {
        update_field($key, $value, $post_id);
      }
    }

    // Process avatar
    $this->processMasterAvatar($post_id, $item_data);

    // Process service relationships
    $this->processMasterServices($post_id, $item_data);

    return true;
  }

  /**
   * Process master avatar
   *
   * @param int $post_id Master post ID
   * @param array $item_data Master data
   */
  protected function processMasterAvatar($post_id, $item_data) {
    $photo = isset($item_data['avatar_big']) ? esc_url_raw($item_data['avatar_big']) : '';

    if (empty($photo)) {
      return;
    }

    // Check if we already have a featured image
    if (has_post_thumbnail($post_id)) {
      return;
    }

    // Download and attach avatar as featured image
    if (function_exists('media_sideload_image')) {
      $attachment_id = media_sideload_image($photo, $post_id, null, 'id');

      if (!is_wp_error($attachment_id)) {
        set_post_thumbnail($post_id, $attachment_id);
      } else {
        $this->logger->log('Error downloading master avatar', AltegioLogger::WARNING, [
          'error' => $attachment_id->get_error_message(),
          'post_id' => $post_id,
        ]);
      }
    }
  }

  /**
   * Process service relationships
   *
   * @param int $post_id Master post ID
   * @param array $item_data Master data
   */
  /**
   * Process service relationships
   *
   * @param int $post_id Master post ID
   * @param array $item_data Master data
   */
  protected function processMasterServices($post_id, $item_data) {
    if (isset($item_data['services_links']) && is_array($item_data['services_links'])) {
      $service_ids = array_map(function ($link) {
        return isset($link['service_id']) ? $link['service_id'] : null;
      }, $item_data['services_links']);

      $service_ids = array_filter($service_ids);

      // Store the raw Altegio service IDs for reference
      update_post_meta($post_id, 'service_ids', $service_ids);

      // Save as related_services field for the relationship field
      if (function_exists('update_field')) {
        // First, we need to find the corresponding WordPress post IDs for these Altegio service IDs
        $wp_service_ids = [];

        foreach ($service_ids as $altegio_service_id) {
          // Try with both meta key possibilities for backward compatibility
          $services = [];

          // First try with 'altegio_id'
          $args = [
            'post_type' => 'service',
            'meta_query' => [
              [
                'key' => 'altegio_id',
                'value' => $altegio_service_id,
                'compare' => '=',
              ],
            ],
            'posts_per_page' => 1,
            'fields' => 'ids',
          ];

          $services = get_posts($args);

          // If not found, try with '_altegio_id'
          if (empty($services)) {
            $args['meta_query'][0]['key'] = '_altegio_id';
            $services = get_posts($args);
          }

          if (!empty($services)) {
            $wp_service_ids[] = $services[0]; // Add the WP post ID to our array
            $this->logger->log('Found service post for Altegio ID', AltegioLogger::DEBUG, [
              'altegio_id' => $altegio_service_id,
              'wp_post_id' => $services[0],
            ]);
          } else {
            $this->logger->log(
              'Could not find service post for Altegio ID',
              AltegioLogger::WARNING,
              [
                'altegio_id' => $altegio_service_id,
              ],
            );
          }
        }

        // Log what we're updating
        $this->logger->log('Updating related_services field', AltegioLogger::DEBUG, [
          'master_post_id' => $post_id,
          'service_wp_ids' => $wp_service_ids,
          'service_count' => count($wp_service_ids),
        ]);

        // Update the ACF relationship field with the WP post IDs
        update_field('related_services', $wp_service_ids, $post_id);
      }
    }
  }

  /**
   * Start master synchronization process
   *
   * @return array Synchronization results
   */
  public function sync() {
    $this->logger->log('Starting master synchronization', AltegioLogger::INFO);

    // Reset statistics
    $this->stats = [
      'created' => 0,
      'updated' => 0,
      'skipped' => 0,
      'errors' => 0,
    ];

    // Get data from API
    $masters = $this->fetchApiData();

    if ($masters === false) {
      return $this->stats;
    }

    // Process each master
    foreach ($masters as $master) {
      $this->processItem($master);
    }

    $this->logger->log('Master synchronization completed', AltegioLogger::INFO, $this->stats);

    return $this->stats;
  }
}
