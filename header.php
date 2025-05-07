<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    
<header id="masthead" class="site-header header-three">
    <div class="container">
        <div class="logo">
            <?php if (has_custom_logo()): ?>
                <?php the_custom_logo(); ?>
            <?php endif; ?>
        </div>
        <div class="location">
            <span>Singapore</span>
        </div>
        <div class="menu-container">
            <button id="burger" class="burger">
                <span class="bar bar--top"></span>
                <span class="bar bar--middle"></span>
                <span class="bar bar--bottom"></span>
            </button>
            <nav>
                <?php
                    wp_nav_menu(
                        array(
                            'theme_location' => 'main-menu',
                            'container' => 'ul',
                        )
                    );
                ?>
            </nav>
        </div>
    </div>
</header>
<div class="burger-menu">
    <nav>
        <?php
            wp_nav_menu(
                array(
                    'theme_location' => 'main-menu',
                    'container' => 'ul',
                )
            );
        ?>
    </nav>
    <div class="icons">
        <?php
        foreach(getIcon() as $icon) {
            $text = $icon['text'];
            $image = $icon['image'];
            echo "<a target='_blank' rel='noopener noreferrer' href='$text'>
                <img src='$image' alt='image'>
            </a>";
        };
        ?>
    </div>
    <a href="#" class="btn white">Book an Appointment</a>
</div>