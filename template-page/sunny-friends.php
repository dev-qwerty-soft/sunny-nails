<?php
/**
 * Template Name: Sunny friends
 */
  get_header();
  $tabs = get_field('sunny_friends_tabs');
  $percent = 100;
  $baseColor = '253, 196, 31';
  $notEmpty = count($tabs) !== 0;
  if($notEmpty) {
    $percent = 100 / count($tabs);
  };
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
      <div class="sunny-friends-table-section__wrapper">
        <div class="sunny-friends-table-section__table">
          <div class="column main">
            <?php
              $title = get_field('sunny_friends_table_title');
              $items = get_field('sunny_friends_main_column_table');
              echo "<div class='cell name'>$title</div>";
              if($items && count($items) > 0 && is_array($items)) {
                foreach($items as $item) {
                  $text = isset($item['sunny_friends_main_column_table_text']) ? $item['sunny_friends_main_column_table_text'] : '';
                  echo "<div class='cell'>$text</div>";
                };
              }
            ?>
          </div>
          <?php
            $columns = get_field("sunny_friends_table_column");
            $totalColumns = count($columns);
            if($totalColumns !== 0 && is_array($columns) && $columns) {
              $i = 0;
              foreach ($columns as $column) {
                $alpha = 0.1 + ($i / max($totalColumns - 1, 1)) * 0.9;
                $alpha = round($alpha, 2) + 0.1;
                $title = isset($column['sunny_friends_table_column_title']) ? $column['sunny_friends_table_column_title'] : '';
                $fields = isset($column['sunny_friends_table_column_field']) ? $column['sunny_friends_table_column_field'] : [];
                $content = "";
                if($fields && count($fields) > 0 && is_array($fields)) {
                  foreach($fields as $field) {
                    $text = isset($field['sunny_friends_table_column_text']) ? $field['sunny_friends_table_column_text'] : '';
                    $isCheckClass = $field['sunny_friends_table_column_check_mark'] ? " check" : "";
                    $content .= "<div class='cell$isCheckClass'>$text</div>";
                  };
                };
                echo "<div class='column'>
                  <div class='cell label'>
                    <span style='background-color: rgba($baseColor, $alpha);'>$title</span>
                  </div>
                  $content
                </div>";
                $i++;
              };
            };
          ?>
        </div>
      </div>
      <a href="#" class="btn yellow">Join Sunny Friends</a>
    </div>
  </section>
</main>
<?php get_footer(); ?>