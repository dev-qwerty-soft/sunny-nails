<?php
// Temporary script to setup benefit icons
// Visit this file in browser once, then delete it

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo '<h1>Setting up Benefit Icons</h1>';

// Find icons by filename in uploads
$discount_icon = get_posts(array(
    'post_type' => 'attachment',
    'meta_query' => array(
        array(
            'key' => '_wp_attached_file',
            'value' => 'benefit-discount-icon.svg',
            'compare' => 'LIKE'
        )
    ),
    'posts_per_page' => 1
));

$complimentary_icon = get_posts(array(
    'post_type' => 'attachment',
    'meta_query' => array(
        array(
            'key' => '_wp_attached_file',
            'value' => 'benefit-complimentary-icon.svg',
            'compare' => 'LIKE'
        )
    ),
    'posts_per_page' => 1
));

$gift_icon = get_posts(array(
    'post_type' => 'attachment',
    'meta_query' => array(
        array(
            'key' => '_wp_attached_file',
            'value' => 'benefit-gift-icon.svg',
            'compare' => 'LIKE'
        )
    ),
    'posts_per_page' => 1
));

if (!empty($discount_icon)) {
    update_option('benefit_discount_icon_id', $discount_icon[0]->ID);
    echo '<p>✓ Discount icon found and set: ID ' . $discount_icon[0]->ID . '</p>';
} else {
    echo '<p>✗ Discount icon not found</p>';
}

if (!empty($complimentary_icon)) {
    update_option('benefit_complimentary_icon_id', $complimentary_icon[0]->ID);
    echo '<p>✓ Complimentary icon found and set: ID ' . $complimentary_icon[0]->ID . '</p>';
} else {
    echo '<p>✗ Complimentary icon not found</p>';
}

if (!empty($gift_icon)) {
    update_option('benefit_gift_icon_id', $gift_icon[0]->ID);
    echo '<p>✓ Gift icon found and set: ID ' . $gift_icon[0]->ID . '</p>';
} else {
    echo '<p>✗ Gift icon not found</p>';
}

update_option('benefit_icons_setup_done', true);

echo '<h2>Current Settings:</h2>';
echo '<p>Discount Icon ID: ' . get_option('benefit_discount_icon_id') . '</p>';
echo '<p>Complimentary Icon ID: ' . get_option('benefit_complimentary_icon_id') . '</p>';
echo '<p>Gift Icon ID: ' . get_option('benefit_gift_icon_id') . '</p>';

echo '<p><strong>Setup complete! You can now delete this file.</strong></p>';
