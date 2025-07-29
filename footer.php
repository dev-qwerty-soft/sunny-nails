<footer class="footer">
  <div class="container">
    <div class="footer__top">
      <div class="footer__logo">
        <?= logo('footer_logo') ?>
        <?php if (has_nav_menu('footer-menu')) { ?>
          <?php wp_nav_menu([
            'theme_location' => 'footer-menu',
            'depth' => 1,
            'menu_class' => 'footer__menu',
          ]); ?>
        <?php } ?>
      </div>
      <div class="icons">
        <?php displayIcon(); ?>
      </div>
    </div>
    <div class="footer__bottom">
      <p class="copyright"><?= get_field('footer_copyright', 'option') ?></p>

      <?php if (has_nav_menu('footer-menu-aside')) { ?>
        <?php wp_nav_menu([
          'theme_location' => 'footer-menu-aside',
          'depth' => 1,
          'menu_class' => 'footer__menu__aside',
        ]); ?>
      <?php } ?>
    </div>
  </div>
</footer>
<script>
  const themeUrl = "<?= get_template_directory_uri() ?>";
</script>
<?php wp_footer(); ?>
</body>

</html>