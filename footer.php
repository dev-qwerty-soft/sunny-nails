<footer class="footer">
  <div class="container">
    <div class="footer__top">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
        <?php
          $logo = get_field('footer_logo', 'option');
          $url = isset($logo['url']) ? $logo['url'] : null;
          $alt = isset($logo['title']) ? $logo['title'] : null;
          if ($url && $alt) {
            echo "<img src='$url' alt='$alt'>";
          };
        ?>
      </a>
      <div class="icons">
        <?php displayIcon(); ?>
      </div>
    </div>
    <div class="footer__bottom">
      <p class="copyright"><?= get_field('footer_copyright', 'option'); ?></p>
      <?php if (has_nav_menu('footer-menu')) { ?>
        <?php wp_nav_menu(array('theme_location' => 'footer-menu', 'depth' => 1, 'menu_class' => 'footer__menu')); ?>
      <?php } ?>
      <?php if (has_nav_menu('footer-menu-aside')) { ?>
        <?php wp_nav_menu(array('theme_location' => 'footer-menu-aside', 'depth' => 1, 'menu_class' => 'footer__menu__aside')); ?>
      <?php } ?>
    </div>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>