<?php
$index = $args["index"] ?? 0;
$addClass = $args["addClass"] ?? "";

$imageData = $args['image']['image'] ?? '';
$url = is_numeric($imageData)
  ? wp_get_attachment_image_url($imageData, 'large')
  : (is_array($imageData) ? $imageData['url'] : $imageData);

$master = $args["master"] ?? null;
$name = $master ? get_the_title($master) : '';
$level = $master ? (int) get_field('master_level', $master->ID) : 0;

$levelTitles = [
  0 => 'Intern',
  1 => 'Sunny Ray',
  2 => 'Sunny Shine',
  3 => 'Sunny Inferno',
  4 => 'Trainer',
  5 => 'Supervisor',
];

$levelName = $levelTitles[$level] ?? '';

$tags = $args['image']['master_image_work_tag'] ?? [];
$tagSlugs = [];
if (is_array($tags)) {
  foreach ($tags as $term_id) {
    $term = get_term($term_id, 'gallery_tag');
    if ($term && !is_wp_error($term)) {
      $tagSlugs[] = $term->slug;
    }
  }
}
$slug = implode(' ', $tagSlugs);

// === MULTI-SERVICE SUPPORT ===
$customTitle = $args['image']['custom_title'] ?? '';
$service_ids = $args['image']['servise'] ?? [];

if (!is_array($service_ids)) {
  $service_ids = [$service_ids];
}

$total_price = 0;
$currency = 'SGD';
$service_titles = [];

foreach ($service_ids as $service_id) {
  $price = floatval(get_field('price_min', $service_id));
  $total_price += $price;

  $service_titles[] = get_the_title($service_id);
  $currency_field = get_field('currency', $service_id);
  if (!empty($currency_field)) {
    $currency = $currency_field;
  }
}

$service_titles_string = implode(', ', $service_titles);
$service_ids_string = implode(',', $service_ids);
?>

<div data-index='<?= $index; ?>' data-slug='<?= esc_attr($slug); ?>' class='image active<?= esc_attr($addClass); ?>'>

  <div class='image__front'>
    <?php if ($url): ?>
      <img src='<?= esc_url($url); ?>' alt='<?= esc_attr($customTitle ?: $service_titles_string); ?>'>
    <?php endif; ?>
    <div class='wrapper'>
      <button type='button' aria-label='View' class='view'></button>
      <button type="button"
        class="btn white want-this-btn"
        data-master-id="<?= esc_attr($master ? $master->ID : 0); ?>"
        data-service-ids="<?= esc_attr($service_ids_string); ?>">
        I want this
      </button>
    </div>
  </div>

  <div class='image__back'>
    <span class='image__title'>
      <?= esc_html($customTitle ?: $service_titles_string); ?>
    </span>
    <span class='image__price'>Price: <?= esc_html(number_format($total_price, 2)); ?> <?= esc_html($currency); ?></span>
    <span class='image__master'>Master: <?= esc_html($name); ?></span>

    <?php
    $starsCount = match (true) {
      $level === 0 => 0,
      $level === 1 => 1,
      $level === 2 => 2,
      $level === 3 => 3,
      $level === 4, $level === 5 => 4,
      default => 0,
    };
    ?>
    <div class='stars'>
      <?= str_repeat("<div class='star'></div>", $starsCount); ?>
      <?php if ($levelName): ?>
        <span>(<?= esc_html($levelName); ?>)</span>
      <?php endif; ?>
    </div>


    <div class='wrapper'>
      <button type="button"
        class="btn white want-this-btn"
        data-master-id="<?= esc_attr(get_field('altegio_id', $master ? $master->ID : 0)); ?>"

        data-service-ids="<?= esc_attr($service_ids_string); ?>">
        I want this
      </button>

    </div>
  </div>

</div>