<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <?php wp_head(); 
        $data = getPlaceReviews("ChIJN1t_tDeuEmsRUsoyG83frY4", "AIzaSyDM4NT14KUDqLh66ExmnsXBzHWLh-wavPA");
        // dump($data["reviews"][0]);
    ?>
</head>
<body <?php body_class(); ?>>
<header id="masthead" class="site-header header-three">
    <div class="container">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
            <?php
                $logo = get_field('header_logo', 'option');
                $url = isset($logo['url']) ? $logo['url'] : null;
                $alt = isset($logo['alt']) ? $logo['alt'] : null;
                if ($url && $alt) {
                    echo "<img src='$url' alt='$alt'>";
                };
            ?>
        </a>
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
        <?php displayIcon(); ?>
    </div>
    <a href="#" class="btn white">Book an Appointment</a>
</div>
<?php
    $chat_link_url = get_field('chat_link_url', 'option');
    if($chat_link_url) {
        echo "<a target='_blank' rel='noopener noreferrer' href='$chat_link_url' class='chat'>
            <span>Chat</span>
        </a>";
    }
?>