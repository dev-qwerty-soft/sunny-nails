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
  1 => 'Sunny Ray',
  2 => 'Sunny Shine',
  3 => 'Sunny Inferno',
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


$service_id = !empty($args['image']['servise']) ? (int) $args['image']['servise'] : null;
$price = $service_id ? get_field('price_min', $service_id) : 0;
$currency = $service_id ? get_field('currency', $service_id) : 'SGD';
$serviceTitle = $service_id ? get_the_title($service_id) : '';
?>

<div data-index='<?= $index; ?>' data-slug='<?= esc_attr(implode(" ", $tagSlugs)); ?>' class='image active<?= $addClass; ?>'>

  <div class='image__front'>
    <?php if ($url): ?>
      <img src='<?= esc_url($url); ?>' alt='<?= esc_attr($serviceTitle); ?>'>
    <?php endif; ?>
    <div class='wrapper'>
      <button type='button' aria-label='View' class='view'></button>
      <a href='#' class='btn white'>I want this</a>
    </div>
  </div>
  <div class='image__back'>
    <?php if ($serviceTitle): ?>
      <span class='image__title'><?= esc_html($serviceTitle); ?></span>
    <?php endif; ?>
    <span class='image__price'>Price: <?= esc_html("$price $currency"); ?></span>
    <span class='image__master'>Master: <?= esc_html($name); ?></span>
    <div class='stars'>
      <?= str_repeat("<div class='star'></div>", $level); ?>
      <?php if ($levelName): ?>
        <span>(<?= esc_html($levelName); ?>)</span>
      <?php endif; ?>
    </div>
    <div class='wrapper'>
      <a href='#' class='btn white'>I want this</a>
    </div>
  </div>
</div>