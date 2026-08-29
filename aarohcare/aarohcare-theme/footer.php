  <footer class="site-footer py-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
      <p class="mb-0"><?php echo esc_html(aaroh_get('footer_copy')); ?></p>
      <a href="<?php echo esc_url(aaroh_section_url('#appointment')); ?>" class="footer-link"><?php echo esc_html(aaroh_get('footer_link')); ?></a>
    </div>
  </footer>
  <?php wp_footer(); ?>
</body>
</html>
