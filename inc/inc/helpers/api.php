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
