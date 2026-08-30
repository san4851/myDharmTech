<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
  <header class="site-header">
    <?php get_template_part('template-parts/navigation'); ?>
    <?php if (is_front_page()) : ?>
      <?php get_template_part('template-parts/hero'); ?>
      <?php get_template_part('template-parts/consult-topics'); ?>
      <?php get_template_part('template-parts/articles-carousel'); ?>
    <?php endif; ?>
  </header>
