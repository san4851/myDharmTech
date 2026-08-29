<?php
/**
 * Tag / category archives reuse the articles landing layout.
 *
 * @package AarohCare
 */

get_header();
?>
  <main class="section-pad articles-page" id="articles">
    <div class="container">
      <div class="section-heading text-center mx-auto">
        <span class="eyebrow"><?php echo esc_html(aaroh_get('nav_articles')); ?></span>
        <h1><?php echo esc_html(wp_strip_all_tags(get_the_archive_title())); ?></h1>
        <?php the_archive_description('<p>', '</p>'); ?>
      </div>
      <div class="row g-4 align-items-start">
        <div class="col-lg-8">
          <?php if (have_posts()) : ?>
            <div class="row g-4">
              <?php
              while (have_posts()) :
                  the_post();
                  get_template_part('template-parts/article-card');
              endwhile;
              ?>
            </div>
            <?php
            the_posts_pagination([
                'prev_text' => 'Previous',
                'next_text' => 'Next',
            ]);
            ?>
          <?php else : ?>
            <div class="article-card">
              <h2>No articles found</h2>
              <p><a href="<?php echo esc_url(aaroh_articles_url()); ?>">Return to all articles</a></p>
            </div>
          <?php endif; ?>
        </div>
        <aside class="col-lg-4" aria-label="Article sidebar">
          <?php get_template_part('template-parts/articles-sidebar'); ?>
        </aside>
      </div>
    </div>
  </main>
<?php
get_footer();
