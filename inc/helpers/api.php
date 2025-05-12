<?php


function getUrl($str) {
  return get_template_directory_uri() . "/$str";
}

function dump($var, $label = null, $echo = true) {
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
  }
}

function displayIcon() {
  foreach(get_field('footer_icons', 'option') as $icon) {
    $img = $icon['footer_icon']["url"];
    $title = $icon['footer_icon']["title"];
    $url = $icon['footer_link'];

    if($img && $url) {
      echo "<a target='_blank' rel='noopener noreferrer' href='$url'>
        <img src='$img' alt='$title'>
      </a>";
    }
  };
};

function getPlaceReviews() {
  $apiKey = get_field('reviews_api_token', 'option');
  $placeId = get_field('reviews_api_place_id', 'option');
  $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=name,rating,reviews&language=en&key={$apiKey}";
  $response = file_get_contents($url);
  if ($response === false) {
    return null;
  }
  $data = json_decode($response, true);
  if (!isset($data['result'])) {
    return null;
  }
  $result = $data['result'];
  dump($data);
  return [
    'name' => $result['name'] ?? null,
    'rating' => $result['rating'] ?? null,
    'reviews' => $result['reviews'] ?? [],
  ];
};
