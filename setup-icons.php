<?php

/**
 * Setup script to configure benefit icons
 * Run this once after uploading icons to media library
 */

require_once('wp-config.php');

// Find the uploaded icons by filename or ID
// Replace these IDs with actual IDs from your media library
$discount_icon_id = 123; // Replace with actual ID of discount icon
$complimentary_icon_id = 124; // Replace with actual ID of complimentary icon  
$gift_icon_id = 125; // Replace with actual ID of gift icon

// Set the options
update_option('benefit_discount_icon_id', $discount_icon_id);
update_option('benefit_complimentary_icon_id', $complimentary_icon_id);
update_option('benefit_gift_icon_id', $gift_icon_id);

echo "Benefit icons configured successfully!\n";
echo "Discount icon ID: " . $discount_icon_id . "\n";
echo "Complimentary icon ID: " . $complimentary_icon_id . "\n";
echo "Gift icon ID: " . $gift_icon_id . "\n";

// Test the setup
function test_icon_setup()
{
    include_once(get_template_directory() . '/inc/helpers/benefit-icons.php');

    $types = ['discount', 'complimentary', 'gift'];
    foreach ($types as $type) {
        $icon_data = get_benefit_icon_data($type);
        if ($icon_data) {
            echo "$type icon: " . $icon_data['url'] . "\n";
        } else {
            echo "$type icon: NOT CONFIGURED\n";
        }
    }
}

test_icon_setup();
