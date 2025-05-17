 <div class="services-list">
     <?php foreach ($services as $service) :
            $post_id = $service->ID;
            $title = get_the_title($post_id);
            $price_min = get_post_meta($post_id, 'price_min', true);
            $currency = get_post_meta($post_id, 'currency', true) ?: 'SGD';
            $duration = get_post_meta($post_id, 'duration_minutes', true);
            $wear_time = get_post_meta($post_id, 'wear_time', true);


            if (empty($wear_time) && !empty($service->post_content)) {
                preg_match('/wear\s+time:?\s+([^\.]+)/i', $service->post_content, $matches);
                if (!empty($matches[1])) {
                    $wear_time = trim($matches[1]);
                }
            }

            $acf_description = get_post_meta($post_id, 'description', true);
        ?>
         <div class="service-card" data-service-id="<?php echo esc_attr($post_id); ?>">
             <div class="service-meta">
                 <h3 class="service-title"><?php echo esc_html($title); ?>
                     <div class="service-price">
                         <?php echo esc_html($price_min); ?> <?php echo esc_html($currency); ?>
                     </div>
                 </h3>

                 <?php if ($duration) : ?>
                     <div class="service-duration"><strong>Duration:</strong> <?php echo esc_html($duration); ?> min</div>
                 <?php endif; ?>

                 <?php if ($wear_time) : ?>
                     <div class="service-wear-time"><strong>Wear time:</strong> <?php echo esc_html($wear_time); ?></div>
                 <?php endif; ?>

                 <?php if ($acf_description) : ?>
                     <div class="service-description"><?php echo esc_html($acf_description); ?></div>
                 <?php endif; ?>
             </div>

             <button type="button"
                 class="btn yellow book-btn "
                 data-popup-open="true"
                 data-service-id="<?php echo esc_attr($post_id); ?>">

                 Book this
             </button>
         </div>
     <?php endforeach; ?>
 </div>