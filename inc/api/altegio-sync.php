<?php

add_action('altegio_sync_services', 'sync_altegio_services');

function sync_altegio_services()
{
    if (!class_exists('AltegioClient')) {
        require_once __DIR__ . '/../api/altegio-client.php';
    }

    $services = AltegioClient::getServices();

    if (!isset($services['success']) || !$services['success']) {
        error_log('Failed to fetch services from Altegio API');
        return;
    }

    foreach ($services['data'] as $item) {
        $title = $item['title'] ?? '';
        $price = $item['price_min'] ?? 0;
        $duration = $item['duration'] ?? 0;

        if (!$title) continue;

        $post_id = wp_insert_post([
            'post_type'   => 'service',
            'post_title'  => wp_strip_all_tags($title),
            'post_status' => 'publish',
        ]);

        if (!is_wp_error($post_id)) {
            update_post_meta($post_id, '_altegio_id', $item['id']);
            update_post_meta($post_id, '_price_min', $price);
            update_post_meta($post_id, '_duration', $duration);
        }
    }
}
