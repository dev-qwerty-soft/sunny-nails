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
            <div id="nav__burger-menu" class="nav__burger-menu">
                <div class="burger-menu_button">
                    <span class="burger-menu_lines"></span>
                </div>
            </div>
            <nav id="burger-menu_nav" class="burger-menu_nav">
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