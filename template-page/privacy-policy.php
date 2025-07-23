<?php
/**
 * Template Name: Privacy Policy
 */
get_header(); ?>
<main>
  <section class="privacy-policy-section">
    <div class="container">
      <div class="privacy-policy-section__top">
        <h1 class="title"><?= trim(get_the_title()) ?></h1>
        <p class="paragraph">Last updated: <?= get_the_modified_date() ?></p>
      </div>
      <div class="privacy-policy-section__blocks">
        <?php foreach (get_field('privacy_policy_block') as $item) {
          $title = $item['privacy_policy_block_title'];
          $content = $item['privacy_policy_block_content'];
          $contentWithOutEmpty = preg_replace('/<p>&nbsp;<\/p>/', '', $content);
          echo "<div class='privacy-policy-section__block'>
              <h2>$title</h2>
              <div class='content'>$contentWithOutEmpty</div>
            </div>";
        } ?>
      </div>
    </div>
  </section>
</main>
<?php get_footer(); ?>
