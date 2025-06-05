<?php

/**
 * Services synchronization class for Altegio API
 *
 * @package AltegioSync
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once dirname(__FILE__) . '/abstract/sync-base.php';

/**
 * Class for synchronizing services from Altegio API
 */
class AltegioSyncServices extends AltegioSyncBase
{
    /**
     * @var string
     */
    protected $post_type = 'service';

    /**
     * @var string
     */
    protected $meta_key = '_altegio_id';

    /**
     * @var string
     */
    protected $taxonomy = 'service_category';

    /**
     * @var string
     */
    protected $category_meta_key = '_altegio_category_id';

    /**
     * @var array
     */
    protected $category_mapping = [];

    /**
     * Fetch data from API
     * 
     * @return array|false API data or false on error
     */
    protected function fetchApiData()
    {
        $services = $this->api_client::getServices();

        if (!isset($services['success']) || $services['success'] != 1) {
            $this->logger->log('Failed to fetch services from Altegio API', AltegioLogger::ERROR);
            return false;
        }

        if (!isset($services['data']) || !is_array($services['data'])) {
            $this->logger->log('No data found in Altegio API response', AltegioLogger::ERROR);
            return false;
        }

        return $services['data'];
    }

    /**
     * Process one data item (service)
     * 
     * @param array $item_data Item data
     * @return bool Operation success
     */
    protected function processItem($item_data)
    {
        // Get field mapping for service
        $prepared_data = AltegioFieldsMapper::prepareServiceData($item_data);

        if (empty($prepared_data)) {
            $this->logger->log('Failed to prepare service data', AltegioLogger::WARNING, $item_data);
            $this->stats['errors']++;
            return false;
        }

        $post_data = $prepared_data['post_data'];
        $acf_data = $prepared_data['acf_data'];

        $altegio_id = $acf_data['altegio_id'] ?? '';

        if (empty($altegio_id) || empty($post_data['post_title'])) {
            $this->logger->log('Missing required service data (id or title)', AltegioLogger::WARNING, $item_data);
            $this->stats['skipped']++;
            return false;
        }

        // Check if post with this Altegio ID exists using the proper meta key
        $existing_post_id = $this->findExistingPostByAltegioId($altegio_id, $this->meta_key, $this->post_type);

        // If not found with the new meta key, try the old one for backward compatibility
        if (!$existing_post_id) {
            $existing_post_id = $this->findExistingPostByAltegioId($altegio_id, 'altegio_id', $this->post_type);
        }

        if ($existing_post_id) {
            // Update existing post
            $post_data['ID'] = $existing_post_id;
            $result = wp_update_post($post_data, true);

            if (is_wp_error($result)) {
                $this->logger->log('Error updating service post', AltegioLogger::ERROR, [
                    'error' => $result->get_error_message(),
                    'post_id' => $existing_post_id
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
                $this->logger->log('Error creating service post', AltegioLogger::ERROR, [
                    'error' => $result->get_error_message(),
                    'title' => $post_data['post_title']
                ]);
                $this->stats['errors']++;
                return false;
            }

            $post_id = $result;
            $this->stats['created']++;
        }

        // Always save with the correct meta key
        update_post_meta($post_id, $this->meta_key, $altegio_id);

        // Save for backward compatibility (so both keys work)
        update_post_meta($post_id, 'altegio_id', $altegio_id);

        // Save meta data
        foreach ($acf_data as $key => $value) {
            // Skip altegio_id as we've already saved it
            if ($key !== 'altegio_id') {
                update_post_meta($post_id, $key, $value);

                // If ACF exists, also update through ACF
                if (function_exists('update_field')) {
                    update_field($key, $value, $post_id);
                }
            }
        }

        // Process categories - IMPROVED VERSION
        $this->processServiceCategories($post_id, $item_data);

        // Process prices
        $this->processServicePrices($post_id, $item_data);

        // Process master relationships
        $this->processServiceMasters($post_id, $item_data);

        // Process duration
        if (isset($item_data['duration'])) {
            $duration = intval($item_data['duration']);
            $duration_minutes = round($duration / 60);

            update_post_meta($post_id, '_duration', $duration);
            update_post_meta($post_id, 'duration_minutes', $duration_minutes);

            if (function_exists('update_field')) {
                update_field('_duration', $duration, $post_id);
                update_field('duration_minutes', $duration_minutes, $post_id);
            }
        }

        return true;
    }

    /**
     * IMPROVED: Process service categories with better error handling
     * 
     * @param int $post_id Service post ID
     * @param array $item_data Service data
     */
    protected function processServiceCategories($post_id, $item_data)
    {
        // Extract category ID from API data
        $altegio_category_id = null;

        if (isset($item_data['category_id']) && !empty($item_data['category_id'])) {
            $altegio_category_id = intval($item_data['category_id']);
        }

        if (!$altegio_category_id) {
            $this->logger->log('No category ID found for service', AltegioLogger::DEBUG, [
                'service_id' => $post_id,
                'service_title' => get_the_title($post_id)
            ]);
            return;
        }

        // Save local category ID for reference
        update_post_meta($post_id, 'local_category', $altegio_category_id);

        // Find the WordPress term that corresponds to this Altegio category ID
        $category_terms = get_terms([
            'taxonomy' => $this->taxonomy,
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key' => $this->category_meta_key,
                    'value' => $altegio_category_id,
                    'compare' => '='
                ]
            ]
        ]);

        if (!empty($category_terms) && !is_wp_error($category_terms)) {
            // Found matching category - assign it to the service
            $term_id = $category_terms[0]->term_id;

            $result = wp_set_object_terms($post_id, $term_id, $this->taxonomy);

            if (!is_wp_error($result)) {
                $this->logger->log('Service category assigned successfully', AltegioLogger::DEBUG, [
                    'service_id' => $post_id,
                    'category_term_id' => $term_id,
                    'altegio_category_id' => $altegio_category_id
                ]);
            } else {
                $this->logger->log('Error assigning category to service', AltegioLogger::WARNING, [
                    'service_id' => $post_id,
                    'error' => $result->get_error_message()
                ]);
            }
        } else {
            // Category not found - log this for debugging
            $this->logger->log('Category term not found for Altegio category ID', AltegioLogger::WARNING, [
                'service_id' => $post_id,
                'altegio_category_id' => $altegio_category_id,
                'service_title' => get_the_title($post_id)
            ]);

            // Try to find by category title if available
            if (isset($item_data['category_title']) && !empty($item_data['category_title'])) {
                $category_title = sanitize_text_field($item_data['category_title']);

                $term_by_name = get_term_by('name', $category_title, $this->taxonomy);

                if ($term_by_name && !is_wp_error($term_by_name)) {
                    // Check if this term has an Altegio ID already
                    $existing_altegio_id = get_term_meta($term_by_name->term_id, $this->category_meta_key, true);

                    if (empty($existing_altegio_id)) {
                        // Link this term to the Altegio category ID
                        update_term_meta($term_by_name->term_id, $this->category_meta_key, $altegio_category_id);

                        $this->logger->log('Linked existing category term to Altegio ID', AltegioLogger::INFO, [
                            'term_id' => $term_by_name->term_id,
                            'category_title' => $category_title,
                            'altegio_category_id' => $altegio_category_id
                        ]);
                    }

                    // Assign the category to the service
                    wp_set_object_terms($post_id, $term_by_name->term_id, $this->taxonomy);
                } else {
                    // Create new category term if it doesn't exist
                    $new_term = wp_insert_term($category_title, $this->taxonomy);

                    if (!is_wp_error($new_term)) {
                        $new_term_id = $new_term['term_id'];

                        // Link to Altegio ID
                        update_term_meta($new_term_id, $this->category_meta_key, $altegio_category_id);

                        // Assign to service
                        wp_set_object_terms($post_id, $new_term_id, $this->taxonomy);

                        $this->logger->log('Created new category term and assigned to service', AltegioLogger::INFO, [
                            'new_term_id' => $new_term_id,
                            'category_title' => $category_title,
                            'altegio_category_id' => $altegio_category_id,
                            'service_id' => $post_id
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Process service prices
     * 
     * @param int $post_id Service post ID
     * @param array $item_data Service data
     */
    protected function processServicePrices($post_id, $item_data)
    {
        $price_min = isset($item_data['price_min']) ? floatval($item_data['price_min']) : 0;
        $price_max = isset($item_data['price_max']) ? floatval($item_data['price_max']) : 0;

        update_post_meta($post_id, 'price_min', $price_min);
        update_post_meta($post_id, 'price_max', $price_max);
        update_post_meta($post_id, 'base_price', $price_min);
        update_post_meta($post_id, 'currency', 'SGD'); // Add default currency

        // If ACF exists, also update through ACF
        if (function_exists('update_field')) {
            update_field('price_min', $price_min, $post_id);
            update_field('price_max', $price_max, $post_id);
            update_field('base_price', $price_min, $post_id);
            update_field('currency', 'SGD', $post_id);
        }
    }

    /**
     * Process master relationships
     * 
     * @param int $post_id Service post ID
     * @param array $item_data Service data
     */
    protected function processServiceMasters($post_id, $item_data)
    {
        if (isset($item_data['staff']) && is_array($item_data['staff'])) {
            $altegio_master_ids = array_map(function ($staff) {
                return isset($staff['id']) ? $staff['id'] : null;
            }, $item_data['staff']);

            $altegio_master_ids = array_filter($altegio_master_ids);

            $wp_master_ids = [];

            foreach ($altegio_master_ids as $altegio_id) {
                $masters = get_posts([
                    'post_type' => 'master',
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                    'meta_key' => 'altegio_id',
                    'meta_value' => $altegio_id,
                    'fields' => 'ids'
                ]);

                if (!empty($masters)) {
                    $wp_master_ids[] = $masters[0];
                }
            }

            // Save as ACF relationship
            if (function_exists('update_field')) {
                update_field('related_master', $wp_master_ids, $post_id);
            }

            // Save raw Altegio IDs just in case
            update_post_meta($post_id, 'master_ids', $altegio_master_ids);
        }
    }

    /**
     * Clean up old services that no longer exist in Altegio
     * 
     * @param array $current_altegio_ids Current service IDs from API
     */
    protected function cleanupOldServices($current_altegio_ids)
    {
        $existing_services = get_posts([
            'post_type' => $this->post_type,
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => $this->meta_key,
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => 'altegio_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);

        foreach ($existing_services as $service) {
            $altegio_id = get_post_meta($service->ID, $this->meta_key, true);
            if (empty($altegio_id)) {
                $altegio_id = get_post_meta($service->ID, 'altegio_id', true);
            }

            if ($altegio_id && !in_array($altegio_id, $current_altegio_ids)) {
                $result = wp_delete_post($service->ID, true);

                if ($result) {
                    $this->logger->log("Deleted obsolete service: ID {$service->ID}, Title: {$service->post_title}", AltegioLogger::INFO);
                } else {
                    $this->logger->log("Failed to delete service: ID {$service->ID}", AltegioLogger::ERROR);
                }
            }
        }
    }

    /**
     * Start service synchronization process
     * 
     * @return array Synchronization results
     */
    public function sync()
    {
        $this->logger->log('Starting service synchronization', AltegioLogger::INFO);

        // Reset statistics
        $this->stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors'  => 0
        ];

        // Get data from API
        $services = $this->fetchApiData();

        if ($services === false) {
            return $this->stats;
        }

        $current_altegio_ids = [];

        // Process each service
        foreach ($services as $service) {
            if ($this->processItem($service)) {
                $current_altegio_ids[] = $service['id'];
            }
        }

        // Clean up old services
        $this->cleanupOldServices($current_altegio_ids);

        $this->logger->log('Service synchronization completed', AltegioLogger::INFO, $this->stats);

        return $this->stats;
    }
}
