<?php

function mytheme_customize_register($wp_customize) {
    $wp_customize->add_section('footer_settings', array(
        'title'    => 'Footer Settings',
        'priority' => 160,
    ));

    $wp_customize->add_setting('footer_copyright', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('footer_copyright', array(
        'label'   => 'Copyright Text',
        'section' => 'footer_settings',
        'type'    => 'text',
    ));

    $icons = ["Phone", "Instagram", "Whatsapp"];

    for ($i = 1; $i <= 3; $i++) {
        $icon = $icons[$i - 1];
        $wp_customize->add_setting("footer_icon_text_$i", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control("footer_icon_text_$i", array(
            'label'   => "$icon link",
            'section' => 'footer_settings',
            'type'    => 'text',
        ));
        $wp_customize->add_setting("footer_icon_image_$i", array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ));
        $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, "footer_icon_image_$i", array(
            'label'    => "$icon icon",
            'section'  => 'footer_settings',
            'settings' => "footer_icon_image_$i",
        )));
    }
}

function getIcon() {
  $icons = [];
  for ($i = 1; $i <= 3; $i++) {
    $icons[] = array(
        'text'  => get_theme_mod("footer_icon_text_$i"),
        'image' => get_theme_mod("footer_icon_image_$i"),
    );
  }
  return $icons;
}

add_action('customize_register', 'mytheme_customize_register');
