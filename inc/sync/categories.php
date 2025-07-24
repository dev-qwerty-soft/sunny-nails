<?php

if (!defined('ABSPATH')) {
  exit();
}

require_once dirname(__FILE__) . '/abstract/sync-base.php';

class AltegioSyncCategories extends AltegioSyncBase {
  protected $taxonomy = 'service_category';
  protected $meta_key = '_altegio_category_id';

  protected function fetchApiData() {
    $categories = $this->api_client::getServiceCategories();

    if (!isset($categories['success']) || !$categories['success'] || !isset($categories['data'])) {
      $this->logger->log(
        'Failed to fetch service categories from Altegio API',
        AltegioLogger::ERROR,
      );
      return false;
    }

    return $categories['data'];
  }

  protected function processItem($item_data) {
    $altegio_id = $item_data['id'] ?? null;
    $title = sanitize_text_field($item_data['title'] ?? '');
    $weight = intval($item_data['weight'] ?? 0);

    if (!$altegio_id || !$title) {
      $this->logger->log('Missing required category data', AltegioLogger::WARNING, $item_data);
      $this->stats['skipped']++;
      return false;
    }

    $existing_term_id = $this->findExistingTermByAltegioId(
      $altegio_id,
      $this->meta_key,
      $this->taxonomy,
    );

    if ($existing_term_id) {
      return $this->updateExistingTerm($existing_term_id, $title, $weight, $altegio_id);
    }

    $existing_by_name = get_term_by('name', $title, $this->taxonomy);
    if ($existing_by_name && !is_wp_error($existing_by_name)) {
      $existing_altegio_id = get_term_meta($existing_by_name->term_id, $this->meta_key, true);

      if (empty($existing_altegio_id)) {
        return $this->updateExistingTerm($existing_by_name->term_id, $title, $weight, $altegio_id);
      } elseif ($existing_altegio_id != $altegio_id) {
        $unique_title = $title . ' (ID: ' . $altegio_id . ')';
        return $this->createNewTerm($unique_title, $weight, $altegio_id);
      }
    }

    return $this->createNewTerm($title, $weight, $altegio_id);
  }

  protected function updateExistingTerm($term_id, $title, $weight, $altegio_id) {
    $result = wp_update_term($term_id, $this->taxonomy, [
      'name' => $title,
      'description' => sprintf('Weight: %d, Altegio ID: %s', $weight, $altegio_id),
    ]);

    if (is_wp_error($result)) {
      $this->logger->log('Error updating term', AltegioLogger::ERROR, [
        'term_id' => $term_id,
        'error' => $result->get_error_message(),
      ]);
      $this->stats['errors']++;
      return false;
    }

    update_term_meta($term_id, $this->meta_key, $altegio_id);
    update_term_meta($term_id, 'weight', $weight);
    update_term_meta($term_id, 'altegio_synced', time());

    $this->stats['updated']++;
    return true;
  }

  protected function createNewTerm($title, $weight, $altegio_id) {
    $slug = sanitize_title($title);
    $original_slug = $slug;
    $counter = 1;

    while (term_exists($slug, $this->taxonomy)) {
      $slug = $original_slug . '-' . $counter;
      $counter++;
    }

    $result = wp_insert_term($title, $this->taxonomy, [
      'slug' => $slug,
      'description' => sprintf('Weight: %d, Altegio ID: %s', $weight, $altegio_id),
    ]);

    if (is_wp_error($result)) {
      $this->logger->log('Error creating term', AltegioLogger::ERROR, [
        'title' => $title,
        'error' => $result->get_error_message(),
      ]);
      $this->stats['errors']++;
      return false;
    }

    $term_id = $result['term_id'];
    update_term_meta($term_id, $this->meta_key, $altegio_id);
    update_term_meta($term_id, 'weight', $weight);
    update_term_meta($term_id, 'altegio_synced', time());

    $this->stats['created']++;
    return true;
  }

  protected function deleteObsoleteCategories($current_altegio_ids) {
    $all_terms = get_terms([
      'taxonomy' => $this->taxonomy,
      'hide_empty' => false,
      'fields' => 'ids',
    ]);

    $deleted_count = 0;

    foreach ($all_terms as $term_id) {
      $altegio_id = get_term_meta($term_id, $this->meta_key, true);

      if ($altegio_id && !in_array($altegio_id, $current_altegio_ids)) {
        $result = wp_delete_term($term_id, $this->taxonomy);

        if (!is_wp_error($result) && $result !== false) {
          $deleted_count++;
        } else {
          $this->stats['errors']++;
        }
      } elseif (!$altegio_id) {
        $result = wp_delete_term($term_id, $this->taxonomy);

        if (!is_wp_error($result) && $result !== false) {
          $deleted_count++;
        } else {
          $this->stats['errors']++;
        }
      }
    }

    return $deleted_count;
  }

  public function sync() {
    $this->logger->log('Starting category synchronization', AltegioLogger::INFO);

    $this->stats = [
      'created' => 0,
      'updated' => 0,
      'skipped' => 0,
      'errors' => 0,
      'deleted' => 0,
    ];

    $categories = $this->fetchApiData();
    if ($categories === false) {
      return $this->stats;
    }

    $current_altegio_ids = [];

    foreach ($categories as $category) {
      if ($this->processItem($category)) {
        $current_altegio_ids[] = $category['id'];
      }
    }

    $deleted_count = $this->deleteObsoleteCategories($current_altegio_ids);
    $this->stats['deleted'] = $deleted_count;

    wp_cache_flush();

    $this->logger->log('Category synchronization completed', AltegioLogger::INFO, $this->stats);

    return $this->stats;
  }
}
