<?php
/**
 * Template Name: Mani Mania
 */
  get_header();
  $time = get_field('winner_counter');
  $now = new DateTime();
  $target = new DateTime($time);
  $interval = $now->diff($target);
  $totalDays = $interval->d + $interval->m * 30 + $interval->y * 365;
  $diffMs = max(0, ($target->getTimestamp() - $now->getTimestamp()) * 1000);
  $result = [
    'days' => str_pad($totalDays, 2, '0', STR_PAD_LEFT),
    'hours' => str_pad($interval->h, 2, '0', STR_PAD_LEFT),
    'minutes' => str_pad($interval->i, 2, '0', STR_PAD_LEFT),
    'seconds' => str_pad($interval->s, 2, '0', STR_PAD_LEFT),
  ];
?>
<main>
  <?php
    get_template_part('template-parts/sections/form');
  ?>
  <section class="counter-section">
    <div class="container">
      <h2 class="title"><?= get_field('winner_counter_title'); ?></h2>
      <div class="time" data-time-ms="<?= $diffMs ?>">
        <div class="time__days">
          <span id="days" class="time__number"><?= $result['days']; ?></span>
          <span class="time__text">Days</span>
        </div>
        <span class="time__number">:</span>
        <div class="time__days">
          <span id="hours" class="time__number"><?= $result['hours']; ?></span>
          <span class="time__text">Hours</span>
        </div>
        <span class="time__number">:</span>
        <div class="time__days">
          <span id="minutes" class="time__number"><?= $result['minutes']; ?></span>
          <span class="time__text">Minutes</span>
        </div>
        <span class="time__number">:</span>
        <div class="time__days">
          <span id="seconds" class="time__number"><?= $result['seconds']; ?></span>
          <span class="time__text">Seconds</span>
        </div>
      </div>
    </div>
  </section>
  <?php
    $items = get_field('winners_list');
    $title = get_field('winners_list_title');
    if($title && $items && count($items) > 0 && is_array($items)) {
      get_template_part('template-parts/sections/winners', null, ['items' => $items, 'title' => $title]);
    }
  ?>
</main>
<?php get_footer(); ?>