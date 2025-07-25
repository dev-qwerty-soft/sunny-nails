<?php foreach ($service_categories_popup as $i => $category_popup): ?>
  <?php if ($category_popup && is_object($category_popup) && isset($category_popup->term_id)): ?>
    <?php $services = get_services_by_category($category_popup->term_id); ?>
    <div class="category-services" data-category-id="<?php echo esc_attr(
                                                        $category_popup->term_id,
                                                      ); ?>" style="<?php echo $i === 0 ? '' : 'display:none'; ?>">
      <?php
      foreach ($services as $service):

        setup_postdata($service);
        $post_id = $service->ID;

        // Get service categories
        $service_categories = wp_get_post_terms($post_id, 'service_category', [
          'fields' => 'slugs',
        ]);
        $category_slugs = is_array($service_categories)
          ? implode(' ', $service_categories)
          : '';

        // Check if service is online
        $is_online = get_post_meta($post_id, 'is_online', true);
        if (!$is_online) {
          continue;
        }

        // Check if category should exclude master markup
        $should_exclude_markup =
          in_array('addons', $service_categories) ||
          in_array('add-ons-nail-art', $service_categories);

        $price = get_post_meta($post_id, 'price_min', true);
        $currency = get_post_meta($post_id, 'currency', true) ?: 'SGD';
        $duration = get_post_meta($post_id, 'duration_minutes', true);
        $wear_time = get_post_meta($post_id, 'wear_time', true);
        $desc = $service->post_content;
        if (empty($wear_time) && !empty($service->post_content)) {
          preg_match('/wear\s+time:?\s+([^\.]+)/i', $service->post_content, $matches);
          if (!empty($matches[1])) {
            $wear_time = trim($matches[1]);
          }
        }
        $is_addon = get_post_meta($post_id, 'addons', true) === 'yes';
        if ($is_addon) {
          continue;
        }

        // Skip add-ons for now
      ?>
        <div class="service-item"
          data-service-id="<?php echo esc_attr($post_id); ?>"
          data-category-slugs="<?php echo esc_attr($category_slugs); ?>"
          data-exclude-master-markup="<?php echo $should_exclude_markup
                                        ? 'true'
                                        : 'false'; ?>">
          <div class="service-info">
            <div class="service-title">
              <h4 class="service-name"><?php echo esc_html(
                                          get_the_title($post_id),
                                        ); ?></h4>
              <div class="service-checkbox-wrapper">
                <div class="service-price">
                  <?php echo esc_html($price); ?> <?php echo esc_html(
                                                    $currency,
                                                  ); ?>
                </div>
                <input type="checkbox"
                  class="service-checkbox"
                  data-service-id="<?php echo esc_attr($post_id); ?>"
                  data-altegio-id="<?php echo esc_attr($altegio_id); ?>"
                  data-service-title="<?php echo esc_attr(
                                        get_the_title($post_id),
                                      ); ?>"
                  data-service-price="<?php echo esc_attr($price); ?>"
                  data-service-currency="<?php echo esc_attr($currency); ?>"
                  data-is-addon="false"
                  <?php if (
                    $duration
                  ): ?>data-service-duration="<?php echo esc_attr(
                                                                  $duration,
                                                                ); ?>" <?php endif; ?>
                  <?php if (
                    $wear_time
                  ): ?>data-service-wear-time="<?php echo esc_attr(
                                                                    $wear_time,
                                                                  ); ?>" <?php endif; ?>>
              </div>
            </div>
            <?php if ($duration): ?>
              <div class="service-duration"><strong>Duration:</strong> <?php echo esc_html(
                                                                          $duration,
                                                                        ); ?> min</div>
            <?php endif; ?>
            <?php if ($wear_time): ?>
              <div class="service-wear-time"><strong>Wear time:</strong> <?php echo esc_html(
                                                                            $wear_time,
                                                                          ); ?></div>
            <?php endif; ?>

            <?php if ($desc): ?>
              <div class="service-description"><?php echo esc_html($desc); ?></div>
            <?php endif; ?>
          </div>
          <?php
          $related_addons = get_field('addons', $post_id);
          if (!empty($related_addons)): ?>
            <div class="core-related-addons" data-core-id="<?php echo esc_attr(
                                                              $post_id,
                                                            ); ?>">
              <?php foreach ($related_addons as $addon):

                $addon_post = is_object($addon) ? $addon : get_post($addon);
                $a_id = $addon_post->ID;

                // Check if addon is online
                $addon_is_online = get_post_meta($a_id, 'is_online', true);
                if (!$addon_is_online) {
                  continue;
                } // Skip offline addons

                $a_title = get_the_title($a_id);
                $a_price = get_post_meta($a_id, 'price_min', true);
                $a_currency = get_post_meta($a_id, 'currency', true) ?: 'SGD';
                $a_duration = get_post_meta($a_id, 'duration_minutes', true);
                $a_wear = get_post_meta($a_id, 'wear_time', true);
                $a_desc = get_post_meta($a_id, 'description', true);
                $a_altegio = get_post_meta($a_id, 'altegio_id', true);
              ?>
                <div class="service-item addon-item"
                  data-service-id="<?php echo esc_attr($a_id); ?>"
                  data-core-linked="<?php echo esc_attr($post_id); ?>">

                  <div class="service-info">
                    <div class="service-title">
                      <h4 class="service-name"><?php echo esc_html(
                                                  $a_title,
                                                ); ?></h4>
                      <div class="service-checkbox-wrapper">
                        <div class="service-price"><?php echo esc_html(
                                                      $a_price,
                                                    ); ?> <?php echo esc_html($a_currency); ?></div>
                        <input type="checkbox"
                          class="service-checkbox"
                          data-service-id="<?php echo esc_attr($a_id); ?>"
                          data-altegio-id="<?php echo esc_attr(
                                              $a_altegio,
                                            ); ?>"
                          data-service-title="<?php echo esc_attr(
                                                $a_title,
                                              ); ?>"
                          data-service-price="<?php echo esc_attr(
                                                $a_price,
                                              ); ?>"
                          data-service-currency="<?php echo esc_attr(
                                                    $a_currency,
                                                  ); ?>"
                          data-is-addon="true"
                          <?php if (
                            $a_duration
                          ): ?>data-service-duration="<?php echo esc_attr(
                                                                                  $a_duration,
                                                                                ); ?>" <?php endif; ?>
                          <?php if (
                            $a_wear
                          ): ?>data-service-wear-time="<?php echo esc_attr(
                                                                                    $a_wear,
                                                                                  ); ?>" <?php endif; ?>>
                      </div>
                    </div>
                    <?php if (
                      $a_duration
                    ): ?><div class="service-duration"><strong>Duration:</strong> <?php echo esc_html(
                                                                                                        $a_duration,
                                                                                                      ); ?> min</div><?php endif; ?>
                    <?php if (
                      $a_wear
                    ): ?><div class="service-wear-time"><strong>Wear time:</strong> <?php echo esc_html(
                                                                                                          $a_wear,
                                                                                                        ); ?></div><?php endif; ?>
                    <?php if (
                      $a_desc
                    ): ?><div class="service-description"><?php echo esc_html(
                                                                                $a_desc,
                                                                              ); ?></div><?php endif; ?>
                  </div>
                </div>
              <?php
              endforeach; ?>
            </div>
          <?php endif;
          ?>
        </div>




      <?php
      endforeach;
      wp_reset_postdata();
      ?>
    </div>
  <?php endif; ?>
<?php endforeach; ?>