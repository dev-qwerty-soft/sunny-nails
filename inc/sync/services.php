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
    protected $meta_key = '_altegio_id'; // Changed from 'altegio_id' to match original code

    /**
     * @var string
     */
    protected $taxonomy = 'service_category';

    /**
     * @var string
     */
    protected $category_meta_key = '_altegio_category_id';

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

        // Process categories
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
     * Process service categories
     * 
     * @param int $post_id Service post ID
     * @param array $item_data Service data
     */
    protected function processServiceCategories($post_id, $item_data)
    {
        $category_id = isset($item_data['category_id']) ? sanitize_text_field($item_data['category_id']) : '';

        if (empty($category_id)) {
            return;
        }

        // Save local category
        update_post_meta($post_id, 'local_category', $category_id);

        // Find term by Altegio ID
        $category_terms = get_terms([
            'taxonomy' => $this->taxonomy,
            'meta_key' => $this->category_meta_key,
            'meta_value' => $category_id,
            'hide_empty' => false,
        ]);

        if (!empty($category_terms) && !is_wp_error($category_terms)) {
            // Set terms for the post
            wp_set_object_terms($post_id, $category_terms[0]->term_id, $this->taxonomy);
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

        // Process each service
        foreach ($services as $service) {
            $this->processItem($service);
        }

        $this->logger->log('Service synchronization completed', AltegioLogger::INFO, $this->stats);

        return $this->stats;
    }
}
