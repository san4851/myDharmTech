<?php
/**
 * Single article.
 *
 * @package AarohCare
 */

get_header();
?>
  <main class="section-pad articles-page" id="article">
    <div class="container">
      <div class="row g-4 align-items-start">
        <div class="col-lg-8">
          <?php
          if (have_posts()) :
              while (have_posts()) :
                  the_post();
                  ?>
          <article <?php post_class('article-single'); ?>>
            <span class="eyebrow"><?php echo esc_html(aaroh_get('nav_articles')); ?></span>
            <h1><?php echo esc_html(get_the_title()); ?></h1>
            <p class="article-meta"><?php echo esc_html(get_the_date()); ?></p>
            <?php if (has_post_thumbnail()) : ?>
              <figure class="article-hero-image">
                <?php the_post_thumbnail('large', ['class' => 'img-fluid', 'alt' => get_the_title()]); ?>
              </figure>
            <?php endif; ?>
            <div class="article-content">
              <?php the_content(); ?>
            </div>
            <?php
            $tags = get_the_tag_list('', ' ');
            if ($tags) :
                ?>
            <div class="article-tags">
              <?php echo wp_kses_post($tags); ?>
            </div>
                <?php
            endif;
            ?>
            <p class="mb-0"><a class="btn btn-outline-brand" href="<?php echo esc_url(aaroh_articles_url()); ?>">Back to articles</a></p>
          </article>
                  <?php
              endwhile;
          endif;
          ?>
        </div>
        <aside class="col-lg-4" aria-label="Article sidebar">
          <?php get_template_part('template-parts/articles-sidebar'); ?>
        </aside>
      </div>
    </div>
  </main>
<?php
get_footer();
