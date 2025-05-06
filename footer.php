</main>
<footer class="footer">
  <div class="container">
    <div class="footer__logo">
      <?php
      $footer_logo = get_field('footer_logo', 'option');
      if ($footer_logo): ?>
        <img src="<?php echo esc_url($footer_logo['url']); ?>"
          alt="<?php echo esc_attr($footer_logo['alt']); ?>"
          draggable="false">
      <?php else:
        if (has_custom_logo()):
          the_custom_logo();
        endif;
      endif;
      ?>

    </div>

    <?php if (has_nav_menu('footer-menu')) { ?>
      <?php wp_nav_menu(array('theme_location' => 'footer-menu', 'depth' => 1)); ?>
    <?php } ?>

    <p class="copyright"><?php the_field('footer_copyright', 'option'); ?></p>
  </div>
</footer>
<div class="preloader">
  <svg
    class="preloader__image"
    role="img"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 512 512">
    <path
      fill="currentColor"
      d="M304 48c0 26.51-21.49 48-48 48s-48-21.49-48-48 21.49-48 48-48 48 21.49 48 48zm-48 368c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zm208-208c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zM96 256c0-26.51-21.49-48-48-48S0 229.49 0 256s21.49 48 48 48 48-21.49 48-48zm12.922 99.078c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.491-48-48-48zm294.156 0c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.49-48-48-48zM108.922 60.922c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.491-48-48-48z"></path>
  </svg>
</div>
<?php wp_footer(); ?>
</body>

</html>