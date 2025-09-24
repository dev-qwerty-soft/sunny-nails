<?php

/**
 * Benefit Icons Helper Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get benefit icon ID by type
 */
function get_benefit_icon_id($type)
{
    $icon_mapping = [
        'discount' => get_option('benefit_discount_icon_id'),
        'complimentary' => get_option('benefit_complimentary_icon_id'),
        'gift' => get_option('benefit_gift_icon_id')
    ];

    return $icon_mapping[$type] ?? null;
}

/**
 * Set benefit icon IDs (use this in admin to set up icons)
 * Call this function once after uploading icons to media library
 */
function setup_benefit_icons()
{
    // You'll need to get these IDs from your media library after uploading
    // Example usage:
    // setup_benefit_icons_ids(123, 124, 125);
}

function setup_benefit_icons_ids($discount_id, $complimentary_id, $gift_id)
{
    update_option('benefit_discount_icon_id', $discount_id);
    update_option('benefit_complimentary_icon_id', $complimentary_id);
    update_option('benefit_gift_icon_id', $gift_id);
}

/**
 * Get benefit icon data (ID, URL, alt text)
 */
function get_benefit_icon_data($type)
{
    $icon_id = get_benefit_icon_id($type);

    if (!$icon_id) {
        return null;
    }

    return [
        'id' => $icon_id,
        'url' => wp_get_attachment_image_url($icon_id, 'full'),
        'alt' => get_post_meta($icon_id, '_wp_attachment_image_alt', true),
        'title' => get_the_title($icon_id)
    ];
}

/**
 * Admin notice to setup icons
 */
function benefit_icons_admin_notice()
{
    $icons_setup = get_option('benefit_discount_icon_id') &&
        get_option('benefit_complimentary_icon_id') &&
        get_option('benefit_gift_icon_id');

    if (!$icons_setup && current_user_can('manage_options')) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Benefit Icons Setup Required:</strong> Please upload benefit icons and configure them. ';
        echo '<a href="' . admin_url('upload.php') . '">Go to Media Library</a></p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'benefit_icons_admin_notice');

/**
 * Display media library with attachment IDs for easy configuration
 * Add this as temporary admin page
 */
function show_media_ids_admin_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>Media Library IDs</h1>';
    echo '<p>Find your uploaded benefit icons and use their IDs to configure the system:</p>';

    // Get recent images
    $attachments = get_posts(array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => 20,
        'post_status' => 'inherit'
    ));

    echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">';

    foreach ($attachments as $attachment) {
        $image_url = wp_get_attachment_image_url($attachment->ID, 'thumbnail');
        echo '<div style="border: 1px solid #ddd; padding: 10px; text-align: center;">';
        echo '<img src="' . esc_url($image_url) . '" style="max-width: 100px; max-height: 100px;">';
        echo '<p><strong>ID: ' . $attachment->ID . '</strong></p>';
        echo '<p>' . esc_html($attachment->post_title) . '</p>';
        echo '</div>';
    }

    echo '</div>';

    echo '<h2>Configuration Code</h2>';
    echo '<p>Add this to your theme\'s functions.php (replace IDs with your actual icon IDs):</p>';
    echo '<pre>
// Configure benefit icons (run once)
update_option(\'benefit_discount_icon_id\', 123); // Replace with discount icon ID
update_option(\'benefit_complimentary_icon_id\', 124); // Replace with complimentary icon ID  
update_option(\'benefit_gift_icon_id\', 125); // Replace with gift icon ID
</pre>';

    echo '</div>';
}

function add_media_ids_admin_menu()
{
    add_management_page(
        'Media IDs',
        'Media IDs',
        'manage_options',
        'media-ids',
        'show_media_ids_admin_page'
    );
}
add_action('admin_menu', 'add_media_ids_admin_menu');
