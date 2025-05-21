<?php
  $isPage = $args["page"] ?? false;
?>

<section class="contact-section<?= $isPage ? ' page' : '' ?>">
  <div class="container">
    <div class="contact-section__content">
      <div class="contact-section__text">
        <h2 class="title"><?php the_field('contact_title', 'option'); ?></h2>
        <p class="paragraph"><?php the_field('contact_text', 'option'); ?></p>
      </div>
      <?php
        foreach(get_field('contact_item', 'option') as $item) {
          $image = $item["contact_item_image"];
          $url = $image['url'];
          $title = $image['title'];
          $content = $item["contact_item_content"];
          $content_str = "";

          foreach($content as $str) {
            $content_text = $str["contact_item_content_text"];
            $content_link = $str["contact_item_content_link"];
            $content_subtext = $str["contact_item_content_subtext"];

            $tag_name = $content_link ? 'a' : 'span';
            $href_attr = $content_link ? "href='$content_link'" : "";
            $tag_subtext = $content_subtext ? "<span>$content_subtext</span>" : "";
            $target = $content_link ? "target='_blank'" : "";

            $content_str .= "<$tag_name $target $href_attr class='info-item__item'>
              <b>$content_text</b>
              $tag_subtext
            </$tag_name>";
          };

          echo "
            <div class='info-item'>
              <img src='$url' alt='$title'>
              <div class='info-item__content'>
                $content_str
              </div>
            </div>
          ";
        }
      ?>
    </div>
    <div class="contact-section__map">
      <?php
        $cords = json_encode([
          'lat' => +get_field('map_lat', 'option'),
          'lng' => +get_field('map_lng', 'option'),
        ]);
        
        $link = get_field('map_btn_url', 'option');
        $text = get_field('map_btn_text', 'option');
        if ($link && $text) {
          echo "<a href='$link' target='_blank' class='btn yellow'>$text</a>";
        }
      ?>
      <div data-token="<?php the_field('map_token', 'option'); ?>" data-center='<?= $cords; ?>' id="map"></div>
    </div>
  </div>
</section>