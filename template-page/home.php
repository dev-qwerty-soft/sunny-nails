<?php

/**
 * Template Name: Home
 */
get_header();

$services = [
    "Women’s Manicure",
    "Women’s Pedicure",
    "Men’s Manicure",
    "Men’s Pedicure",
    "Children’s Manicure",
    "Children’s Pedicure"
];

$reviews = [
    [
        "text" => "I had an amazing experience at Sunny Nails! The staff is super friendly, and the manicure was flawless. The salon is clean and cozy. Definitely coming back!",
        "name" => "Sunny Inferno",
        "date" => "3 days ago",
        "image" => getUrl("images/image.png"),
    ],
    [
        "text" => "I had an amazing experience at Sunny Nails! The staff is super friendly, and the manicure was flawless. The salon is clean and cozy. Definitely coming back!",
        "name" => "Sunny Inferno",
        "date" => "3 days ago",
        "image" => getUrl("images/image.png"),
    ],
    [
        "text" => "I had an amazing experience at Sunny Nails! The staff is super friendly, and the manicure was flawless. The salon is clean and cozy. Definitely coming back!",
        "name" => "Sunny Inferno",
        "date" => "3 days ago",
        "image" => getUrl("images/image.png"),
    ],
    [
        "text" => "I had an amazing experience at Sunny Nails! The staff is super friendly, and the manicure was flawless. The salon is clean and cozy. Definitely coming back!",
        "name" => "Sunny Inferno",
        "date" => "3 days ago",
        "image" => getUrl("images/image.png"),
    ],
    [
        "text" => "I had an amazing experience at Sunny Nails! The staff is super friendly, and the manicure was flawless. The salon is clean and cozy. Definitely coming back!",
        "name" => "Sunny Inferno",
        "date" => "3 days ago",
        "image" => getUrl("images/image.png"),
    ],
    [
        "text" => "I had an amazing experience at Sunny Nails! The staff is super friendly, and the manicure was flawless. The salon is clean and cozy. Definitely coming back!",
        "name" => "Sunny Inferno",
        "date" => "3 days ago",
        "image" => getUrl("images/image.png"),
    ],
];

?>
<main>
    <section class="hero-section">
        <div class="container">
            <div class="hero-section__top">
                <h1 class="title">
                    <?php the_field('hero_title'); ?>
                </h1>
                <div class="hero-section__buttons">
                    <button type="button open-popup" class="btn yellow">Free Manicure</button>
                    <?php
                        $link = get_field('hero_link');
                        $text = get_field('hero_link_text');
                        if($link && $text) {
                            echo "<a href='$link' class='btn'>$text</a>";
                        }
                    ?>
                </div>
            </div>
            <div class="swiper hero-swiper button-container">
                <div class="swiper-wrapper">
                    <?php
                    foreach (get_field('hero_slides') as $slide) {
                        $img = $slide["url"];
                        $title = $slide["title"];
                        echo "<div class='swiper-slide'>
                            <img src='$img' alt='$title'>
                        </div>";
                    }
                    ?>
                </div>
                <div class="swiper-pagination"></div>
                <button type="button" aria-label="Next slide" class="button swiper-button-next"></button>
                <button type="button" aria-label="Previous slide" class="button swiper-button-prev"></button>
            </div>
        </div>
    </section>
    <section class="reasons-section">
        <div class="container">
            <h2 class="title"><?php the_field('choose_title'); ?></h2>
            <div class="reasons-section__items">
                <?php
                foreach (get_field('choose_cards') as $card) {
                    $text = $card["card_text"];
                    $image = $card["card_image"];
                    $url = $image["url"];
                    $title = $image["title"];
                    echo "<div class='item'>
              <img src='$url' alt='$title'>
              <span>$text</span>
            </div>";
                };
                ?>
            </div>
        </div>
    </section>
    <?php
        get_template_part("template-parts/gallery/gallery-grid", null, [
            "full" => false
        ]);
    ?>
    <section class="services-preview-section">
        <div class="container">
            <div class="services-preview-section__top">
                <h2 class="title"><?php the_field('services_title', 'option'); ?></h2>
                <?php
                    $url = get_field('services_link_url', 'option');
                    $text = get_field('services_link_text', 'option');
                    if($url && $text) {
                        echo "<a href='$url' class='btn yellow'>$text</a>";
                    }
                ?>
            </div>
            <div class="services-preview-section__items">
                <?php
                    $index = 1;
                    foreach ($services as $service) {
                        $indexPretty = $index < 9 ? "0$index" : $index;
                        echo "<div class='item'>
                            <span class='item__number'>/$indexPretty</span>
                            <span class='item__title'>$service</span>
                            <span class='item__arrow'></span>
                        </div>";
                        $index++;
                    };
                ?>
            </div>
        </div>
    </section>
    <section class="reviews-section">
        <div class="container">
            <div class="reviews-section__top">
                <h2 class="title">What Our Clients Say</h2>
                <a href="#" class="btn white">Leave a Review</a>
            </div>
            <div class="reviews-section__wrapper button-container">
                <div class="swiper reviews-swiper">
                    <div class="swiper-wrapper">
                        <?php
                            foreach ($reviews as $slide) {
                                $image = $slide["image"];
                                $date = $slide["date"];
                                $name = $slide["name"];
                                $text = $slide["text"];
                                echo "<div class='swiper-slide'>
                                    <div class='review'>
                                        <div class='review__message'>
                                            <div class='review__rate'>
                                                <div class='star'></div>
                                                <div class='star'></div>
                                                <div class='star'></div>
                                                <div class='star'></div>
                                                <div class='star'></div>
                                            </div>
                                            <p>$text</p>
                                        </div>
                                        <div class='review__info'>
                                            <img src='$image' alt='$name'>
                                            <span class='review__name'>$name</span>
                                            <span class='review__date'>$date</span>
                                        </div>
                                    </div>
                                </div>";
                            };
                        ?>
                    </div>
                </div>
                <button type="button" aria-label="Next slide" class="button swiper-button-next"></button>
                <button type="button" aria-label="Previous slide" class="button swiper-button-prev"></button>
            </div>
        </div>
    </section>
    <?php
        get_template_part("template-parts/sections/contact");
    ?>
</main>
<?php get_footer(); ?>