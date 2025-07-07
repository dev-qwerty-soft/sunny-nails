<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= get_template_directory_uri(); ?>/assets/svg/favicon_32x32.png">
    <link rel="shortcut icon" href="<?= get_template_directory_uri(); ?>/assets/svg/favicon_32x32.png">

    <link rel="icon" href="<?= getAssetUrlAcf('favicon_black_theme'); ?>" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="<?= getAssetUrlAcf('favicon_light_theme'); ?>" media="(prefers-color-scheme: light)">

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "Sunny Nails",
            "url": "<?php echo esc_url(home_url()); ?>",
            "logo": "<?php echo get_site_icon_url(); ?>"
        }
    </script>

    <title><?= wp_get_document_title(); ?></title>
    <?php wp_head(); ?>
</head>


<body <?php body_class(); ?>>
    <header id="masthead" class="site-header header-three">
        <div class="container">
            <?= logo('header_logo'); ?>
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
                <div class="buttons">
                    <button type="button" class="btn yellow open-popup mini">Book an Appointment</button>
                </div>
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
        <div class="icons"><?php displayIcon(); ?></div>
        <a href="#" class="btn white open-popup">Book an Appointment</a>
    </div>
    <?php
    $chat_link_url = get_field('chat_link_url', 'option');
    if ($chat_link_url) {
        echo "<a target='_blank' rel='noopener noreferrer' href='$chat_link_url' class='chat'>
            <span>Chat</span>
        </a>";
    }
    ?>