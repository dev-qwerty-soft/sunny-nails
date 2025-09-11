<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <meta name="robots" content="index, follow">
    <meta name="description" content="<?php echo get_bloginfo('description'); ?>">
    <meta name="application-name" content="SUNNY NAILS">
    <meta property="og:site_name" content="SUNNY NAILS">
    <meta name="twitter:site" content="@sunnynails">
    <meta property="og:title" content="<?= wp_get_document_title() ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>">
    <meta property="og:description" content="Professional nail salon in Singapore offering manicure, pedicure and nail care services.">
    <meta name="description" content="SUNNY NAILS - Professional nail salon in Singapore offering manicure, pedicure and nail care services">
    <meta property="og:image" content="<?php echo get_site_icon_url(); ?>">

    <link rel="icon" type="image/png" sizes="32x32" href="<?= get_template_directory_uri() ?>/assets/svg/favicon_32x32.png">
    <link rel="shortcut icon" href="<?= get_template_directory_uri() ?>/assets/svg/favicon_32x32.png">
    <link rel="icon" href="<?= getAssetUrlAcf(
                                'favicon_black_theme',
                            ) ?>" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="<?= getAssetUrlAcf(
                                'favicon_light_theme',
                            ) ?>" media="(prefers-color-scheme: light)">
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "SUNNY NAILS",
            "legalName": "SUNNY NAILS Singapore",
            "alternateName": ["Sunny Nails", "SUNNY NAILS SG"],
            "url": "<?php echo esc_url(home_url()); ?>",
            "logo": "<?php echo get_site_icon_url(); ?>",
            "description": "Professional nail salon in Singapore offering manicure, pedicure and nail care services",
            "address": {
                "@type": "PostalAddress",
                "streetAddress": "420 N Bridge Rd, #01-28 NORTH BRIDGE CENTRE",
                "addressLocality": "Singapore",
                "postalCode": "188727",
                "addressCountry": "SG"
            },
            "telephone": "+65 9173 7264"
        }
    </script>
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "name": "SUNNY NAILS",
            "alternateName": "SUNNY NAILS | Manicure & Pedicure Nail Salon in Singapore",
            "url": "<?php echo esc_url(home_url()); ?>",
            "description": "Professional nail salon in Singapore offering manicure, pedicure and nail care services",
            "publisher": {
                "@type": "Organization",
                "name": "SUNNY NAILS"
            }
        }
    </script>
    <title><?= wp_get_document_title() ?></title>
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <header id="masthead" class="site-header header-three">
        <div class="container">
            <?= logo('header_logo') ?>
            <div class="location">
                <span>Singapore</span>


            </div>

            <div class="menu-container">
                <?php echo do_shortcode('[sunny_login_icon device="mobile" margin="10"]'); ?>
                <button id="burger" class="burger">
                    <span class="bar bar--top"></span>
                    <span class="bar bar--middle"></span>
                    <span class="bar bar--bottom"></span>
                </button>
                <nav>
                    <?php wp_nav_menu([
                        'theme_location' => 'main-menu',
                        'container' => 'ul',
                    ]); ?>
                </nav>
                <?php echo do_shortcode('[sunny_login_icon device="desktop" margin="16"]'); ?>
                <div class="buttons">
                    <button type="button" class="btn yellow open-popup mini">Book an Appointment</button>
                </div>
            </div>
        </div>
    </header>
    <div class="burger-menu">
        <nav>
            <?php wp_nav_menu([
                'theme_location' => 'main-menu',
                'container' => 'ul',
            ]); ?>
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