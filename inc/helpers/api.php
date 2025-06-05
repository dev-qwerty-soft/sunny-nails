<?php

function getUrl($str)
{
  return get_template_directory_uri() . "/$str";
}

function dump($var, $label = null, $echo = true)
{
  $style = '<style>
  .pretty-dump {
    font-family: monospace;
    font-size: 14px;
    background: #1e1e1e;
    color: #dcdcdc;
    padding: 1em;
    margin: 1em 0;
    border-radius: 8px;
    overflow: auto;
    position: fixed;
    white-space: pre-wrap;
    line-height: 1.4;
    word-break: break-word;
    z-index: 9999;
    inset: 10%;
  }
  .pretty-dump b {
    color: #9cdcfe;
  }
  .pretty-dump .label {
    font-weight: bold;
    font-size: 1.1em;
    margin-bottom: 0.5em;
    display: block;
    color: #ce9178;
  }
  </style>';

  $output = $style;
  $output .= '<div class="pretty-dump">';
  if ($label) {
    $output .= '<span class="label">' . htmlspecialchars($label) . '</span>';
  }
  $output .= '<pre>' . htmlspecialchars(print_r($var, true)) . '</pre>';
  $output .= '</div>';
  if ($echo) {
    echo $output;
  } else {
    return $output;
  };
}

function displayIcon()
{
  $arr = get_field('footer_icons', 'option');

  if (!is_array($arr)) return;

  foreach ($arr as $icon) {
    $img = $icon['footer_icon']['url'] ?? null;
    $url = $icon['footer_link']['url'] ?? null;
    $title = $icon['footer_icon']['title'] ?? '';

    if (is_string($img) && is_string($url)) {
      echo "<a target='_blank' rel='noopener noreferrer' href='" . esc_url($url) . "'>
              <img src='" . esc_url($img) . "' alt='" . esc_attr($title) . "'>
            </a>";

    }
  }
}



function getPlaceReviews()
{
  $apiKey = get_field('reviews_api_token', 'option');
  $placeId = get_field('reviews_api_place_id', 'option');
  $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=name,rating,reviews&language=en&key={$apiKey}";

  $ch = curl_init();

  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
  ]);

  $response = curl_exec($ch);
  $error = curl_error($ch);
  curl_close($ch);

  if ($response === false || !empty($error)) {
    return null;
  }

  $data = json_decode($response, true);
  if (!isset($data['result'])) {
    return null;
  }

  $result = $data['result'];

  return [
    'name' => $result['name'] ?? null,
    'rating' => $result['rating'] ?? null,
    'reviews' => $result['reviews'] ?? [],
  ];
}

function getPosts($slug)
{
  $query = new WP_Query([
    'post_type' => $slug,
    'posts_per_page' => -1
  ]);
  wp_reset_postdata();
  return $query->posts;
}

function logo($str)
{
  $logo_data = get_field($str, 'option');
  if (!$logo_data || !isset($logo_data['url'])) return '';

  $url = $logo_data['url'];
  $alt = $logo_data['title'] ?? '';
  $width = $logo_data['width'] ?? '215';
  $height = $logo_data['height'] ?? '40';

  return "<a href='" . esc_url(home_url('/')) . "' class='logo'>
    <img src='" . esc_url($url) . "'
         alt='" . esc_attr($alt) . "'
         width='{$width}' height='{$height}'
         fetchpriority='high'
         decoding='async'>
  </a>";
}


function console($data)
{
  echo '<script>console.log(' . json_encode($data) . ');</script>';
}

// Function to get services for a specific category
function get_services_by_category($category_id)
{
  return get_posts([
    'post_type' => 'service',
    'posts_per_page' => -1,
    'tax_query' => [
      [
        'taxonomy' => 'service_category',
        'field' => 'term_id',
        'terms' => $category_id
      ]
    ],
    'meta_key' => 'price_min',
    'orderby' => 'meta_value_num',
    'order' => 'ASC'
  ]);
}

function getAssetUrlAcf($str)
{
  $image = get_field($str, 'option');
  $url = isset($image['url']) ? $image['url'] : null;
  return $url;
}
