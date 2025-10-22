<?php

/**
 * First Visit Popup Component with ACF fields
 * Shows user journey selection on first visit to any page
 */

// Always show popup HTML - JavaScript will handle first visit detection
$show_popup = true;

// Get ACF fields for popup content from partners page
$partners_page = get_page_by_path('partners');
$partners_page_id = $partners_page ? $partners_page->ID : get_option('page_on_front');

$popup_title = get_field('partners_pop-up_title', $partners_page_id) ?: 'Choose Your Journey';
$popup_image = get_field('partners_pop-up_image', $partners_page_id);
$popup_cards = get_field('partners_pop-up_card', $partners_page_id);
?>

<?php if ($show_popup): ?>
    <?php
    // Предзагрузка изображений для ускорения
    if ($popup_image) {
      echo '<link rel="preload" as="image" href="' . esc_url($popup_image['url']) . '">';
    }
    if ($popup_cards) {
      foreach ($popup_cards as $card) {
        if (isset($card['icon']['url'])) {
          echo '<link rel="preload" as="image" href="' . esc_url($card['icon']['url']) . '">';
        }
      }
    }
    ?>
    <div id="first-visit-popup" class="first-visit-popup">
        <div class="first-visit-popup__overlay">
            <div class="first-visit-popup__container">
                <button type="button" class="first-visit-popup__close" id="firstVisitPopupClose">
                    <svg xmlns="http://www.w3.org/2000/svg" width="29" height="29" viewBox="0 0 29 29" fill="none">
                        <path d="M13.2197 14.5L5.70327 6.98538C5.61901 6.90112 5.55217 6.80109 5.50657 6.691C5.46097 6.5809 5.4375 6.46291 5.4375 6.34375C5.4375 6.22459 5.46097 6.1066 5.50657 5.99651C5.55217 5.88642 5.61901 5.78638 5.70327 5.70213C5.78753 5.61787 5.88756 5.55103 5.99765 5.50543C6.10774 5.45983 6.22573 5.43636 6.34489 5.43636C6.46406 5.43636 6.58205 5.45983 6.69214 5.50543C6.80223 5.55103 6.90226 5.61787 6.98652 5.70213L14.5011 13.2186L22.0158 5.70213C22.1859 5.53196 22.4167 5.43636 22.6574 5.43636C22.8981 5.43636 23.1288 5.53196 23.299 5.70213C23.4692 5.87229 23.5648 6.10309 23.5648 6.34375C23.5648 6.58441 23.4692 6.81521 23.299 6.98538L15.7826 14.5L23.299 22.0146C23.4692 22.1848 23.5648 22.4156 23.5648 22.6562C23.5648 22.8969 23.4692 23.1277 23.299 23.2979C23.1288 23.468 22.8981 23.5636 22.6574 23.5636C22.4167 23.5636 22.1859 23.468 22.0158 23.2979L14.5011 15.7814L6.98652 23.2979C6.81635 23.468 6.58555 23.5636 6.34489 23.5636C6.10424 23.5636 5.87344 23.468 5.70327 23.2979C5.5331 23.1277 5.4375 22.8969 5.4375 22.6562C5.4375 22.4156 5.5331 22.1848 5.70327 22.0146L13.2197 14.5Z" fill="#302F34" />
                    </svg>
                </button>
                <div class="first-visit-popup__content">
                    <div class="popup-header">
                        <?php if ($popup_title): ?>
                            <div class="popup-header-title"><?php echo $popup_title; ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if ($popup_image): ?>
                        <div class="popup-image">
                            <img src="<?php echo esc_url($popup_image['url']); ?>"
                                alt="<?php echo esc_attr($popup_image['alt']); ?>"
                                loading="eager"
                                decoding="async"
                                fetchpriority="high" />
                        </div>
                    <?php endif; ?>

                    <?php if ($popup_cards): ?>
                        <div class="journey-options">
                            <?php foreach ($popup_cards as $index => $card): ?>
                                <div class="journey-option <?php echo $index === 0
                                  ? 'business-owner'
                                  : 'customer'; ?>">
                                    <?php if ($card['icon']): ?>
                                        <div class="option-icon">
                                            <img src="<?php echo esc_url($card['icon']['url']); ?>"
                                                alt="<?php echo esc_attr($card['icon']['alt']); ?>"
                                                loading="eager"
                                                decoding="async" />
                                        </div>
                                    <?php endif; ?>

                                    <div class="option-content">
                                        <?php if ($card['title']): ?>
                                            <h3><?php echo $card['title']; ?></h3>
                                        <?php endif; ?>
                                        <?php if ($card['description']): ?>
                                            <p><?php echo $card['description']; ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($card['link']): ?>
                                        <?php
                                        $link_url = '';
                                        $link_target = '';
                                        $link_title = '';

                                        if (is_array($card['link'])) {
                                          $link_url = $card['link']['url'] ?? '';
                                          $link_target = '_blank';
                                          $link_title =
                                            $card['link']['title'] ??
                                            ($index === 0 ? 'Apply as Partner' : 'See Benefits');
                                        } else {
                                          $link_url = $card['link'];
                                          $link_target = '_blank';
                                          $link_title =
                                            $index === 0 ? 'Apply as Partner' : 'See Benefits';
                                        }
                                        ?>
                                        <?php if ($link_url): ?>
                                            <a href="<?php echo esc_url($link_url); ?>"
                                                class="option-button btn yellow popup-link-close"
                                                target="<?php echo esc_attr($link_target); ?>"
                                                data-option="<?php echo $index === 0
                                                  ? 'business'
                                                  : 'customer'; ?>">
                                                <?php echo esc_html($link_title); ?>

                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>
