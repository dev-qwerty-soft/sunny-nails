<?php
/**
 * Template Name: Sunny friends
 */
  get_header();
  $tabs = get_field('sunny_friends_tabs');
  $percent = 100;
  $notEmpty = count($tabs) !== 0;
  if($notEmpty) {
    $percent = 100 / count($tabs);
  }
?>
<main>
  <section class="sunny-friends-section">
    <div class="container">
      <div class="sunny-friends-section__text">
        <h1 class="title"><?php the_field('sunny_friends_title'); ?></h1>
        <p class="paragraph"><?php the_field('sunny_friends_text'); ?></p>
      </div>
      <?php
        $image = get_field('sunny_friends_image');
        $url = isset($image['url']) ? $image['url'] : null;
        $title = isset($image['title']) ? $image['title'] : null;
        if($url && $title) {
          echo "<div class='sunny-friends-section__image'><img src='$url' alt='$title'></div>";
        }
      ?>
    </div>
  </section>
  <section class="sunny-friends-table-section">
    <div class="container">
      <div class="sunny-friends-table-section__top">
        <div class="sunny-friends-table-section__buttons" style="<?= "--offset-width: $percent%"; ?>">
          <?php
            if($notEmpty) {
              $i = 0;
              foreach($tabs as $button) {
                $class = $i === 0 ? ' active' : '';
                $name = $button['sunny_friends_tab_name'];
                echo "<button data-index='$i' class='sunny-friends-table-section__button$class'>$name</button>";
                $i++;
              };
            };
          ?>
        </div>
        <div class="sunny-friends-table-section__tabs">
          <?php
            if($notEmpty) {
              $i = 0;
              foreach($tabs as $tab) {
                $class = $i === 0 ? ' active' : '';
                echo "<div data-index='$i' class='sunny-friends-table-section__tab$class'>";
                $content = $tab['sunny_friends_tab_content'];
                foreach($content as $item) {
                  $image = $item['sunny_friends_tab_content_image'];
                  $url = isset($image['url']) ? $image['url'] : null;
                  $title = isset($image['title']) ? $image['title'] : null;
                  $text = $item['sunny_friends_tab_content_text'];
                  if($url && $title) {
                    echo "<div class='item'>
                      <img src='$url' alt='$title'>
                      <span>$text</span>
                    </div>";
                  }
                };
                echo '</div>';
                $i++;
              };
            };
          ?>
        </div>
      </div>
    </div>
  </section>
</main>
<?php get_footer(); ?>