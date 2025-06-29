<?php

/**
 * Booking Popup Template
 * 
 * This template is included in the footer and displays the booking popup.
 */

// Get necessary data
$staffList = isset($staff_list) && !empty($staff_list['data']) ? $staff_list['data'] : [];
$ordered_category_ids = function_exists('get_field') ? get_field('category_selection', 'option') : [];

if (empty($ordered_category_ids)) {
    $service_categories_popup = get_terms([
        'taxonomy' => 'service_category',
        'hide_empty' => true,
        'orderby' => 'name',
    ]);
} else {
    $service_categories_popup = [];
    foreach ($ordered_category_ids as $cat_id) {
        $term = get_term($cat_id, 'service_category');
        if (!is_wp_error($term) && $term !== null) {
            $service_categories_popup[] = $term;
        }
    }
}


?>

<!-- Booking Popup Overlay -->
<div class="booking-popup-overlay">

    <div class="loading-overlay">
        <div class="loader"></div>
    </div>

    <div class="booking-popup">

        <div class="booking-popup-content">
            <!-- Booking Steps Content -->
            <div class="booking-steps-container">
                <button class="booking-popup-close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M1 1L13 13M13 1L1 13" stroke="#302F34" stroke-width="1.5" stroke-linecap="round" />
                    </svg>

                </button>
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
                        <?php foreach ($service_categories_popup as $i => $category_popup): ?>
                            <?php if ($category_popup && is_object($category_popup) && isset($category_popup->term_id, $category_popup->name)) : ?>
                                <button type="button" class="category-tab<?php echo $i === 0 ? ' active' : ''; ?>" data-category-id="<?php echo esc_attr($category_popup->term_id); ?>">
                                    <?php echo esc_html($category_popup->name); ?>
                                </button>

                            <?php endif; ?>
                        <?php endforeach;

                        ?>
                    </div>


                    <div class="services-list">
                        <?php foreach ($service_categories_popup as $i => $category_popup): ?>
                            <?php if ($category_popup && is_object($category_popup) && isset($category_popup->term_id)) : ?>
                                <?php $services = get_services_by_category($category_popup->term_id); ?>
                                <div class="category-services" data-category-id="<?php echo esc_attr($category_popup->term_id); ?>" style="<?php echo $i === 0 ? '' : 'display:none'; ?>">
                                    <?php foreach ($services as $service):
                                        setup_postdata($service);
                                        $post_id = $service->ID;

                                        // Get service categories
                                        $service_categories = wp_get_post_terms($post_id, 'service_category', ['fields' => 'slugs']);
                                        $category_slugs = is_array($service_categories) ? implode(' ', $service_categories) : '';

                                        // Check if service is online
                                        $is_online = get_post_meta($post_id, 'is_online', true);
                                        if (!$is_online) continue;

                                        // Check if category should exclude master markup
                                        $should_exclude_markup = in_array('addons', $service_categories) ||
                                            in_array('add-ons-nail-art', $service_categories);

                                        $price = get_post_meta($post_id, 'price_min', true);
                                        $currency = get_post_meta($post_id, 'currency', true) ?: 'SGD';
                                        $duration = get_post_meta($post_id, 'duration_minutes', true);
                                        $wear_time = get_post_meta($post_id, 'wear_time', true);
                                        $desc = get_post_meta($post_id, 'description', true);
                                        if (empty($wear_time) && !empty($service->post_content)) {
                                            preg_match('/wear\s+time:?\s+([^\.]+)/i', $service->post_content, $matches);
                                            if (!empty($matches[1])) {
                                                $wear_time = trim($matches[1]);
                                            }
                                        }
                                        $is_addon = get_post_meta($post_id, 'addons', true) === 'yes';
                                        if ($is_addon) continue; // Skip add-ons for now
                                    ?>
                                        <div class="service-item"
                                            data-service-id="<?php echo esc_attr($post_id); ?>"
                                            data-category-slugs="<?php echo esc_attr($category_slugs); ?>"
                                            data-exclude-master-markup="<?php echo $should_exclude_markup ? 'true' : 'false'; ?>">
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
                                                            data-altegio-id="<?php echo esc_attr($altegio_id); ?>"
                                                            data-service-title="<?php echo esc_attr(get_the_title($post_id)); ?>"
                                                            data-service-price="<?php echo esc_attr($price); ?>"
                                                            data-service-currency="<?php echo esc_attr($currency); ?>"
                                                            data-is-addon="false"
                                                            <?php if ($duration): ?>data-service-duration="<?php echo esc_attr($duration); ?>" <?php endif; ?>
                                                            <?php if ($wear_time): ?>data-service-wear-time="<?php echo esc_attr($wear_time); ?>" <?php endif; ?>>
                                                    </div>
                                                </div>
                                                <?php if ($duration): ?>
                                                    <div class="service-duration"><strong>Duration:</strong> <?php echo esc_html($duration); ?> min</div>
                                                <?php endif; ?>
                                                <?php if ($wear_time): ?>
                                                    <div class="service-wear-time"><strong>Wear time:</strong> <?php echo esc_html($wear_time); ?></div>
                                                <?php endif; ?>

                                                <?php if ($desc): ?>
                                                    <div class="service-description"><?php echo esc_html($desc); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <?php
                                            $related_addons = get_field('addons', $post_id);
                                            if (!empty($related_addons)): ?>
                                                <div class="core-related-addons" data-core-id="<?php echo esc_attr($post_id); ?>">
                                                    <?php foreach ($related_addons as $addon):
                                                        $addon_post = is_object($addon) ? $addon : get_post($addon);
                                                        $a_id = $addon_post->ID;

                                                        // Check if addon is online
                                                        $addon_is_online = get_post_meta($a_id, 'is_online', true);
                                                        if (!$addon_is_online) continue; // Skip offline addons

                                                        $a_title = get_the_title($a_id);
                                                        $a_price = get_post_meta($a_id, 'price_min', true);
                                                        $a_currency = get_post_meta($a_id, 'currency', true) ?: 'SGD';
                                                        $a_duration = get_post_meta($a_id, 'duration_minutes', true);
                                                        $a_wear = get_post_meta($a_id, 'wear_time', true);
                                                        $a_desc = get_post_meta($a_id, 'description', true);
                                                        $a_altegio = get_post_meta($a_id, 'altegio_id', true); ?>
                                                        <div class="service-item addon-item"
                                                            data-service-id="<?php echo esc_attr($a_id); ?>"
                                                            data-core-linked="<?php echo esc_attr($post_id); ?>">

                                                            <div class="service-info">
                                                                <div class="service-title">
                                                                    <h4 class="service-name"><?php echo esc_html($a_title); ?></h4>
                                                                    <div class="service-checkbox-wrapper">
                                                                        <div class="service-price"><?php echo esc_html($a_price); ?> <?php echo esc_html($a_currency); ?></div>
                                                                        <input type="checkbox"
                                                                            class="service-checkbox"
                                                                            data-service-id="<?php echo esc_attr($a_id); ?>"
                                                                            data-altegio-id="<?php echo esc_attr($a_altegio); ?>"
                                                                            data-service-title="<?php echo esc_attr($a_title); ?>"
                                                                            data-service-price="<?php echo esc_attr($a_price); ?>"
                                                                            data-service-currency="<?php echo esc_attr($a_currency); ?>"
                                                                            data-is-addon="true"
                                                                            <?php if ($a_duration): ?>data-service-duration="<?php echo esc_attr($a_duration); ?>" <?php endif; ?>
                                                                            <?php if ($a_wear): ?>data-service-wear-time="<?php echo esc_attr($a_wear); ?>" <?php endif; ?>>
                                                                    </div>
                                                                </div>
                                                                <?php if ($a_duration): ?><div class="service-duration"><strong>Duration:</strong> <?php echo esc_html($a_duration); ?> min</div><?php endif; ?>
                                                                <?php if ($a_wear): ?><div class="service-wear-time"><strong>Wear time:</strong> <?php echo esc_html($a_wear); ?></div><?php endif; ?>
                                                                <?php if ($a_desc): ?><div class="service-description"><?php echo esc_html($a_desc); ?></div><?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>




                                    <?php endforeach;
                                    wp_reset_postdata(); ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>


                    <div class="step-actions">
                        <button type="button" class="btn yellow next-btn"> Choose a master </button>
                    </div>
                </div>

                <!-- Step 3: Choose a Master -->
                <div class="booking-step" data-step="master">
                    <div class="step-header">
                        <button type="button" class="booking-back-btn">back</button>
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

                        <?php
                        $master_query = new WP_Query([
                            'post_type' => 'master',
                            'posts_per_page' => -1,
                            'post_status' => 'publish',
                        ]);

                        $levelTitles = [
                            -1 => "Intern",
                            1 => "Sunny Ray",
                            2 => "Sunny Shine",
                            3 => "Sunny Inferno",
                            4 => "Trainer",
                            5 => "Sunny Inferno, Supervisor",
                        ];

                        $markupMap = [
                            -1 => '-50% to price',
                            1 => '+0% to price',
                            2 => '+10% to price',
                            3 => '+20% to price',
                            4 => '+30% to price',
                            5 => '+30% to price',
                        ];

                        $starSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20.8965 18.008L18.6085 15.7L19.2965 15.012L21.6045 17.3L20.8965 18.008ZM17.7005 6.373L17.0125 5.685L19.3005 3.396L20.0085 4.085L17.7005 6.373ZM6.30048 6.393L4.01148 4.084L4.70048 3.395L7.00848 5.684L6.30048 6.393ZM3.08548 18.007L2.39648 17.299L4.68548 15.01L5.39248 15.699L3.08548 18.007ZM6.44048 20L7.91048 13.725L3.00048 9.481L9.47048 8.933L12.0005 3L14.5505 8.933L21.0205 9.481L16.1085 13.725L17.5785 20L12.0005 16.66L6.44048 20Z" fill="#FDC41F"/>
                        </svg>';

                        if ($master_query->have_posts()) :
                            while ($master_query->have_posts()) : $master_query->the_post();

                                $is_bookable = get_field('is_bookable');
                                if (!$is_bookable) {
                                    continue;
                                }
                                $level = (int)get_field('master_level');

                                $starsCount = match (true) {
                                    $level === -1 => 0,
                                    $level === 1 => 1,
                                    $level === 2 => 2,
                                    $level === 3 => 3,
                                    $level === 4, $level === 5 => 4,
                                    default => 0,
                                };
                                $stars = str_repeat($starSvg, $starsCount);

                                $markup = $markupMap[$level] ?? '';
                                $avatar = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                                $specialization = get_field('master_specialization');
                                $levelTitle = $levelTitles[$level] ?? ''; ?>
                                <label class="staff-item level-<?php echo esc_attr($level); ?>"
                                    data-staff-id="<?php echo esc_attr(get_field('altegio_id')); ?>"
                                    data-staff-level="<?php echo esc_attr($level); ?>"
                                    data-staff-specialization="<?php echo esc_attr($specialization); ?>">
                                    <input type="radio" name="staff">
                                    <div class="staff-radio-content">
                                        <div class="staff-avatar">
                                            <?php if ($avatar) : ?>
                                                <img src="<?php echo esc_url($avatar); ?>" alt="<?php the_title_attribute(); ?>">
                                            <?php endif; ?>
                                        </div>
                                        <div class="staff-info">
                                            <h4 class="staff-name"><?php the_title(); ?></h4>
                                            <div class="staff-specialization">
                                                <div class="staff-stars">
                                                    <?php echo $stars; ?>
                                                </div>
                                                <?php if ($levelTitle): ?>
                                                    <span class="studio-name">(<?php echo esc_html($levelTitle); ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($markup): ?>
                                            <div class="staff-price-modifier"><?php echo esc_html($markup); ?></div>
                                        <?php endif; ?>
                                        <span class="radio-indicator"></span>
                                    </div>
                                </label>
                            <?php
                            endwhile;
                            wp_reset_postdata();
                        else :
                            ?>
                            <p class="no-items-message">No specialists available at the moment.</p>
                        <?php endif; ?>


                    </div>

                    <div class="step-actions">
                        <button type="button" class="btn yellow next-btn">Select date and time</button>
                    </div>
                </div>

                <!-- Step 4: Select Date and Time -->
                <div class="booking-step" data-step="datetime">
                    <div class="step-header">
                        <button type="button" class="booking-back-btn">back</button>
                        <h2 class="booking-title">Select date and time</h2>
                    </div>
                    <div class="datetime-container">
                        <div class="date-selector">
                            <div class="month-header">
                                <span class="current-month">May 2025</span>
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
                            <div class="time-sections">
                                <div class="time-slots"></div>
                            </div>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="btn yellow next-btn">Book an appointment</button>
                    </div>
                </div>


                <!-- Step 5: Booking Details -->
                <div class="booking-step contact" data-step="contact">
                    <div class="step-header">
                        <button type="button" class="booking-back-btn">back</button>
                        <h2 class="booking-title">Booking details</h2>
                    </div>

                    <div class="booking-details-content">

                        <div class="booking-summary-box">
                            <div class="summary-master-date">
                                <div class="summary-master">
                                    <div class="master-info">
                                        <img class="avatar" src="" alt="Master photo" data-no-lazy="1" loading="eager" decoding="async" />

                                        <div class="master-meta">
                                            <div class="name-stars">
                                                <span class="name"></span>
                                                <div class="stars-container">
                                                    <span class="stars"> </span>
                                                    <span class="stars-name"></span>
                                                </div>

                                            </div>

                                        </div>
                                    </div>
                                    <button class="edit-master-btn" aria-label="Edit master" data-edit-step="master">
                                        <svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M15 6.5L18 9.5M13 20.5H21M5 16.5L4 20.5L8 19.5L19.586 7.914C19.9609 7.53895 20.1716 7.03033 20.1716 6.5C20.1716 5.96967 19.9609 5.46106 19.586 5.086L19.414 4.914C19.0389 4.53906 18.5303 4.32843 18 4.32843C17.4697 4.32843 16.9611 4.53906 16.586 4.914L5 16.5Z" stroke="#302F34" stroke-opacity="0.5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>

                                    </button>
                                </div>

                                <div class="date-time-wrapper">
                                    <div class="master-info">
                                        <div class="date-time-icon">
                                            <svg width="22" height="23" viewBox="0 0 22 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M20.2812 4.625H16.5V2.21875H15.125V4.625H6.875V2.21875H5.5V4.625H1.71875C1.44535 4.62534 1.18325 4.7341 0.989923 4.92742C0.7966 5.12075 0.687841 5.38285 0.6875 5.65625V20.0938C0.687841 20.3671 0.7966 20.6293 0.989923 20.8226C1.18325 21.0159 1.44535 21.1247 1.71875 21.125H20.2812C20.5546 21.1247 20.8168 21.0159 21.0101 20.8226C21.2034 20.6293 21.3122 20.3671 21.3125 20.0938V5.65625C21.3122 5.38285 21.2034 5.12075 21.0101 4.92742C20.8168 4.7341 20.5546 4.62534 20.2812 4.625ZM19.9375 19.75H2.0625V6H5.5V7.71875H6.875V6H15.125V7.71875H16.5V6H19.9375V19.75Z" fill="#302F34" />
                                                <path d="M4.8125 10.125H6.1875V11.5H4.8125V10.125ZM8.59375 10.125H9.96875V11.5H8.59375V10.125ZM12.0312 10.125H13.4062V11.5H12.0312V10.125ZM15.8125 10.125H17.1875V11.5H15.8125V10.125ZM4.8125 13.2188H6.1875V14.5938H4.8125V13.2188ZM8.59375 13.2188H9.96875V14.5938H8.59375V13.2188ZM12.0312 13.2188H13.4062V14.5938H12.0312V13.2188ZM15.8125 13.2188H17.1875V14.5938H15.8125V13.2188ZM4.8125 16.3125H6.1875V17.6875H4.8125V16.3125ZM8.59375 16.3125H9.96875V17.6875H8.59375V16.3125ZM12.0312 16.3125H13.4062V17.6875H12.0312V16.3125ZM15.8125 16.3125H17.1875V17.6875H15.8125V16.3125Z" fill="#302F34" />
                                            </svg>

                                        </div>
                                        <div class="booking-date-time">
                                            <div class="calendar-date"></div>
                                            <div class="calendar-time"></div>
                                        </div>
                                    </div>
                                    <button class="edit-datetime-btn" aria-label="Edit date and time" data-edit-step="datetime">
                                        <svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M15 6.5L18 9.5M13 20.5H21M5 16.5L4 20.5L8 19.5L19.586 7.914C19.9609 7.53895 20.1716 7.03033 20.1716 6.5C20.1716 5.96967 19.9609 5.46106 19.586 5.086L19.414 4.914C19.0389 4.53906 18.5303 4.32843 18 4.32843C17.4697 4.32843 16.9611 4.53906 16.586 4.914L5 16.5Z" stroke="#302F34" stroke-opacity="0.5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>

                                    </button>
                                </div>
                            </div>

                            <div class="summary-services-list">


                            </div>
                            <div class="summary-services-list summary-addons" style="display: none;">
                                <!-- Add-ons will be populated dynamically via JavaScript -->
                            </div>


                            <div class="summary-total-group">
                                <div class="summary-coupon-group summary-item master-category">
                                    <div class="summary-item "><span>Master category (<span class="percent">0</span>%) </span> <span class="master-bonus">0 SGD</span></div>
                                    <span class="master-category-note">Not applicable for Add-ons and Nail Art services.</span>

                                </div>

                                <div class="summary-item summary-coupon-group">
                                    <label for="coupon-code" class="coupon-label">Coupon <span class="coupon-discount"></span></label>
                                    <p class="coupon-desc">Do you have a coupon? Enter it here and get a discount on services.</p>
                                    <div class="coupon-input-group">
                                        <input type="text" id="coupon-code" class="coupon-code-input" placeholder="Coupon code">
                                        <button type="button" class=" yellow apply-coupon-btn">Apply</button>
                                    </div>
                                    <div class="coupon-feedback" style="display: none;"></div>
                                </div>

                                <div class="summary-item total"><span>Total</span> <span class="summary-total-amount">0.00 SGD</span></div>
                                <?php
                                $price_notice = get_field('booking_price_note', 'option');
                                if ($price_notice) : ?>
                                    <div class="summary-item tax"><?= esc_html($price_notice); ?></div>
                                <?php endif; ?>
                            </div>
                            <form id="booking-form" class="contact-form" novalidate>
                                <h3>Personal Information</h3>

                                <div class="form-group">
                                    <input type="text" id="client-name" name="client[name]" placeholder="Name*" required />
                                </div>

                                <div class="form-group">
                                    <input type="email" id="client-email" name="client[email]" placeholder="Email*" required />
                                </div>

                                <div class="form-group phone-group">
                                    <div class="phone-input-wrapper">
                                        <select id="client-country" name="client[country]" class="country-select-inline">
                                            <option value="+65" selected>Singapore +65</option>
                                            <option value="+93">Afghanistan +93</option>
                                            <option value="+355">Albania +355</option>
                                            <option value="+213">Algeria +213</option>
                                            <option value="+1684">American Samoa +1684</option>
                                            <option value="+376">Andorra +376</option>
                                            <option value="+244">Angola +244</option>
                                            <option value="+1264">Anguilla +1264</option>
                                            <option value="+54">Argentina +54</option>
                                            <option value="+374">Armenia +374</option>
                                            <option value="+297">Aruba +297</option>
                                            <option value="+61">Australia +61</option>
                                            <option value="+43">Austria +43</option>
                                            <option value="+994">Azerbaijan +994</option>
                                            <option value="+1242">Bahamas +1242</option>
                                            <option value="+973">Bahrain +973</option>
                                            <option value="+880">Bangladesh +880</option>
                                            <option value="+1246">Barbados +1246</option>
                                            <option value="+375">Belarus +375</option>
                                            <option value="+32">Belgium +32</option>
                                            <option value="+501">Belize +501</option>
                                            <option value="+229">Benin +229</option>
                                            <option value="+1441">Bermuda +1441</option>
                                            <option value="+975">Bhutan +975</option>
                                            <option value="+591">Bolivia +591</option>
                                            <option value="+387">Bosnia and Herzegovina +387</option>
                                            <option value="+267">Botswana +267</option>
                                            <option value="+55">Brazil +55</option>

                                            <option value="+673">Brunei +673</option>
                                            <option value="+359">Bulgaria +359</option>
                                            <option value="+226">Burkina Faso +226</option>
                                            <option value="+257">Burundi +257</option>
                                            <option value="+855">Cambodia +855</option>
                                            <option value="+237">Cameroon +237</option>
                                            <option value="+1">Canada +1</option>
                                            <option value="+238">Cape Verde +238</option>
                                            <option value="+1345">Cayman Islands +1345</option>
                                            <option value="+236">Central African Republic +236</option>
                                            <option value="+235">Chad +235</option>
                                            <option value="+56">Chile +56</option>
                                            <option value="+86">China +86</option>
                                            <option value="+61">Christmas Island +61</option>
                                            <option value="+61">Cocos Islands +61</option>
                                            <option value="+57">Colombia +57</option>
                                            <option value="+269">Comoros +269</option>
                                            <option value="+682">Cook Islands +682</option>
                                            <option value="+506">Costa Rica +506</option>
                                            <option value="+385">Croatia +385</option>
                                            <option value="+53">Cuba +53</option>
                                            <option value="+599">Curacao +599</option>
                                            <option value="+357">Cyprus +357</option>
                                            <option value="+420">Czech Republic +420</option>
                                            <option value="+243">Congo (DRC) +243</option>
                                            <option value="+45">Denmark +45</option>
                                            <option value="+253">Djibouti +253</option>
                                            <option value="+1767">Dominica +1767</option>
                                            <option value="+1809">Dominican Republic +1809</option>
                                            <option value="+670">East Timor +670</option>
                                            <option value="+593">Ecuador +593</option>
                                            <option value="+20">Egypt +20</option>
                                            <option value="+503">El Salvador +503</option>
                                            <option value="+240">Equatorial Guinea +240</option>
                                            <option value="+291">Eritrea +291</option>
                                            <option value="+372">Estonia +372</option>
                                            <option value="+268">Eswatini +268</option>
                                            <option value="+251">Ethiopia +251</option>
                                            <option value="+298">Faroe Islands +298</option>
                                            <option value="+679">Fiji +679</option>
                                            <option value="+358">Finland +358</option>
                                            <option value="+33">France +33</option>
                                            <option value="+594">French Guiana +594</option>
                                            <option value="+689">French Polynesia +689</option>
                                            <option value="+241">Gabon +241</option>
                                            <option value="+220">Gambia +220</option>
                                            <option value="+995">Georgia +995</option>
                                            <option value="+49">Germany +49</option>
                                            <option value="+233">Ghana +233</option>
                                            <option value="+350">Gibraltar +350</option>
                                            <option value="+30">Greece +30</option>
                                            <option value="+299">Greenland +299</option>
                                            <option value="+1473">Grenada +1473</option>
                                            <option value="+590">Guadeloupe +590</option>
                                            <option value="+1671">Guam +1671</option>
                                            <option value="+502">Guatemala +502</option>
                                            <option value="+44">Guernsey +44</option>
                                            <option value="+224">Guinea +224</option>
                                            <option value="+245">Guinea-Bissau +245</option>
                                            <option value="+592">Guyana +592</option>
                                            <option value="+509">Haiti +509</option>
                                            <option value="+504">Honduras +504</option>
                                            <option value="+852">Hong Kong +852</option>
                                            <option value="+36">Hungary +36</option>
                                            <option value="+354">Iceland +354</option>
                                            <option value="+91">India +91</option>
                                            <option value="+62">Indonesia +62</option>
                                            <option value="+98">Iran +98</option>
                                            <option value="+964">Iraq +964</option>
                                            <option value="+353">Ireland +353</option>
                                            <option value="+44">Isle of Man +44</option>
                                            <option value="+972">Israel +972</option>
                                            <option value="+39">Italy +39</option>
                                            <option value="+225">Ivory Coast +225</option>
                                            <option value="+1876">Jamaica +1876</option>
                                            <option value="+81">Japan +81</option>
                                            <option value="+44">Jersey +44</option>
                                            <option value="+962">Jordan +962</option>
                                            <option value="+7">Kazakhstan +7</option>
                                            <option value="+254">Kenya +254</option>
                                            <option value="+686">Kiribati +686</option>
                                            <option value="+383">Kosovo +383</option>
                                            <option value="+965">Kuwait +965</option>
                                            <option value="+996">Kyrgyzstan +996</option>
                                            <option value="+856">Laos +856</option>
                                            <option value="+371">Latvia +371</option>
                                            <option value="+961">Lebanon +961</option>
                                            <option value="+266">Lesotho +266</option>
                                            <option value="+231">Liberia +231</option>
                                            <option value="+218">Libya +218</option>
                                            <option value="+423">Liechtenstein +423</option>
                                            <option value="+370">Lithuania +370</option>
                                            <option value="+352">Luxembourg +352</option>
                                            <option value="+853">Macau +853</option>
                                            <option value="+389">Macedonia +389</option>
                                            <option value="+261">Madagascar +261</option>
                                            <option value="+265">Malawi +265</option>
                                            <option value="+60">Malaysia +60</option>
                                            <option value="+960">Maldives +960</option>
                                            <option value="+223">Mali +223</option>
                                            <option value="+356">Malta +356</option>
                                            <option value="+692">Marshall Islands +692</option>
                                            <option value="+596">Martinique +596</option>
                                            <option value="+222">Mauritania +222</option>
                                            <option value="+230">Mauritius +230</option>
                                            <option value="+262">Mayotte +262</option>
                                            <option value="+52">Mexico +52</option>
                                            <option value="+691">Micronesia +691</option>
                                            <option value="+373">Moldova +373</option>
                                            <option value="+377">Monaco +377</option>
                                            <option value="+976">Mongolia +976</option>
                                            <option value="+382">Montenegro +382</option>
                                            <option value="+1664">Montserrat +1664</option>
                                            <option value="+212">Morocco +212</option>
                                            <option value="+258">Mozambique +258</option>
                                            <option value="+95">Myanmar +95</option>
                                            <option value="+264">Namibia +264</option>
                                            <option value="+674">Nauru +674</option>
                                            <option value="+977">Nepal +977</option>
                                            <option value="+31">Netherlands +31</option>
                                            <option value="+687">New Caledonia +687</option>
                                            <option value="+64">New Zealand +64</option>
                                            <option value="+505">Nicaragua +505</option>
                                            <option value="+227">Niger +227</option>
                                            <option value="+234">Nigeria +234</option>
                                            <option value="+683">Niue +683</option>
                                            <option value="+850">North Korea +850</option>
                                            <option value="+1670">Northern Mariana Islands +1670</option>
                                            <option value="+47">Norway +47</option>
                                            <option value="+968">Oman +968</option>
                                            <option value="+92">Pakistan +92</option>
                                            <option value="+680">Palau +680</option>
                                            <option value="+970">Palestine +970</option>
                                            <option value="+507">Panama +507</option>
                                            <option value="+675">Papua New Guinea +675</option>
                                            <option value="+595">Paraguay +595</option>
                                            <option value="+51">Peru +51</option>
                                            <option value="+63">Philippines +63</option>
                                            <option value="+48">Poland +48</option>
                                            <option value="+351">Portugal +351</option>
                                            <option value="+1787">Puerto Rico +1787</option>
                                            <option value="+974">Qatar +974</option>
                                            <option value="+242">Republic of the Congo +242</option>
                                            <option value="+262">Reunion +262</option>
                                            <option value="+40">Romania +40</option>
                                            <option value="+7">Russia +7</option>
                                            <option value="+250">Rwanda +250</option>
                                            <option value="+590">Saint Barthelemy +590</option>
                                            <option value="+1869">Saint Kitts and Nevis +1869</option>
                                            <option value="+1758">Saint Lucia +1758</option>
                                            <option value="+590">Saint Martin +590</option>
                                            <option value="+1784">St. Vincent & Grenadines +1784</option>
                                            <option value="+685">Samoa +685</option>
                                            <option value="+378">San Marino +378</option>
                                            <option value="+239">Sao Tome and Principe +239</option>
                                            <option value="+966">Saudi Arabia +966</option>
                                            <option value="+221">Senegal +221</option>
                                            <option value="+381">Serbia +381</option>
                                            <option value="+248">Seychelles +248</option>
                                            <option value="+232">Sierra Leone +232</option>
                                            <option value="+1721">Sint Maarten +1721</option>
                                            <option value="+421">Slovakia +421</option>
                                            <option value="+386">Slovenia +386</option>
                                            <option value="+677">Solomon Islands +677</option>
                                            <option value="+252">Somalia +252</option>
                                            <option value="+27">South Africa +27</option>
                                            <option value="+82">South Korea +82</option>
                                            <option value="+211">South Sudan +211</option>
                                            <option value="+34">Spain +34</option>
                                            <option value="+94">Sri Lanka +94</option>
                                            <option value="+249">Sudan +249</option>
                                            <option value="+597">Suriname +597</option>
                                            <option value="+47">Svalbard and Jan Mayen +47</option>
                                            <option value="+46">Sweden +46</option>
                                            <option value="+41">Switzerland +41</option>
                                            <option value="+963">Syria +963</option>
                                            <option value="+886">Taiwan +886</option>
                                            <option value="+992">Tajikistan +992</option>
                                            <option value="+255">Tanzania +255</option>
                                            <option value="+66">Thailand +66</option>
                                            <option value="+228">Togo +228</option>
                                            <option value="+676">Tonga +676</option>
                                            <option value="+1868">Trinidad and Tobago +1868</option>
                                            <option value="+216">Tunisia +216</option>
                                            <option value="+90">Turkey +90</option>
                                            <option value="+993">Turkmenistan +993</option>
                                            <option value="+1649">Turks and Caicos Islands +1649</option>
                                            <option value="+688">Tuvalu +688</option>
                                            <option value="+1340">U.S. Virgin Islands +1340</option>
                                            <option value="+256">Uganda +256</option>
                                            <option value="+380">Ukraine +380</option>
                                            <option value="+971">United Arab Emirates +971</option>
                                            <option value="+44">United Kingdom +44</option>
                                            <option value="+1">United States +1</option>
                                            <option value="+598">Uruguay +598</option>
                                            <option value="+998">Uzbekistan +998</option>
                                            <option value="+678">Vanuatu +678</option>
                                            <option value="+39">Vatican +39</option>
                                            <option value="+58">Venezuela +58</option>
                                            <option value="+84">Vietnam +84</option>
                                            <option value="+212">Western Sahara +212</option>
                                            <option value="+967">Yemen +967</option>
                                            <option value="+260">Zambia +260</option>
                                            <option value="+263">Zimbabwe +263</option>
                                        </select>
                                        <input type="tel" id="client-phone" name="client[phone]" placeholder="Phone number*" value="" required />
                                    </div>
                                </div>

                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const countrySelect = document.getElementById('client-country');
                                        const phoneInput = document.getElementById('client-phone');

                                        if (countrySelect && phoneInput) {
                                            // Set initial value
                                            phoneInput.value = countrySelect.value + ' ';

                                            countrySelect.addEventListener('change', function() {
                                                const selectedCode = this.value;
                                                const currentValue = phoneInput.value;
                                                const phoneNumber = currentValue.replace(/^\+\d+\s*/, '').trim();
                                                phoneInput.value = selectedCode + ' ' + phoneNumber;
                                                phoneInput.focus();
                                            });

                                            phoneInput.addEventListener('focus', function() {
                                                const selectedCode = countrySelect.value;
                                                if (!this.value || this.value.trim() === '') {
                                                    this.value = selectedCode + ' ';
                                                }
                                            });

                                            phoneInput.addEventListener('input', function() {
                                                const selectedCode = countrySelect.value;
                                                const currentValue = this.value;

                                                if (!currentValue.startsWith(selectedCode)) {
                                                    const phoneNumber = currentValue.replace(/^\+\d+\s*/, '').trim();
                                                    this.value = selectedCode + ' ' + phoneNumber;
                                                }
                                            });
                                        }
                                    });
                                </script>

                                <div class="form-group">
                                    <textarea id="client-comment" name="client_comment" placeholder="Comment"></textarea>
                                </div>

                                <div class="form-group checkbox">
                                    <label for="privacy-policy">
                                        <input type="checkbox" id="privacy-policy" required />
                                        <span>
                                            I confirm that I have read and accepted the
                                            <a href="<?= esc_url(get_privacy_policy_url()); ?>" target="_blank" rel="noopener noreferrer">
                                                Privacy Policy
                                            </a>.
                                        </span>

                                    </label>
                                    <div class="input-error" data-for="privacy-policy"></div>
                                </div>
                                <div class="form-errors global-form-error" style="display:none;">

                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect width="24" height="24" rx="6" fill="white" />
                                        <path d="M12.0002 5.33325C15.6822 5.33325 18.6668 8.31859 18.6668 11.9999C18.6668 15.6813 15.6822 18.6666 12.0002 18.6666C8.31816 18.6666 5.3335 15.6813 5.3335 11.9999C5.3335 8.31859 8.31816 5.33325 12.0002 5.33325ZM12.0002 6.44459C8.93683 6.44459 6.44483 8.93659 6.44483 11.9999C6.44483 15.0633 8.93683 17.5553 12.0002 17.5553C15.0635 17.5553 17.5555 15.0633 17.5555 11.9999C17.5555 8.93659 15.0635 6.44459 12.0002 6.44459ZM11.9995 13.6679C12.1761 13.6679 12.3455 13.7381 12.4704 13.863C12.5953 13.9879 12.6655 14.1573 12.6655 14.3339C12.6655 14.5106 12.5953 14.68 12.4704 14.8049C12.3455 14.9298 12.1761 14.9999 11.9995 14.9999C11.8229 14.9999 11.6535 14.9298 11.5286 14.8049C11.4037 14.68 11.3335 14.5106 11.3335 14.3339C11.3335 14.1573 11.4037 13.9879 11.5286 13.863C11.6535 13.7381 11.8229 13.6679 11.9995 13.6679ZM11.9962 8.66659C12.1171 8.66643 12.234 8.71011 12.3252 8.78954C12.4164 8.86898 12.4757 8.97877 12.4922 9.09859L12.4968 9.16592L12.4995 12.1673C12.4996 12.294 12.4516 12.4161 12.3652 12.5087C12.2788 12.6014 12.1603 12.6579 12.0339 12.6666C11.9075 12.6753 11.7824 12.6357 11.6841 12.5557C11.5857 12.4758 11.5214 12.3615 11.5042 12.2359L11.4995 12.1679L11.4968 9.16725C11.4967 9.10154 11.5096 9.03645 11.5347 8.97571C11.5598 8.91497 11.5966 8.85977 11.643 8.81327C11.6895 8.76677 11.7446 8.72988 11.8053 8.70471C11.866 8.67954 11.9304 8.66659 11.9962 8.66659Z" fill="#DC3232" />
                                    </svg>

                                    <span>One or more fields have an error. Please check and try again.</span>

                                </div>
                            </form>


                        </div>



                        <div class="step-actions">
                            <button type="button" class="btn yellow next-btn  confirm-booking-btn">Book an appointment </button>
                        </div>
                    </div>

                </div>


                <!-- Step 6: Confirmation -->
                <div class="booking-step confirm" data-step="confirm">

                    <div class="confirmation-body">
                        <div class="confirmation-success">
                            <div class="confirmation-success-icon">
                                <svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M26.4771 1.81641L12.2729 20.5436L3.875 12.1518L0 16.0268L12.9146 28.9414L31 5.69141L26.4771 1.81641Z" fill="#302F34" />
                                </svg>
                            </div>
                            <p class="confirmation-message">You have booked an appointment to Sunny Nails Studio!</p>
                        </div>



                        <div class="booking-summary-box">
                            <h3 class="summary-title">Booking details</h3>
                            <div class="summary-master-date">
                                <div class="summary-master">
                                    <div class="master-info">
                                        <img class="avatar" src="" alt="Master photo" data-no-lazy="1" loading="eager" decoding="async" />

                                        <div class="master-meta">
                                            <div class="name-stars">
                                                <span class="name"></span>
                                                <div class="stars-container">
                                                    <span class="stars"> </span>
                                                    <span class="stars-name"></span>
                                                </div>

                                            </div>

                                        </div>
                                    </div>

                                </div>

                                <div class="date-time-wrapper">
                                    <div class="master-info">
                                        <div class="date-time-icon">
                                            <svg width="22" height="23" viewBox="0 0 22 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M20.2812 4.625H16.5V2.21875H15.125V4.625H6.875V2.21875H5.5V4.625H1.71875C1.44535 4.62534 1.18325 4.7341 0.989923 4.92742C0.7966 5.12075 0.687841 5.38285 0.6875 5.65625V20.0938C0.687841 20.3671 0.7966 20.6293 0.989923 20.8226C1.18325 21.0159 1.44535 21.1247 1.71875 21.125H20.2812C20.5546 21.1247 20.8168 21.0159 21.0101 20.8226C21.2034 20.6293 21.3122 20.3671 21.3125 20.0938V5.65625C21.3122 5.38285 21.2034 5.12075 21.0101 4.92742C20.8168 4.7341 20.5546 4.62534 20.2812 4.625ZM19.9375 19.75H2.0625V6H5.5V7.71875H6.875V6H15.125V7.71875H16.5V6H19.9375V19.75Z" fill="#302F34" />
                                                <path d="M4.8125 10.125H6.1875V11.5H4.8125V10.125ZM8.59375 10.125H9.96875V11.5H8.59375V10.125ZM12.0312 10.125H13.4062V11.5H12.0312V10.125ZM15.8125 10.125H17.1875V11.5H15.8125V10.125ZM4.8125 13.2188H6.1875V14.5938H4.8125V13.2188ZM8.59375 13.2188H9.96875V14.5938H8.59375V13.2188ZM12.0312 13.2188H13.4062V14.5938H12.0312V13.2188ZM15.8125 13.2188H17.1875V14.5938H15.8125V13.2188ZM4.8125 16.3125H6.1875V17.6875H4.8125V16.3125ZM8.59375 16.3125H9.96875V17.6875H8.59375V16.3125ZM12.0312 16.3125H13.4062V17.6875H12.0312V16.3125ZM15.8125 16.3125H17.1875V17.6875H15.8125V16.3125Z" fill="#302F34" />
                                            </svg>

                                        </div>
                                        <div class="booking-date-time">
                                            <div class="calendar-date"></div>
                                            <div class="calendar-time"></div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="summary-services-list">


                            </div>

                            <div class="summary-services-list summary-addons" style="display: none;">
                                <!-- Add-ons will be populated dynamically via JavaScript -->
                            </div>



                            <div class="summary-total-group">
                                <div class="summary-coupon-group summary-item master-category">
                                    <div class="summary-item "><span>Master category (<span class="percent">0</span>%) </span> <span class="master-bonus">0 SGD</span></div>
                                    <span class="master-category-note">Not applicable for Add-ons and Nail Art services.</span>

                                </div>
                                <div class="summary-item summary-coupon-group">
                                    <label for="coupon-code" class="coupon-label">Coupon <span class="coupon-discount"></span></label>
                                    <p class="coupon-desc">Do you have a coupon? Enter it here and get a discount on services.</p>
                                </div>

                                <div class="summary-item total"><span>Total</span> <span class="summary-total-amount">0.00 SGD</span></div>
                                <?php
                                $price_notice = get_field('booking_price_note', 'option');
                                if ($price_notice) : ?>
                                    <div class="summary-item tax"><?= esc_html($price_notice); ?></div>
                                <?php endif; ?>

                            </div>


                        </div>
                        <div class="step-actions">
                            <button type="button" class="btn yellow new-booking-btn">Make another booking</button>
                            <!-- <button type="button" class="btn  outline edit-booking-btn">Edit Booking</button> -->
                            <button type="button" class="btn  outline cancel-booking-btn">Back to site</button>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>
</div>