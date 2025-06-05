<?php

/**
 * Categories synchronization class for Altegio API
 * @package AltegioSync
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/abstract/sync-base.php';

class AltegioSyncCategories extends AltegioSyncBase
{
    protected $taxonomy = 'service_category';
    protected $meta_key = '_altegio_category_id';
    protected $category_mapping = [];

    protected function fetchApiData()
    {
        $categories = $this->api_client::getServiceCategories();

        if (!isset($categories['success']) || !$categories['success'] || !isset($categories['data'])) {
            $this->logger->log('Failed to fetch service categories from Altegio API', AltegioLogger::ERROR);
            return false;
        }

        return $categories['data'];
    }

    protected function processItem($item_data)
    {
        $altegio_id = $item_data['id'] ?? null;
        $title = $item_data['title'] ?? null;

        if (!$altegio_id || !$title) {
            $this->logger->log('Missing required category data', AltegioLogger::WARNING, $item_data);
            $this->stats['skipped']++;
            return false;
        }

        $existing_terms = get_terms([
            'taxonomy' => $this->taxonomy,
            'meta_key' => $this->meta_key,
            'meta_value' => $altegio_id,
            'hide_empty' => false,
        ]);

        if (!empty($existing_terms) && !is_wp_error($existing_terms)) {
            $term_id = $existing_terms[0]->term_id;
            $result = wp_update_term($term_id, $this->taxonomy, ['name' => $title]);

            if (is_wp_error($result)) {
                $this->logger->log('Error updating term', AltegioLogger::ERROR, $result->get_error_message());
                $this->stats['errors']++;
                return false;
            }

            $this->stats['updated']++;
        } else {

            $term_check = term_exists($title, $this->taxonomy);
            if ($term_check && is_array($term_check)) {
                $term_id = $term_check['term_id'];
                update_term_meta($term_id, $this->meta_key, $altegio_id);
                $this->logger->log("Linked existing term '{$title}' with Altegio ID {$altegio_id}", AltegioLogger::INFO);
                $this->stats['updated']++;
                return true;
            }

            $result = wp_insert_term($title, $this->taxonomy);
            if (is_wp_error($result)) {
                $this->logger->log('Error creating term', AltegioLogger::ERROR, $result->get_error_message());
                $this->stats['errors']++;
                return false;
            }

            $term_id = $result['term_id'];
            update_term_meta($term_id, $this->meta_key, $altegio_id);
            $this->stats['created']++;
        }

        return true;
    }

    public function sync()
    {
        $this->logger->log('Starting service category sync', AltegioLogger::INFO);

        $this->stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $categories = $this->fetchApiData();

        if ($categories === false) {
            return $this->stats;
        }

        foreach ($categories as $category) {
            $this->processItem($category);
        }

        $existing_terms = get_terms([
            'taxonomy'   => $this->taxonomy,
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key'     => $this->meta_key,
                    'compare' => 'EXISTS'
                ]
            ]
        ]);

        $altegio_ids = array_map(fn($cat) => $cat['id'], $categories);

        foreach ($existing_terms as $term) {
            $term_altegio_id = get_term_meta($term->term_id, $this->meta_key, true);
            if (!in_array($term_altegio_id, $altegio_ids)) {
                wp_delete_term($term->term_id, $this->taxonomy);
                $this->logger->log("Deleted obsolete category: ID {$term->term_id}, Altegio ID {$term_altegio_id}", AltegioLogger::INFO);
            }
        }

        $this->logger->log('Finished service category sync', AltegioLogger::INFO, $this->stats);
        return $this->stats;
    }
}
