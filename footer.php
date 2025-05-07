<footer class="footer">
  <div class="container">
    <div class="footer__top">
      <div class="logo">
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
      <div class="icons">
        <?php
          foreach(getIcon() as $icon) {
            $text = $icon['text'];
            $image = $icon['image'];
            echo "<a target='_blank' rel='noopener noreferrer' href='$text'>
              <img src='$image' alt='image'>
            </a>";
          };
        ?>
      </div>
    </div>
    <div class="footer__bottom">
      <p class="copyright"><?= get_theme_mod('footer_copyright'); ?></p>
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