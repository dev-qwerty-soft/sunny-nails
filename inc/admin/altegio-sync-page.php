<?php

/**
 * Admin page for Altegio synchronization
 *
 * @package AltegioSync
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Require sync runner if not already included
if (!function_exists('run_complete_altegio_sync')) {
    require_once get_template_directory() . '/inc/sync/sync-runner.php';
}

/**
 * Add admin page for synchronization
 */
add_action('admin_menu', 'altegio_sync_menu');
function altegio_sync_menu()
{
    add_submenu_page(
        'tools.php',
        'Altegio Synchronization',
        'Altegio Sync',
        'manage_options',
        'altegio-sync',
        'altegio_sync_page'
    );
}

/**
 * Admin page content for synchronization
 */
function altegio_sync_page()
{
    echo '<div class="wrap">';
    echo '<h1>Altegio Service Synchronization</h1>';

    if (isset($_POST['sync_now']) && check_admin_referer('altegio_sync_nonce')) {
        $sync_type = isset($_POST['sync_type']) ? sanitize_text_field($_POST['sync_type']) : 'all';

        if ($sync_type === 'categories') {
            $count = sync_altegio_categories();
            echo '<div class="notice notice-success"><p>Synchronized ' . $count['created'] + $count['updated'] . ' categories.</p></div>';
        } elseif ($sync_type === 'services') {
            $count = sync_altegio_services();
            echo '<div class="notice notice-success"><p>Synchronized ' . $count['created'] + $count['updated'] . ' services.</p></div>';
        } elseif ($sync_type === 'masters') {
            $count = sync_altegio_masters();
            echo '<div class="notice notice-success"><p>Synchronized ' . $count['created'] + $count['updated'] . ' masters.</p></div>';
        } else {
            $result = run_complete_altegio_sync();
            echo '<div class="notice notice-success"><p>Synchronized ' .
                $result['categories']['created'] + $result['categories']['updated'] . ' categories, ' .
                $result['services']['created'] + $result['services']['updated'] . ' services, and ' .
                $result['masters']['created'] + $result['masters']['updated'] . ' masters.</p></div>';
        }
    }

    echo '<form method="post">';
    wp_nonce_field('altegio_sync_nonce');

    echo '<h2>Synchronization Options</h2>';
    echo '<p>Select what data you want to synchronize from the Altegio API:</p>';

    echo '<p>';
    echo '<label><input type="radio" name="sync_type" value="all" checked> Synchronize All Data</label><br>';
    echo '<label><input type="radio" name="sync_type" value="categories"> Synchronize Categories Only</label><br>';
    echo '<label><input type="radio" name="sync_type" value="services"> Synchronize Services Only</label><br>';
    echo '<label><input type="radio" name="sync_type" value="masters"> Synchronize Masters Only</label>';
    echo '</p>';

    echo '<p><input type="submit" name="sync_now" class="button button-primary" value="Start Synchronization"></p>';
    echo '</form>';

    $cat_count = wp_count_terms('service_category', ['hide_empty' => false]);
    $service_count = wp_count_posts('service');
    $master_count = wp_count_posts('master');

    echo '<div class="altegio-stats" style="margin-top: 30px; padding: 20px; background: #f8f8f8; border-radius: 5px;">';
    echo '<h2>Current Data Statistics</h2>';
    echo '<p><strong>Categories:</strong> ' . (is_wp_error($cat_count) ? '0' : $cat_count) . '</p>';
    echo '<p><strong>Services:</strong> ' . (isset($service_count->publish) ? $service_count->publish : '0') . '</p>';
    echo '<p><strong>Masters:</strong> ' . (isset($master_count->publish) ? $master_count->publish : '0') . '</p>';
    echo '</div>';

    echo '</div>';
}

/**
 * Register AJAX endpoints for synchronization
 */
add_action('wp_ajax_altegio_sync_categories', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    $count = sync_altegio_categories();
    wp_send_json_success(['count' => $count]);
});

add_action('wp_ajax_altegio_sync_services', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    $count = sync_altegio_services();
    wp_send_json_success(['count' => $count]);
});

add_action('wp_ajax_altegio_sync_masters', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    $count = sync_altegio_masters();
    wp_send_json_success(['count' => $count]);
});

/**
 * Enqueue admin scripts
 */
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'tools_page_altegio-sync') {
        return;
    }

    wp_enqueue_style(
        'altegio-admin-styles',
        get_template_directory_uri() . '/inc/admin/css/altegio-admin.css',
        [],
        '1.0.0'
    );
});
