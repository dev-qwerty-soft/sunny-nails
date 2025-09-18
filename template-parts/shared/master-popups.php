<?php
$posts = $args['posts'] ?? [];
$isPage = $args['isPage'] ?? false;

// Don't generate popups if no posts
if (empty($posts)) {
  return;
}

// Generate popups for all masters
foreach ($posts as $post) {

  $details_popup = get_field('details_pop_up', $post->ID);
  $master_description = $details_popup['description'] ?? null;
  $master_achievements = $details_popup['masters_achievements'] ?? null;

  $has_content =
    ($master_description && !empty(trim($master_description))) ||
    ($master_achievements && is_array($master_achievements) && !empty($master_achievements));

  if (!$has_content) {
    continue;
  }

  // Get master data
  $level = max((int) get_field('master_level', $post->ID), -1);
  $levelName = get_master_level_title($level, true);
  $starsCount = get_master_level_stars($level);
  $id = get_field('altegio_id', $post->ID);
  $image = get_the_post_thumbnail_url($post->ID, 'large') ?: '';
  $name = isset($post->post_title) ? $post->post_title : '';
  $instagram = get_field('instagram', $post->ID) ?: '';
  ?>

    <div id="master-popup-<?= esc_attr(
      $post->ID,
    ) ?>" class="master-popup-backdrop" style="display: none;">
        <div class="master-popup">
            <button class="master-popup__close">
                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 19 19" fill="none">
                    <path d="M8.21873 9.49992L0.702293 1.9853C0.618034 1.90104 0.551196 1.80101 0.505595 1.69092C0.459994 1.58083 0.436523 1.46283 0.436523 1.34367C0.436523 1.22451 0.459994 1.10652 0.505595 0.996429C0.551196 0.886339 0.618034 0.786308 0.702293 0.702049C0.786552 0.617789 0.886583 0.550952 0.996673 0.505351C1.10676 0.45975 1.22476 0.436279 1.34392 0.436279C1.46308 0.436279 1.58107 0.45975 1.69116 0.505351C1.80125 0.550952 1.90128 0.617789 1.98554 0.702049L9.50017 8.21849L17.0148 0.702049C17.185 0.531879 17.4158 0.436279 17.6564 0.436279C17.8971 0.436279 18.1279 0.531879 18.298 0.702049C18.4682 0.872218 18.5638 1.10302 18.5638 1.34367C18.5638 1.58433 18.4682 1.81513 18.298 1.9853L10.7816 9.49992L18.298 17.0145C18.4682 17.1847 18.5638 17.4155 18.5638 17.6562C18.5638 17.8968 18.4682 18.1276 18.298 18.2978C18.1279 18.468 17.8971 18.5636 17.6564 18.5636C17.4158 18.5636 17.185 18.468 17.0148 18.2978L9.50017 10.7814L1.98554 18.2978C1.81537 18.468 1.58457 18.5636 1.34392 18.5636C1.10326 18.5636 0.872463 18.468 0.702293 18.2978C0.532123 18.1276 0.436523 17.8968 0.436523 17.6562C0.436523 17.4155 0.532123 17.1847 0.702293 17.0145L8.21873 9.49992Z" fill="#302F34" />
                </svg>
            </button>
            <h2 class="master-popup__title mob"><?= esc_html($name) ?></h2>

            <div class="master-popup__img">
                <?php if ($image): ?>
                    <img src="<?= esc_url($image) ?>" alt="<?= esc_attr($name) ?>" />
                <?php else: ?>
                    <div class="master-popup__placeholder">No Image</div>
                <?php endif; ?>
            </div>

            <div class="master-popup__content">
                <h2 class="master-popup__title"><?= esc_html($name) ?></h2>

                <div class="master-popup__level">
                    <div class="stars yellow">
                        <?= str_repeat("<div class='star'></div>", $starsCount) ?>
                        <?php if ($levelName): ?>
                            <span>(<?= esc_html($levelName) ?>)</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($instagram): ?>
                        <a href="<?= esc_url(
                          $instagram,
                        ) ?>" class="instagram-link" target="_blank" rel="noopener">
                            <span class="instagram-icon">S</span>
                            Instagram
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($master_description): ?>
                    <div class="master-popup__desc">
                        <?= wp_kses_post($master_description) ?>
                    </div>
                <?php endif; ?>

                <?php if ($master_achievements): ?>
                    <div class="master-popup__achievements">
                        <h3>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="19" viewBox="0 0 18 19" fill="none">
                                <g clip-path="url(#clip0_3975_30891)">
                                    <path d="M8.361 0.692969C8.74772 0.436328 9.25397 0.436328 9.64069 0.692969L10.2665 1.1043C10.4774 1.24141 10.7235 1.3082 10.9731 1.29414L11.7219 1.24844C12.186 1.22031 12.6219 1.47344 12.8294 1.88828L13.1669 2.55977C13.2794 2.78477 13.4622 2.96406 13.6837 3.07656L14.3622 3.41758C14.777 3.625 15.0301 4.06094 15.002 4.525L14.9563 5.27383C14.9422 5.52344 15.009 5.77305 15.1462 5.98047L15.561 6.60625C15.8176 6.99297 15.8176 7.49922 15.561 7.88594L15.1462 8.51524C15.009 8.72617 14.9422 8.97227 14.9563 9.22188L15.002 9.9707C15.0301 10.4348 14.777 10.8707 14.3622 11.0781L13.6907 11.4156C13.4657 11.5281 13.2864 11.7109 13.1739 11.9324L12.8329 12.6109C12.6255 13.0258 12.1895 13.2789 11.7255 13.2508L10.9766 13.2051C10.727 13.191 10.4774 13.2578 10.27 13.3949L9.6442 13.8098C9.25748 14.0664 8.75123 14.0664 8.36451 13.8098L7.73522 13.3949C7.52428 13.2578 7.27819 13.191 7.02858 13.2051L6.27975 13.2508C5.81569 13.2789 5.37975 13.0258 5.17233 12.6109L4.83483 11.9395C4.72233 11.7145 4.53951 11.5352 4.31803 11.4227L3.63951 11.0816C3.22467 10.8742 2.97155 10.4383 2.99967 9.97422L3.04537 9.22539C3.05944 8.97578 2.99264 8.72617 2.85553 8.51875L2.4442 7.88945C2.18756 7.50274 2.18756 6.99649 2.4442 6.60977L2.85553 5.98398C2.99264 5.77305 3.05944 5.52695 3.04537 5.27734L2.99967 4.52852C2.97155 4.06445 3.22467 3.62852 3.63951 3.42109L4.311 3.08359C4.536 2.96758 4.71881 2.78477 4.83131 2.55977L5.16881 1.88828C5.37623 1.47344 5.81217 1.22031 6.27623 1.24844L7.02506 1.29414C7.27467 1.3082 7.52428 1.24141 7.7317 1.1043L8.361 0.692969ZM11.8133 7.24961C11.8133 6.50369 11.517 5.78832 10.9896 5.26087C10.4621 4.73343 9.74676 4.43711 9.00084 4.43711C8.25492 4.43711 7.53955 4.73343 7.0121 5.26087C6.48466 5.78832 6.18834 6.50369 6.18834 7.24961C6.18834 7.99553 6.48466 8.7109 7.0121 9.23835C7.53955 9.76579 8.25492 10.0621 9.00084 10.0621C9.74676 10.0621 10.4621 9.76579 10.9896 9.23835C11.517 8.7109 11.8133 7.99553 11.8133 7.24961ZM2.29654 16.0316L3.81178 12.4281C3.81881 12.4316 3.82233 12.4352 3.82584 12.4422L4.16334 13.1137C4.57467 13.9293 5.42897 14.425 6.34303 14.3723L7.09186 14.3266C7.09889 14.3266 7.10944 14.3266 7.11647 14.3336L7.74225 14.7484C7.92155 14.8645 8.11139 14.9559 8.30826 15.0191L6.98639 18.1586C6.90553 18.352 6.72623 18.482 6.51881 18.4996C6.31139 18.5172 6.111 18.4223 5.9985 18.2465L4.86647 16.5133L2.8942 16.8051C2.69381 16.8332 2.49342 16.7523 2.36686 16.5941C2.24029 16.4359 2.21569 16.218 2.29303 16.0316H2.29654ZM11.0153 18.1551L9.69342 15.0191C9.8903 14.9559 10.0801 14.868 10.2594 14.7484L10.8852 14.3336C10.8922 14.3301 10.8993 14.3266 10.9098 14.3266L11.6587 14.3723C12.5727 14.425 13.427 13.9293 13.8383 13.1137L14.1758 12.4422C14.1794 12.4352 14.1829 12.4316 14.1899 12.4281L15.7087 16.0316C15.786 16.218 15.7579 16.4324 15.6348 16.5941C15.5118 16.7559 15.3079 16.8367 15.1075 16.8051L13.1352 16.5133L12.0032 18.243C11.8907 18.4188 11.6903 18.5137 11.4829 18.4961C11.2755 18.4785 11.0962 18.3449 11.0153 18.1551Z" fill="url(#paint0_linear_3975_30891)" />
                                </g>
                                <defs>
                                    <linearGradient id="paint0_linear_3975_30891" x1="14.6252" y1="-0.250061" x2="1.87524" y2="12.4999" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#85754F" />
                                        <stop offset="0.533654" stop-color="#D8CDB4" />
                                        <stop offset="1" stop-color="#85754F" />
                                    </linearGradient>
                                    <clipPath id="clip0_3975_30891">
                                        <rect width="18" height="18" fill="white" transform="translate(0 0.5)" />
                                    </clipPath>
                                </defs>
                            </svg>
                            Master's achievements
                        </h3>
                        <?php foreach ($master_achievements as $achievement): ?>
                            <div class="achievement-item">
                                <div class="achievement-header">
                                    <?php if ($achievement['place']): ?>
                                        <div class="achievement-place"><?= esc_html(
                                          $achievement['place'],
                                        ) ?></div>
                                    <?php endif; ?>
                                    <?php if ($achievement['date']): ?>
                                        <div class="achievement-date"><?= esc_html(
                                          $achievement['date'],
                                        ) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($achievement['title']): ?>
                                        <div class="achievement-title"><?= esc_html(
                                          $achievement['title'],
                                        ) ?></div>
                                    <?php endif; ?>
                                    <?php if ($achievement['description']): ?>
                                        <div class="achievement-description"><?= esc_html(
                                          $achievement['description'],
                                        ) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($achievement['location']): ?>
                                    <div class="achievement-location"><?= esc_html(
                                      $achievement['location'],
                                    ) ?></div>
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <button data-staff-id="<?= esc_attr(
                  $id,
                ) ?>" class="btn yellow book-tem master-popup__book">Book an Appointment</button>
            </div>
        </div>
    </div>

<?php
} ?>
