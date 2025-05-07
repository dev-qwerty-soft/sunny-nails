<?php

/**
 * Categories synchronization class for Altegio API
 *
 * @package AltegioSync
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once dirname(__FILE__) . '/abstract/sync-base.php';

/**
 * Class for synchronizing categories from Altegio API
 */
class AltegioSyncCategories extends AltegioSyncBase
{
    /**
     * @var string
     */
    protected $taxonomy = 'service_category';

    /**
     * @var string
     */
    protected $meta_key = '_altegio_category_id';

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
        $categories = $this->api_client::getCategories();

        if (!isset($categories['success']) || $categories['success'] != 1) {
            $this->logger->log('Failed to fetch categories from Altegio API', AltegioLogger::ERROR);
            return false;
        }

        if (!isset($categories['data']) || !is_array($categories['data'])) {
            $this->logger->log('No category data found in Altegio API response', AltegioLogger::ERROR);
            return false;
        }

        return $categories['data'];
    }

    /**
     * Process one data item (category)
     * 
     * @param array $item_data Item data
     * @return bool Operation success
     */
    protected function processItem($item_data)
    {
        // Get field mapping for category
        $prepared_data = AltegioFieldsMapper::prepareCategoryData($item_data);

        if (empty($prepared_data)) {
            $this->logger->log('Failed to prepare category data', AltegioLogger::WARNING, $item_data);
            $this->stats['errors']++;
            return false;
        }

        $term_data = $prepared_data['term_data'];
        $acf_data = $prepared_data['acf_data'];

        $altegio_id = $acf_data['altegio_category_id'] ?? '';
        $parent_id = $acf_data['parent_category_id'] ?? '';

        if (empty($altegio_id) || empty($term_data['name'])) {
            $this->logger->log('Missing required category data (id or name)', AltegioLogger::WARNING, $item_data);
            $this->stats['skipped']++;
            return false;
        }

        // Check if term with this Altegio ID exists
        $existing_terms = get_terms([
            'taxonomy' => $this->taxonomy,
            'meta_key' => $this->meta_key,
            'meta_value' => $altegio_id,
            'hide_empty' => false,
        ]);

        if (!empty($existing_terms) && !is_wp_error($existing_terms)) {
            // Update existing term
            $term_id = $existing_terms[0]->term_id;

            $result = wp_update_term($term_id, $this->taxonomy, [
                'name' => $term_data['name'],
                'description' => $term_data['description'],
            ]);

            if (is_wp_error($result)) {
                $this->logger->log('Error updating category term', AltegioLogger::ERROR, [
                    'error' => $result->get_error_message(),
                    'term_id' => $term_id
                ]);
                $this->stats['errors']++;
                return false;
            }

            $this->stats['updated']++;
        } else {
            // Create new term
            $result = wp_insert_term(
                $term_data['name'],
                $this->taxonomy,
                ['description' => $term_data['description']]
            );

            if (is_wp_error($result)) {
                $this->logger->log('Error creating category term', AltegioLogger::ERROR, [
                    'error' => $result->get_error_message(),
                    'name' => $term_data['name']
                ]);
                $this->stats['errors']++;
                return false;
            }

            $term_id = $result['term_id'];

            // Save Altegio ID as term meta
            update_term_meta($term_id, $this->meta_key, $altegio_id);

            $this->stats['created']++;
        }

        // Save additional ACF fields if any
        if (function_exists('update_field')) {
            foreach ($acf_data as $key => $value) {
                update_field($key, $value, $this->taxonomy . '_' . $term_id);
            }
        }

        // Save parent category information for further processing
        if (!empty($parent_id)) {
            $this->category_mapping[$altegio_id] = [
                'term_id' => $term_id,
                'parent_id' => $parent_id
            ];
        }

        return true;
    }

    /**
     * Update parent-child relationships structure
     */
    protected function updateParentChildRelationships()
    {
        foreach ($this->category_mapping as $altegio_id => $data) {
            $parent_altegio_id = $data['parent_id'];

            // Find WordPress ID for parent term
            $parent_terms = get_terms([
                'taxonomy' => $this->taxonomy,
                'meta_key' => $this->meta_key,
                'meta_value' => $parent_altegio_id,
                'hide_empty' => false,
            ]);

            if (!empty($parent_terms) && !is_wp_error($parent_terms)) {
                $parent_term_id = $parent_terms[0]->term_id;

                // Update parent ID for term
                wp_update_term($data['term_id'], $this->taxonomy, [
                    'parent' => $parent_term_id
                ]);
            }
        }
    }

    /**
     * Start category synchronization process
     * 
     * @return array Synchronization results
     */
    public function sync()
    {
        $this->logger->log('Starting category synchronization', AltegioLogger::INFO);

        // Reset statistics
        $this->stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors'  => 0
        ];

        $this->category_mapping = [];

        // Get data from API
        $categories = $this->fetchApiData();

        if ($categories === false) {
            return $this->stats;
        }

        // Process each category
        foreach ($categories as $category) {
            $this->processItem($category);
        }

        // Set parent-child relationships
        $this->updateParentChildRelationships();

        $this->logger->log('Category synchronization completed', AltegioLogger::INFO, $this->stats);

        return $this->stats;
    }
}
