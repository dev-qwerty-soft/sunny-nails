<?php

/**
 * Booking Popup Template
 * 
 * This template is included in the footer and displays the booking popup.
 */

// Get necessary data
$staffList = isset($staff_list) && !empty($staff_list['data']) ? $staff_list['data'] : [];
$ordered_category_ids = function_exists('get_field') ? get_field('category_selection') : [];

if (empty($ordered_category_ids)) {
    $service_categories = get_terms([
        'taxonomy' => 'service_category',
        'hide_empty' => true,
        'orderby' => 'name',
    ]);
} else {
    $service_categories = [];
    foreach ($ordered_category_ids as $cat_id) {
        $term = get_term($cat_id, 'service_category');
        if (!is_wp_error($term)) {
            $service_categories[] = $term;
        }
    }
}

function get_services_by_category($category_id)
{
    return get_posts([
        'post_type' => 'service',
        'posts_per_page' => -1,
        'tax_query' => [
            [
                'taxonomy' => 'service_category',
                'field' => 'term_id',
                'terms' => $category_id,
            ],
        ],
        'meta_key' => 'price_min',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
    ]);
}
?>

<!-- Booking Popup Overlay -->
<div class="booking-popup-overlay">
    <div class="booking-popup">
        <button class="booking-popup-close">
            <svg width="29" height="29" viewBox="0 0 29 29" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13.2178 14.5L5.70132 6.98538C5.61706 6.90112 5.55022 6.80109 5.50462 6.691C5.45902 6.5809 5.43555 6.46291 5.43555 6.34375C5.43555 6.22459 5.45902 6.1066 5.50462 5.99651C5.55022 5.88642 5.61706 5.78638 5.70132 5.70213C5.78558 5.61787 5.88561 5.55103 5.9957 5.50543C6.10579 5.45983 6.22378 5.43636 6.34294 5.43636C6.4621 5.43636 6.5801 5.45983 6.69019 5.50543C6.80028 5.55103 6.90031 5.61787 6.98457 5.70213L14.4992 13.2186L22.0138 5.70213C22.184 5.53196 22.4148 5.43636 22.6554 5.43636C22.8961 5.43636 23.1269 5.53196 23.2971 5.70213C23.4672 5.87229 23.5628 6.10309 23.5628 6.34375C23.5628 6.58441 23.4672 6.81521 23.2971 6.98538L15.7806 14.5L23.2971 22.0146C23.4672 22.1848 23.5628 22.4156 23.5628 22.6562C23.5628 22.8969 23.4672 23.1277 23.2971 23.2979C23.1269 23.468 22.8961 23.5636 22.6554 23.5636C22.4148 23.5636 22.184 23.468 22.0138 23.2979L14.4992 15.7814L6.98457 23.2979C6.8144 23.468 6.5836 23.5636 6.34294 23.5636C6.10229 23.5636 5.87149 23.468 5.70132 23.2979C5.53115 23.1277 5.43555 22.8969 5.43555 22.6562C5.43555 22.4156 5.53115 22.1848 5.70132 22.0146L13.2178 14.5Z" fill="#302F34" />
            </svg>
        </button>

        <div class="booking-popup-content">
            <!-- Booking Steps Content -->
            <div class="booking-steps-container">

                <!-- Step 1: Initial Options -->
                <div class="booking-step active" data-step="initial">
                    <div class="step-header">
                        <h2 class="booking-title">Book an appointment</h2>
                    </div>

                    <div class="booking-option-item active" data-option="services">
                        <div class="option-icon">
                            <svg width="44" height="45" viewBox="0 0 44 45" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14.0325 17.2126V15.3793H36.6668V17.2126H14.0325ZM14.0325 23.4166V21.5833H36.6668V23.4166H14.0325ZM14.0325 29.6225V27.7891H36.6668V29.6225H14.0325ZM8.46283 17.4216C8.14261 17.4216 7.87433 17.3098 7.658 17.0861C7.44166 16.8649 7.3335 16.5893 7.3335 16.2593C7.3335 15.9501 7.44166 15.691 7.658 15.482C7.87433 15.2717 8.14261 15.1666 8.46283 15.1666C8.78183 15.1666 9.0495 15.2717 9.26583 15.482C9.48216 15.6897 9.59033 15.9488 9.59033 16.2593C9.59033 16.5893 9.48216 16.8655 9.26583 17.088C9.0495 17.3116 8.78122 17.4235 8.461 17.4235M8.461 23.5926C8.142 23.5926 7.87433 23.4881 7.658 23.2791C7.44166 23.0701 7.3335 22.8104 7.3335 22.5C7.3335 22.1455 7.44166 21.8632 7.658 21.653C7.87433 21.4427 8.14261 21.337 8.46283 21.3358C8.78305 21.3346 9.05072 21.4397 9.26583 21.6511C9.48094 21.8626 9.58911 22.1455 9.59033 22.5C9.59033 22.8092 9.48216 23.0689 9.26583 23.2791C9.0495 23.4893 8.78122 23.5938 8.461 23.5926ZM8.461 29.8333C8.142 29.8333 7.87433 29.7221 7.658 29.4996C7.44166 29.276 7.3335 28.9997 7.3335 28.671C7.3335 28.3605 7.44166 28.1008 7.658 27.8918C7.87433 27.6816 8.14261 27.5765 8.46283 27.5765C8.78183 27.5765 9.0495 27.6816 9.26583 27.8918C9.48216 28.102 9.59033 28.3617 9.59033 28.671C9.59033 28.9997 9.48216 29.276 9.26583 29.4996C9.0495 29.7221 8.78122 29.8333 8.461 29.8333Z" fill="#302F34" />
                            </svg>
                        </div>
                        <div class="option-text">Select services</div>
                        <div class="option-status">
                            <span class="status-indicator active"></span>
                        </div>
                    </div>

                    <div class="booking-option-item" data-option="master">
                        <div class="option-icon">
                            <svg width="35" height="34" viewBox="0 0 35 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12.8449 16.9655C15.5785 16.9655 17.7932 14.7508 17.7932 12.0172C17.7932 9.28368 15.5785 7.06897 12.8449 7.06897C10.1114 7.06897 7.89665 9.28368 7.89665 12.0172C7.89665 14.7508 10.1114 16.9655 12.8449 16.9655ZM16.3794 12.0172C16.3794 13.9704 14.7981 15.5517 12.8449 15.5517C10.8918 15.5517 9.31045 13.9704 9.31045 12.0172C9.31045 10.0641 10.8918 8.48276 12.8449 8.48276C14.7981 8.48276 16.3794 10.0641 16.3794 12.0172ZM3.65527 26.8621V22.9035C3.65527 19.8949 9.7777 18.3793 12.8449 18.3793C14.885 18.3793 18.2774 19.0502 20.3204 20.3848C21.7766 19.991 23.3734 19.7931 24.5087 19.7931C25.8221 19.7931 27.5844 20.0575 29.0399 20.5919C29.7652 20.8591 30.4601 21.2097 30.9853 21.6636C31.5148 22.1209 31.9311 22.7395 31.9311 23.5156V26.8621H3.65527ZM5.06907 22.9035C5.06907 22.6794 5.17227 22.3726 5.61055 21.9739C6.05801 21.5667 6.74653 21.1723 7.61814 20.8287C9.36488 20.1395 11.4856 19.7931 12.8449 19.7931C14.2043 19.7931 16.3257 20.1395 18.071 20.8287C18.9433 21.1723 19.6318 21.5667 20.0786 21.9739C20.5176 22.3726 20.6208 22.6794 20.6208 22.9035V25.4483H5.06907V22.9035ZM21.5772 21.5391C22.6468 21.3179 23.7191 21.2069 24.5087 21.2069C25.673 21.2069 27.2677 21.4472 28.5522 21.9195C29.1947 22.1556 29.7143 22.4341 30.0614 22.7338C30.4042 23.03 30.5173 23.2901 30.5173 23.5163V25.4483H22.0346V22.9035C22.0346 22.4086 21.8685 21.9527 21.5772 21.5391ZM28.3967 14.4914C28.3967 16.6396 26.657 18.3793 24.5087 18.3793C23.4776 18.3793 22.4887 17.9697 21.7595 17.2406C21.0304 16.5114 20.6208 15.5225 20.6208 14.4914C20.6208 12.3431 22.3605 10.6035 24.5087 10.6035C26.657 10.6035 28.3967 12.3431 28.3967 14.4914ZM24.5087 16.9655C25.8759 16.9655 26.9829 15.8585 26.9829 14.4914C26.9829 13.1242 25.8759 12.0172 24.5087 12.0172C23.8525 12.0172 23.2232 12.2779 22.7592 12.7419C22.2952 13.2059 22.0346 13.8352 22.0346 14.4914C22.0346 15.8585 23.1416 16.9655 24.5087 16.9655Z" fill="#302F34" />
                            </svg>
                        </div>
                        <div class="option-text">Choose a master</div>
                        <div class="option-status">
                            <span class="status-indicator"></span>
                        </div>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="btn yellow next-btn">Next </button>
                    </div>
                </div>

                <!-- Step 2: Select Services -->
                <div class="booking-step" data-step="services">
                    <div class="step-header">
                        <button type="button" class="booking-back-btn"> back</button>
                        <h2 class="booking-title">Select services</h2>
                    </div>

                    <div class="booking-service-categories">
                        <?php foreach ($service_categories as $i => $category): ?>
                            <button type="button" class="category-tab<?php echo $i === 0 ? ' active' : ''; ?>" data-category-id="<?php echo esc_attr($category->term_id); ?>">
                                <?php echo esc_html($category->name); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="services-list">
                        <?php foreach ($service_categories as $i => $category): ?>
                            <?php $services = get_services_by_category($category->term_id); ?>
                            <div class="category-services" data-category-id="<?php echo esc_attr($category->term_id); ?>" style="<?php echo $i === 0 ? '' : 'display:none'; ?>">
                                <?php
                                // Display core services first
                                foreach ($services as $service):
                                    $post_id = $service->ID;
                                    $price = get_post_meta($post_id, 'price_min', true);
                                    $currency = get_post_meta($post_id, 'currency', true) ?: 'SGD';
                                    $duration = get_post_meta($post_id, 'duration_minutes', true);
                                    $wear_time = get_post_meta($post_id, 'wear_time', true);
                                    $desc = get_post_meta($post_id, 'description', true);

                                    // Check if it's an add-on
                                    $is_addon = get_post_meta($post_id, 'is_addon', true) === 'yes';
                                    if ($is_addon) continue; // Skip add-ons for now
                                ?>
                                    <div class="service-item" data-service-id="<?php echo esc_attr($post_id); ?>">
                                        <div class="service-info">
                                            <div class="service-title">
                                                <h4 class="service-name"><?php echo esc_html(get_the_title($post_id)); ?></h4>
                                                <div class="service-checkbox-wrapper">
                                                    <div class="service-price">
                                                        <?php echo esc_html($price); ?> <?php echo esc_html($currency); ?>
                                                    </div>
                                                    <input type="checkbox"
                                                        class="service-checkbox"
                                                        data-service-id="<?php echo esc_attr($post_id); ?>"
                                                        data-service-title="<?php echo esc_attr(get_the_title($post_id)); ?>"
                                                        data-service-price="<?php echo esc_attr($price); ?>"
                                                        data-service-currency="<?php echo esc_attr($currency); ?>"
                                                        data-is-addon="false"
                                                        <?php if ($duration): ?>data-service-duration="<?php echo esc_attr($duration); ?>" <?php endif; ?>
                                                        <?php if ($wear_time): ?>data-service-wear-time="<?php echo esc_attr($wear_time); ?>" <?php endif; ?>>
                                                </div>
                                            </div>
                                            <?php if ($duration): ?>
                                                <div class="service-duration">Duration: <?php echo esc_html($duration); ?> min</div>
                                            <?php endif; ?>
                                            <?php if ($wear_time): ?>
                                                <div class="service-wear-time">Wear time: <?php echo esc_html($wear_time); ?></div>
                                            <?php endif; ?>
                                            <?php if ($desc): ?>
                                                <div class="service-description"><?php echo esc_html($desc); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Add-on services -->

                                <div class="addon-services-container">
                                    <?php
                                    // Display add-ons second
                                    foreach ($services as $service):
                                        $post_id = $service->ID;
                                        // Check if it's an add-on
                                        $is_addon = get_post_meta($post_id, 'is_addon', true) === 'yes';
                                        if (!$is_addon) continue; // Skip core services

                                        $price = get_post_meta($post_id, 'price_min', true);
                                        $currency = get_post_meta($post_id, 'currency', true) ?: 'SGD';
                                        $duration = get_post_meta($post_id, 'duration_minutes', true);
                                        $wear_time = get_post_meta($post_id, 'wear_time', true);
                                        $desc = get_post_meta($post_id, 'description', true);
                                    ?>
                                        <div class="service-item addon-item disabled" data-service-id="<?php echo esc_attr($post_id); ?>">
                                            <div class="service-info">
                                                <div class="service-title">
                                                    <h4 class="service-name"><?php echo esc_html(get_the_title($post_id)); ?> <span class="addon-label">(add-on)</span></h4>
                                                    <div class="service-checkbox-wrapper">
                                                        <div class="service-price">
                                                            <?php echo esc_html($price); ?> <?php echo esc_html($currency); ?>
                                                        </div>
                                                        <input type="checkbox"
                                                            class="service-checkbox"
                                                            data-service-id="<?php echo esc_attr($post_id); ?>"
                                                            data-service-title="<?php echo esc_attr(get_the_title($post_id)); ?>"
                                                            data-service-price="<?php echo esc_attr($price); ?>"
                                                            data-service-currency="<?php echo esc_attr($currency); ?>"
                                                            data-is-addon="true"
                                                            disabled
                                                            <?php if ($duration): ?>data-service-duration="<?php echo esc_attr($duration); ?>" <?php endif; ?>
                                                            <?php if ($wear_time): ?>data-service-wear-time="<?php echo esc_attr($wear_time); ?>" <?php endif; ?>>
                                                    </div>
                                                </div>
                                                <?php if ($duration): ?>
                                                    <div class="service-duration">Duration: <?php echo esc_html($duration); ?> min</div>
                                                <?php endif; ?>
                                                <?php if ($wear_time): ?>
                                                    <div class="service-wear-time">Wear time: <?php echo esc_html($wear_time); ?></div>
                                                <?php endif; ?>
                                                <?php if ($desc): ?>
                                                    <div class="service-description"><?php echo esc_html($desc); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="btn yellow next-btn"> Choose a master </button>
                    </div>
                </div>

                <!-- Step 3: Choose a Master -->
                <div class="booking-step" data-step="master">
                    <div class="step-header">
                        <button type="button" class="booking-back-btn"> back</button>
                        <h2 class="booking-title">Choose a master</h2>
                    </div>

                    <div class="staff-list">
                        <label class="staff-item any-master first selected" data-staff-id="any" data-staff-level="0">
                            <input type="radio" name="staff" checked>
                            <div class="staff-radio-content">
                                <div class="staff-avatar circle yellow-bg">
                                    <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M16.4891 6.89062C16.3689 8.55873 15.1315 9.84375 13.7821 9.84375C12.4327 9.84375 11.1932 8.55914 11.0751 6.89062C10.952 5.15525 12.1566 3.9375 13.7821 3.9375C15.4075 3.9375 16.6122 5.18684 16.4891 6.89062Z" stroke="#302F34" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M13.7811 12.4688C11.1081 12.4688 8.53765 13.7964 7.8937 16.3821C7.80839 16.7241 8.0229 17.0625 8.37441 17.0625H19.1882C19.5397 17.0625 19.753 16.7241 19.6689 16.3821C19.0249 13.755 16.4545 12.4688 13.7811 12.4688Z" stroke="#302F34" stroke-miterlimit="10" />
                                        <path d="M8.20211 7.62645C8.10614 8.95863 7.10618 10.0078 6.02828 10.0078C4.95039 10.0078 3.94879 8.95904 3.85446 7.62645C3.75643 6.24053 4.72973 5.25 6.02828 5.25C7.32684 5.25 8.30014 6.26596 8.20211 7.62645Z" stroke="#302F34" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M8.44962 12.5507C7.70929 12.2115 6.8939 12.0811 6.0297 12.0811C3.89689 12.0811 1.842 13.1413 1.32726 15.2065C1.25958 15.4796 1.43103 15.7499 1.71157 15.7499H6.31681" stroke="#302F34" stroke-miterlimit="10" stroke-linecap="round" />
                                    </svg>

                                </div>
                                <div class="staff-info">
                                    <h4 class="staff-name">Any master</h4>
                                </div>
                                <span class="radio-indicator"></span>
                            </div>
                        </label>

                        <?php if (!empty($staffList)) : ?>
                            <?php foreach ($staffList as $staff) :
                                $level = isset($staff['level']) ? intval($staff['level']) : 1;
                                $stars = str_repeat('<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20.8965 18.008L18.6085 15.7L19.2965 15.012L21.6045 17.3L20.8965 18.008ZM17.7005 6.373L17.0125 5.685L19.3005 3.396L20.0085 4.085L17.7005 6.373ZM6.30048 6.393L4.01148 4.084L4.70048 3.395L7.00848 5.684L6.30048 6.393ZM3.08548 18.007L2.39648 17.299L4.68548 15.01L5.39248 15.699L3.08548 18.007ZM6.44048 20L7.91048 13.725L3.00048 9.481L9.47048 8.933L12.0005 3L14.5505 8.933L21.0205 9.481L16.1085 13.725L17.5785 20L12.0005 16.66L6.44048 20Z" fill="#FDC41F"/>
                                    </svg>
                                    ', $level);
                                $markup = $level === 2 ? '+10% to price' : ($level >= 3 ? '+20% to price' : '');
                            ?>
                                <label class="staff-item" data-staff-id="<?php echo esc_attr($staff['id']); ?>" data-staff-level="<?php echo esc_attr($level); ?>">
                                    <input type="radio" name="staff">
                                    <div class="staff-radio-content">
                                        <div class="staff-avatar">
                                            <?php if (!empty($staff['avatar'])) : ?>
                                                <img src="<?php echo esc_url($staff['avatar']); ?>" alt="<?php echo esc_attr($staff['name']); ?>">
                                            <?php endif; ?>
                                        </div>
                                        <div class="staff-info">
                                            <h4 class="staff-name"><?php echo esc_html($staff['name']); ?></h4>
                                            <p class="staff-specialization"><?php echo $stars; ?> <span class="studio-name">(<?php echo esc_html($staff['specialization']); ?>)</span></p>
                                        </div>
                                        <?php if ($markup): ?>
                                            <div class="staff-price-modifier"><?php echo $markup; ?></div>
                                        <?php endif; ?>
                                        <span class="radio-indicator"></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="no-items-message">No specialists available at the moment.</p>
                        <?php endif; ?>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="btn yellow next-btn"> Select date and time </button>
                    </div>
                </div>


                <!-- Step 4: Select Date and Time -->
                <div class="booking-step" data-step="datetime">
                    <div class="step-header">
                        <button type="button" class="booking-back-btn"> back</button>
                        <h2 class="booking-title">Select date and time</h2>
                    </div>
                    <div class="datetime-container">
                        <div class="date-selector">
                            <div class="month-header">

                                <span сlass="current-month">May 2025</span>
                                <div class="month-controls">
                                    <button type="button" class="prev-month">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M9.00163 0.71811C9.37537 0.32692 10 0.32692 10.3738 0.71811C10.7242 1.08485 10.7242 1.6623 10.3738 2.02904L5.62459 7L10.3738 11.971C10.7242 12.3377 10.7242 12.9152 10.3738 13.2819C10 13.6731 9.37537 13.6731 9.00163 13.2819L3 7L9.00163 0.71811Z" fill="#302F34" />
                                        </svg>
                                    </button>
                                    <button type="button" class="next-month">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M4.99837 0.71811C4.62463 0.32692 3.99996 0.32692 3.62622 0.71811C3.27584 1.08485 3.27584 1.6623 3.62622 2.02904L8.37541 7L3.62622 11.971C3.27584 12.3377 3.27584 12.9152 3.62622 13.2819C3.99996 13.6731 4.62463 13.6731 4.99837 13.2819L11 7L4.99837 0.71811Z" fill="#302F34" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="weekdays">
                                <div>Mo</div>
                                <div>Tu</div>
                                <div>We</div>
                                <div>Th</div>
                                <div>Fr</div>
                                <div>Sa</div>
                                <div>Su</div>
                            </div>
                            <div class="calendar-grid"></div>
                        </div>
                        <div class="time-selector">
                            <div class="time-header">
                                <span>Available times</span>
                            </div>
                            <div class="time-slots"></div>
                        </div>
                    </div>
                    <div class="step-actions">

                        <button type="button" class="btn yellow next-btn">Book an appointment </button>
                    </div>
                </div>

                <!-- Step 5: Booking Details -->
                <div class="booking-step" data-step="contact">
                    <div class="step-header">
                        <button type="button" class="booking-back-btn"> back</button>
                        <h2 class="booking-title">Booking details</h2>
                    </div>
                    <div class="booking-details-container">
                        <div class="contact-form">
                            <div class="form-group">
                                <label for="client-name">Name</label>
                                <input type="text" id="client-name" name="client_name" required>
                            </div>
                            <div class="form-group">
                                <label for="client-phone">Phone</label>
                                <input type="tel" id="client-phone" name="client_phone" required>
                            </div>
                            <div class="form-group">
                                <label for="client-email">Email</label>
                                <input type="email" id="client-email" name="client_email">
                            </div>
                            <div class="form-group">
                                <label for="client-comment">Comment</label>
                                <textarea id="client-comment" name="client_comment"></textarea>
                            </div>
                        </div>
                        <div class="booking-summary">
                            <h3>Selected master</h3>
                            <div class="selected-master-info"></div>

                            <h3>Selected services</h3>
                            <div class="summary-services-list"></div>

                            <div class="total-price">
                                <span>Total:</span>
                                <span class="summary-total-amount">0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="book-btn confirm-booking-btn">Confirm booking
                            <span class="book-bt__icon">
                                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.22581 0.773971L9.22581 8.01857M9.22581 0.773971L1.98122 0.773971M9.22581 0.773971L0.773784 9.226" stroke="#302F34" stroke-width="0.838404" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Step 6: Confirmation -->
                <div class="booking-step" data-step="confirm">
                    <div class="step-header">
                        <h2 class="booking-title">Booking confirmed</h2>
                    </div>
                    <div class="confirmation-container">
                        <div class="confirmation-icon">✓</div>
                        <p class="confirmation-message">Your booking has been successfully confirmed!</p>
                        <div class="confirmation-details">
                            <p>Booking reference: <span class="booking-reference"></span></p>
                            <p>Date: <span class="booking-date"></span></p>
                            <p>Time: <span class="booking-time"></span></p>
                        </div>
                        <div class="booked-services-summary"></div>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="book-btn close-popup-btn">Done
                            <span class="book-bt__icon">
                                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.22581 0.773971L9.22581 8.01857M9.22581 0.773971L1.98122 0.773971M9.22581 0.773971L0.773784 9.226" stroke="#302F34" stroke-width="0.838404" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>