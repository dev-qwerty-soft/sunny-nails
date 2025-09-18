<?php

/**
 * Master Levels Helper Functions
 *
 * Functions to work with ACF master levels configuration
 */

/**
 * Get master level title with optional additional info
 *
 * @param int $level Master level
 * @param bool $include_additional_info Whether to include additional info
 * @return string Level title
 */
function get_master_level_title($level, $include_additional_info = true) {
  $master_levels = get_field('master_levels', 'option');

  if (empty($master_levels)) {
    return get_fallback_level_title($level, $include_additional_info);
  }

  foreach ($master_levels as $master_level) {
    if (
      isset($master_level['level_number']) &&
      (int) $master_level['level_number'] === (int) $level
    ) {
      $title = $master_level['level_title'] ?? 'Unknown Level';

      if ($include_additional_info && !empty($master_level['additional_info'])) {
        $title .= ', ' . $master_level['additional_info'];
      }

      return $title;
    }
  }

  return get_fallback_level_title($level, $include_additional_info);
}

/**
 * Get master level percentage
 *
 * @param int $level Master level
 * @return int Level percentage
 */
function get_master_level_percent($level) {
  $master_levels = get_field('master_levels', 'option');

  if (empty($master_levels)) {
    return get_fallback_level_percent($level);
  }

  foreach ($master_levels as $master_level) {
    if (
      isset($master_level['level_number']) &&
      (int) $master_level['level_number'] === (int) $level
    ) {
      return (int) ($master_level['price_percentage'] ?? 0);
    }
  }

  return get_fallback_level_percent($level);
}

/**
 * Get master level stars count
 *
 * @param int $level Master level
 * @return int Stars count
 */
function get_master_level_stars($level) {
  $master_levels = get_field('master_levels', 'option');

  if (empty($master_levels)) {
    return get_fallback_level_stars($level);
  }

  foreach ($master_levels as $master_level) {
    if (
      isset($master_level['level_number']) &&
      (int) $master_level['level_number'] === (int) $level
    ) {
      return (int) ($master_level['stars_count'] ?? 1);
    }
  }

  return get_fallback_level_stars($level);
}

/**
 * Get all master levels configuration formatted for JavaScript
 *
 * @return array Configuration array
 */
function get_master_levels_config() {
  $master_levels = get_field('master_levels', 'option');
  $config = [];

  if (empty($master_levels)) {
    return get_fallback_config();
  }

  foreach ($master_levels as $master_level) {
    $level = (int) ($master_level['level_number'] ?? 0);
    $config[$level] = [
      'title' => $master_level['level_title'] ?? 'Unknown Level',
      'percent' => (int) ($master_level['price_percentage'] ?? 0),
      'stars' => (int) ($master_level['stars_count'] ?? 1),
      'additional_info' => $master_level['additional_info'] ?? '',
    ];
  }

  return $config;
}

/**
 * Fallback function for level title when ACF data is not available
 */
function get_fallback_level_title($level, $include_additional_info = true) {
  $titles = [
    -1 => 'Intern',
    1 => 'Sunny Ray',
    2 => 'Sunny Shine',
    3 => 'Sunny Inferno',
    4 => 'Trainer',
    5 => 'Salon Manager',
  ];

  $additional_info = [
    -1 => '',
    1 => '',
    2 => '',
    3 => '',
    4 => '',
    5 => '',
  ];

  $title = $titles[$level] ?? 'Unknown Level';

  if ($include_additional_info && !empty($additional_info[$level])) {
    $title .= ', ' . $additional_info[$level];
  }

  return $title;
}

/**
 * Fallback function for level percentage when ACF data is not available
 */
function get_fallback_level_percent($level) {
  $percents = [
    -1 => -50,
    1 => 0,
    2 => 10,
    3 => 20,
    4 => 30,
    5 => 30,
  ];

  return $percents[$level] ?? 0;
}

/**
 * Fallback function for level stars when ACF data is not available
 */
function get_fallback_level_stars($level) {
  $stars = [
    -1 => 0,
    1 => 1,
    2 => 2,
    3 => 3,
    4 => 3,
    5 => 3,
  ];

  return $stars[$level] ?? 1;
}

/**
 * Fallback configuration when ACF data is not available
 */
function get_fallback_config() {
  return [
    -1 => [
      'title' => 'Intern',
      'percent' => -50,
      'stars' => 0,
      'additional_info' => '',
    ],
    1 => [
      'title' => 'Sunny Ray',
      'percent' => 0,
      'stars' => 1,
      'additional_info' => '',
    ],
    2 => [
      'title' => 'Sunny Shine',
      'percent' => 10,
      'stars' => 2,
      'additional_info' => '',
    ],
    3 => [
      'title' => 'Sunny Inferno',
      'percent' => 20,
      'stars' => 3,
      'additional_info' => '#1 World Champion KAVA UAE',
    ],
    4 => [
      'title' => 'Trainer',
      'percent' => 30,
      'stars' => 3,
      'additional_info' => '',
    ],
    5 => [
      'title' => 'Salon Manager',
      'percent' => 30,
      'stars' => 3,
      'additional_info' => '',
    ],
  ];
}
