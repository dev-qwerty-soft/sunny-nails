<?php

/**
 * Template Name: Partners Page
 */
get_header();

$hero_title = get_field('title');
$hero_desc = get_field('description');
$hero_image = get_field('image');
$image_mob = get_field('image_mob');
$benefits = get_field('benefit_item');
$hero_link = get_field('hero_link');
?>
<main>
    <section class="partners-hero">
        <div class="container partners-hero__container">
            <div class="partners-hero__content">
                <div class="partners-hero__text">
                    <?php if ($hero_title): ?>
                        <h1 class="partners-hero__title"><?= esc_html($hero_title) ?></h1>
                    <?php endif; ?>
                    <?php if ($hero_desc): ?>
                        <div class="partners-hero__desc"><?= esc_html($hero_desc) ?></div>
                    <?php endif; ?>

                    <!-- Hero CTA Button -->
                    <?php if ($hero_link): ?>
                        <?php
                        $link_url = is_array($hero_link) ? $hero_link['url'] : $hero_link;
                        $link_title = is_array($hero_link)
                          ? $hero_link['title']
                          : 'Become a Partner';
                        $link_target = is_array($hero_link) ? $hero_link['target'] : '_self';
                        ?>
                        <?php if ($link_url): ?>
                            <a href="<?php echo esc_url($link_url); ?>"
                                class="partners-hero__cta btn white"
                                target="<?php echo esc_attr($link_target); ?>">
                                <div class="partners-hero__cta-text"><?php echo esc_html(
                                  $link_title,
                                ); ?></div>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="partners-hero__cta btn white">
                            <div class="partners-hero__cta-text">Become a Partner</div>
                        </div>
                    <?php endif; ?>

                    <svg class="hero__icon" xmlns="http://www.w3.org/2000/svg" width="124" height="119" viewBox="0 0 124 119" fill="none">
                        <g opacity="0.75" clip-path="url(#clip0_2821_9816)">
                            <path d="M26.1369 90.1083C27.9886 92.742 29.8486 95.3688 31.8825 98.2571C29.8717 99.6649 27.6269 99.1292 26.1315 97.4863C22.7897 93.792 19.3446 90.0572 16.8211 85.8133C14.0336 81.1309 15.7274 78.3228 21.0566 77.4307C25.8333 76.6313 30.6259 75.9865 35.4198 75.3265C36.9097 75.1166 38.5858 74.6413 39.443 76.9504C37.5392 79.5547 34.329 79.1872 31.6592 79.9657C28.8955 80.7658 26.0382 81.2662 22.2215 82.1113C24.3537 83.3404 25.5746 84.1764 26.8932 84.7921C39.5246 90.6665 52.597 92.6717 66.2592 89.0234C72.5445 87.3423 78.2371 84.5025 82.83 79.8049C89.6215 72.8603 90.9155 65.7737 86.5745 57.1084C84.7569 53.483 82.0789 50.2883 79.7335 46.8192C77.7695 47.9789 76.4684 48.7732 75.1467 49.5426C68.6811 53.2578 62.5062 52.2781 56.6579 48.2221C54.3332 46.6073 52.356 44.4821 53.0832 41.2986C53.7875 38.2736 56.2249 36.807 58.9788 36.0289C63.1339 34.8487 67.2432 35.5776 71.1944 36.9956C73.7697 37.9149 76.2176 39.2048 78.9252 40.4199C81.414 38.897 82.183 36.1537 83.1069 33.6318C85.4709 27.1985 84.8866 20.6981 83.1888 14.2368C82.0171 9.78517 80.688 5.37974 79.3616 0.706692C82.7166 1.87645 83.4619 2.88137 86.0403 13.7971C87.1784 18.5976 87.9296 23.7592 87.4172 28.6089C86.8888 33.6254 84.6105 38.4519 82.7761 44.3924C84.1198 46.0208 86.3799 48.3873 88.2204 51.0581C96.4682 63.0353 94.9047 75.232 84.1072 84.9096C77.0041 91.2734 68.5627 94.4402 59.2408 95.5407C49.5862 96.6783 40.2775 95.2542 31.3441 91.4396C29.7857 90.7704 28.2273 90.1012 26.6689 89.432C26.4938 89.6602 26.3188 89.8884 26.1438 90.1167L26.1369 90.1083ZM75.1744 44.241C68.1398 39.0692 61.0897 38.4347 57.4996 42.6538C62.5673 48.7784 69.7083 49.4216 75.1744 44.241Z" fill="white" />
                        </g>
                        <defs>
                            <clipPath id="clip0_2821_9816">
                                <rect width="68" height="104" fill="white" transform="translate(80.2188) rotate(50.4725)" />
                            </clipPath>
                        </defs>
                    </svg>
                    <svg class="hero__icon-mobile" xmlns="http://www.w3.org/2000/svg" width="68" height="104" viewBox="0 0 68 104" fill="none">
                        <g opacity="0.75" clip-path="url(#clip0_3025_19174)">
                            <path d="M32.9175 99.0631C29.7076 99.311 26.4977 99.5482 22.9754 99.8176C23.1693 102.265 25.0112 103.655 27.2302 103.763C32.2066 103.989 37.2799 104.27 42.1594 103.515C47.5451 102.685 48.633 99.5913 45.9294 94.913C43.5058 90.7198 40.953 86.6128 38.4109 82.4951C37.6246 81.2123 36.9244 79.617 34.5978 80.4254C33.8007 83.5515 36.1274 85.7936 37.226 88.3483C38.3678 90.9893 39.8004 93.5117 41.5777 96.9934C39.2726 96.1311 37.8508 95.7215 36.5367 95.0963C23.9664 89.0921 14.0998 80.2853 8.2186 67.4254C5.51497 61.5075 4.08237 55.3094 4.78252 48.777C5.81657 39.1186 10.4591 33.6104 19.9056 31.4437C23.8587 30.5382 28.0272 30.5705 32.1958 30.1717C32.5512 32.4246 32.7667 33.9337 33.0144 35.4429C34.2639 42.7944 38.9495 46.9337 45.8001 48.8633C48.5253 49.6286 51.4228 49.8011 53.4155 47.214C55.3005 44.7455 54.8804 41.9321 53.7279 39.3127C51.9937 35.3566 48.8161 32.651 45.2077 30.5059C42.8595 29.1045 40.3067 28.0374 37.6461 26.7223C37.2368 23.8334 38.8633 21.4943 40.2205 19.1767C43.6781 13.2588 49.0638 9.57221 55.1281 6.76956C59.3075 4.84004 63.5514 3.06143 68 1.11035C64.9625 -0.732932 63.713 -0.668256 53.6525 4.29028C49.2254 6.46773 44.766 9.17337 41.3515 12.6551C37.8185 16.2555 35.5457 21.0846 32.1312 26.2803C30.02 26.2803 26.7562 26.0432 23.5248 26.3235C9.03723 27.5846 0.624748 36.5531 0.0323181 51.0407C-0.355453 60.5697 2.57437 69.0962 7.65849 76.9868C12.9257 85.1576 19.9487 91.4312 28.5766 95.8939C30.0846 96.67 31.5926 97.4462 33.1006 98.2223C33.036 98.5026 32.9713 98.7828 32.9067 99.0631H32.9175ZM37.086 32.0473C45.5524 34.1817 50.5287 39.2157 49.5593 44.67C41.61 44.6593 36.569 39.5606 37.086 32.0473Z" fill="white" />
                        </g>
                        <defs>
                            <clipPath id="clip0_3025_19174">
                                <rect width="68" height="104" fill="white" transform="matrix(-1 0 0 1 68 0)" />
                            </clipPath>
                        </defs>
                    </svg>
                </div>

            </div>

            <?php if ($image_mob): ?>
                <div class="partners-hero__image mobile">
                    <img
                        src="<?= esc_url($image_mob['url']) ?>"
                        alt="<?= esc_attr($image_mob['alt']) ?>"
                        width="<?= esc_attr($image_mob['width'] ?? 700) ?>"
                        height="<?= esc_attr($image_mob['height'] ?? 700) ?>"
                        loading="lazy">
                </div>
            <?php endif; ?>
            <?php if ($benefits): ?>
                <div class="partners-hero__benefits">
                    <?php foreach ($benefits as $item): ?>
                        <div class="partners-hero__benefit">
                            <div class="partners-hero__icon">
                                <?php if (!empty($item['icon']['url'])): ?>
                                    <img src="<?= esc_url(
                                      $item['icon']['url'],
                                    ) ?>" alt="<?= esc_attr($item['icon']['alt'] ?? '') ?>">
                                <?php endif; ?>
                            </div>
                            <div class="partners-hero__benefit-text">
                                <?php if (!empty($item['title'])): ?>
                                    <div class="partners-hero__benefit-title"><?= esc_html(
                                      $item['title'],
                                    ) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['description'])): ?>
                                    <div class="partners-hero__benefit-desc"><?= esc_html(
                                      $item['description'],
                                    ) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
        <?php if ($hero_image): ?>
            <div class="partners-hero__image">
                <img src="<?= esc_url($hero_image['url']) ?>" alt="<?= esc_attr(
  $hero_image['alt'],
) ?>">
            </div>
        <?php endif; ?>
        <a href="#partners-benefits" class="more-below">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M12.5898 8.47348L8.08984 13.0889L3.58984 8.47348M8.08984 12.4478V3.08887" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>More below</a>
    </section>

    <?php
    $partner_program_active = get_field('partner_program_active');
    if ($partner_program_active): ?>
        <section class="partner-program">
            <div class="partner-program__container">
                <div class="partner-program__image">
                    <?php
                    $image = get_field('partner_program_image');
                    if ($image) {
                      echo '<img src="' .
                        esc_url($image['url']) .
                        '" alt="' .
                        esc_attr($image['alt']) .
                        '">';
                    }
                    ?>
                </div>
                <div class="partner-program__content">
                    <h2 class="partner-program__title"><?php the_field(
                      'partner_program_title',
                    ); ?></h2>
                    <div class="partner-program__desc"><?php the_field(
                      'partner_program_description',
                    ); ?></div>
                </div>
            </div>
        </section>
    <?php endif;
    ?>

    <section class="partners-section" id="partners-benefits">
        <div class="partners-section__head">
            <?php if ($section_title = get_field('partners_section_title')): ?>
                <h2 class="partners-section__title"><?= esc_html($section_title) ?></h2>
            <?php endif; ?>
            <?php if ($section_desc = get_field('partners_section_description')): ?>
                <div class="partners-section__desc"><?= esc_html($section_desc) ?></div>
            <?php endif; ?>
        </div>
        <?php
        $partners = get_posts([
          'post_type' => 'partner',
          'posts_per_page' => -1,
          'orderby' => 'menu_order',
          'order' => 'ASC',
        ]);
        $cats = get_terms(['taxonomy' => 'partner_cat', 'hide_empty' => false]);

        $partners_arr = [];
        foreach ($partners as $post) {
          $image = get_field('partner_image', $post->ID);
          $benefit_icon = get_field('partner_benefit_icon', $post->ID);
          $benefit_title = get_field('partner_benefit_title', $post->ID);
          $benefit_desc = get_field('partner_benefit_description', $post->ID);
          $partner_cats = wp_get_post_terms($post->ID, 'partner_cat', ['fields' => 'slugs']);
          $partners_link_card = get_field('partners_link_card', $post->ID);
          $partners_link_popup = get_field('partners_link_popup', $post->ID);
          $button_text = get_field('partner_button_text', $post->ID) ?: 'View details';
          $desc = get_field('partner_description', $post->ID);
          $partners_arr[] = [
            'ID' => $post->ID,
            'title' => get_the_title($post),
            'desc' => $desc,
            'cats' => $partner_cats,
            'image' => $image,
            'featured' => get_the_post_thumbnail_url($post->ID, 'large'),
            'benefit_icon' => $benefit_icon,
            'benefit_title' => $benefit_title,
            'benefit_desc' => $benefit_desc,
            'link' => $partners_link_card,
            'link_popup' => $partners_link_popup,
            'button_text' => $button_text,
          ];
        }
        ?>
        <div class="partners-filter">
            <button class="partners-filter__btn active" data-cat="all">All</button>
            <?php foreach ($cats as $cat): ?>
                <button class="partners-filter__btn" data-cat="<?= esc_attr(
                  $cat->slug,
                ) ?>"><?= esc_html($cat->name) ?></button>
            <?php endforeach; ?>
        </div>

        <div class="partners-list">
            <?php foreach ($partners_arr as $partner): ?>
                <div class="partner-card" data-cats="<?= esc_attr(
                  implode(' ', $partner['cats']),
                ) ?>">
                    <div class="partner-card__img">
                        <?php if ($partner['featured']): ?>
                            <img src="<?= esc_url($partner['featured']) ?>" alt="">
                        <?php endif; ?>
                    </div>
                    <div class="partner-card__content">
                        <div class="partner-card__title"><?= esc_html($partner['title']) ?></div>

                        <div class="partner-card__desc clamp-3"><?= esc_html(
                          wp_strip_all_tags($partner['desc']),
                        ) ?></div>
                        <?php if ($partner['benefit_icon'] || $partner['benefit_title']): ?>
                            <div class="partner-card__benefit">
                                <div class="partner-card__benefit-icon">
                                    <?php if ($partner['benefit_icon']): ?>
                                        <img src="<?= esc_url(
                                          $partner['benefit_icon']['url'],
                                        ) ?>" alt="<?= esc_attr(
  $partner['benefit_icon']['alt'],
) ?>">
                                    <?php endif; ?>
                                </div>
                                <?php if ($partner['benefit_title']): ?>
                                    <span><?= esc_html($partner['benefit_title']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="partner-card__btns">
                            <button class="partner-card__btn yellow" data-popup="<?= $partner[
                              'ID'
                            ] ?>"><?= esc_html($partner['button_text']) ?></button>
                            <?php if ($partner['link']): ?>
                                <a href="<?= esc_url(
                                  $partner['link'],
                                ) ?>" target="_blank" class="partner-card__btn white"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <path d="M14.4273 13.5271L14.5293 5.47019L6.47239 5.57218M13.9627 6.03678L5.69049 14.309" stroke="#85754F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="partners-show-more btn " style="display:none;">Show more</button>


        <?php foreach ($partners_arr as $partner): ?>
            <div class="partner-popup-backdrop" id="partner-popup-<?= $partner[
              'ID'
            ] ?>" style="display:none;">
                <div class="partner-popup">
                    <div class="partner-popup__title mob"><?= esc_html($partner['title']) ?></div>
                    <div class="partner-popup__img">
                        <?php if ($partner['featured']): ?>
                            <img src="<?= esc_url($partner['featured']) ?>" alt="">
                        <?php endif; ?>
                    </div>
                    <div class="partner-popup__content">
                        <div class="partner-popup__title"><?= esc_html($partner['title']) ?></div>
                        <div class="partner-popup__desc"><?= $partner['desc'] ?></div>
                        <?php if ($partner['benefit_title'] || $partner['benefit_desc']): ?>
                            <div class="partner-popup__benefit-box">
                                <div class="partner-card__benefit">
                                    <?php if ($partner['benefit_icon']): ?>
                                        <div class="partner-popup__benefit-icon"><img src="<?= esc_url(
                                          $partner['benefit_icon']['url'],
                                        ) ?>" alt="<?= esc_attr(
  $partner['benefit_icon']['alt'],
) ?>"></div>
                                    <?php endif; ?>
                                    <?php if ($partner['benefit_title']): ?>
                                        <div class="partner-popup__benefit-title"><?= $partner[
                                          'benefit_title'
                                        ] ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($partner['benefit_desc']): ?>
                                    <div class="partner-popup__benefit-desc"><?= $partner[
                                      'benefit_desc'
                                    ] ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($partner['link_popup']): ?>
                            <a href="<?= esc_url(
                              $partner['link_popup'],
                            ) ?>" target="_blank" class="partner-popup__link">Visit the partner’s website</a>
                        <?php endif; ?>
                    </div>
                    <button class="partner-popup__close" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="29" height="29" viewBox="0 0 29 29" fill="none">
                            <path d="M13.2197 14.5L5.70327 6.98542C5.61901 6.90116 5.55217 6.80113 5.50657 6.69104C5.46097 6.58095 5.4375 6.46296 5.4375 6.3438C5.4375 6.22464 5.46097 6.10664 5.50657 5.99655C5.55217 5.88646 5.61901 5.78643 5.70327 5.70217C5.78753 5.61791 5.88756 5.55107 5.99765 5.50547C6.10774 5.45987 6.22573 5.4364 6.34489 5.4364C6.46406 5.4364 6.58205 5.45987 6.69214 5.50547C6.80223 5.55107 6.90226 5.61791 6.98652 5.70217L14.5011 13.2186L22.0158 5.70217C22.1859 5.532 22.4167 5.4364 22.6574 5.4364C22.8981 5.4364 23.1288 5.532 23.299 5.70217C23.4692 5.87234 23.5648 6.10314 23.5648 6.3438C23.5648 6.58445 23.4692 6.81525 23.299 6.98542L15.7826 14.5L23.299 22.0147C23.4692 22.1848 23.5648 22.4156 23.5648 22.6563C23.5648 22.897 23.4692 23.1278 23.299 23.2979C23.1288 23.4681 22.8981 23.5637 22.6574 23.5637C22.4167 23.5637 22.1859 23.4681 22.0158 23.2979L14.5011 15.7815L6.98652 23.2979C6.81635 23.4681 6.58555 23.5637 6.34489 23.5637C6.10424 23.5637 5.87344 23.4681 5.70327 23.2979C5.5331 23.1278 5.4375 22.897 5.4375 22.6563C5.4375 22.4156 5.5331 22.1848 5.70327 22.0147L13.2197 14.5Z" fill="#302F34" />
                        </svg></button>
                </div>
            </div>
        <?php endforeach; ?>
        <script type="application/json" id="partners-data">
            <?= json_encode($partners_arr) ?>
        </script>
    </section>

    <?php get_template_part('template-parts/shared/first-visit-popup'); ?>

</main>

<?php get_footer(); ?>
