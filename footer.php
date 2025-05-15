<footer class="footer">
  <div class="container">
    <div class="footer__top">
      <?= logo('footer_logo'); ?>
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