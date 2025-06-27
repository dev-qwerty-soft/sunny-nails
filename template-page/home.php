<?php

/**
 * Template Name: Home
 */
get_header();

$ordered_category_ids = [];

if (function_exists('get_field')) {
    $ordered_category_ids = get_field('category_selection');
}

if (empty($ordered_category_ids)) {
    $service_categories = get_terms([
        'taxonomy' => 'service_category',
        'hide_empty' => true,
        'order' => 'DESC'
    ]);
} else {
    $service_categories = [];
    foreach ($ordered_category_ids as $cat_id) {
        $term = get_term($cat_id, 'service_category');
        if (!is_wp_error($term) && !empty($term)) {
            $service_categories[] = $term;
        }
    }
};

function reviews_item() {
    $rating = number_format(getPlaceReviews()["rating"], 1);
    $stars = str_repeat("<div class='star'></div>", $rating);
    $link = get_field('reviews_link_url', 'option');
    return "<a target='_blank' rel='noopener noreferrer' href='$link' class='rating-new-item'>
        <span class='rating-new-item__title'>Average Referral Rating</span>
        <div class='rating-new-item__bottom'>
            <span class='rating-new-item__number'>$rating</span>
            <div class='rating-new-item__stars'>$stars</div>
        </div>
    </a>";
};

?>
<main>
    <section class="hero-section">
        <div class="container">
            <div class="hero-section__top">
                <h1 class="title">
                    <?php the_field('hero_title'); ?>
                </h1>
                <div class="hero-section__buttons">
                    <button type="button" class="btn yellow open-popup">Book an Appointment</button>
                    <?php
                    $link = get_field('hero_link');
                    $text = get_field('hero_link_text');
                    if ($link && $text) {
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
    <?php
    $services_link = get_field('services_link_url', 'option');
    $services_link_url = $services_link['url'] ?? '';
    $services_link_target = $services_link['target'] ?? '_self';
    $services_link_title = $services_link['title'] ?? '';
    ?>
    <section class="services-preview-section">
        <div class="container">
            <div class="services-preview-section__top">
                <h2 class="title"><?php the_field('services_title', 'option'); ?></h2>
                <?php if ($services_link_url && $services_link_title) : ?>
                    <a href="<?= esc_url($services_link_url); ?>" target="<?= esc_attr($services_link_target); ?>" class="btn yellow">
                        <?= esc_html($services_link_title); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="services-preview-section__items">
                <?php
                $index = 1;
                foreach ($service_categories as $service) {
                    $name = $service->name;
                    $indexPretty = $index < 9 ? "0$index" : $index;
                    echo "<a href='" . esc_url($services_link_url) . "' target='" . esc_attr($services_link_target) . "' class='item'>
                        <span class='item__number'>/$indexPretty</span>
                        <span class='item__title'>" . esc_html($name) . "</span>
                        <span class='item__arrow'></span>
                    </a>";
                    $index++;
                }
                ?>
            </div>
        </div>
    </section>

    <?php
    get_template_part("template-parts/sections/form");
    get_template_part("template-parts/sections/team");
    ?>
    <section class="reviews-section">
        <div class="container">
            <div class="reviews-section__top">
                <h2 class="title">What Our Clients Say</h2>
                <?php
                $text = get_field('reviews_link_text', 'option');
                $link = get_field('reviews_link_url', 'option');
                if ($link && $text) {
                    echo "<a target='_blank' rel='noopener noreferrer' href='$link' class='btn white'>$text</a>";
                }
                ?>
            </div>
            <div class="reviews-section__wrapper button-container">
                <div class="swiper reviews-swiper">
                    <div class="swiper-wrapper">
                        <?php
                        $reviews = get_option('selected_google_reviews_data', []);
                        foreach ($reviews as $slide) {
                            $image = $slide["profile_photo_url"];
                            $date = $slide["relative_time_description"];
                            $name = $slide["author_name"];
                            $text = $slide["text"];
                            $rating = $slide["rating"];
                            $stars = str_repeat("<div class='star'></div>", $rating);
                            echo "<div class='swiper-slide'>
                                        <div class='review'>
                                            <div class='review__message'>
                                                <div class='review__rate'>
                                                    $stars
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