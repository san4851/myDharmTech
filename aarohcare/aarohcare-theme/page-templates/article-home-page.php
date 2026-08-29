<?php
/**
 * Template Name: article-home-page
 *
 * Blog landing page: recent articles plus sidebar (recent posts and tag cloud).
 *
 * @package AarohCare
 */

get_header();

$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
$articles = new WP_Query([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 6,
    'paged'          => $paged,
]);
?>
  <main class="section-pad articles-page" id="articles">
    <div class="container">
      <div class="section-heading text-center mx-auto">
        <span class="eyebrow"><?php echo esc_html(aaroh_get('articles_eyebrow')); ?></span>
        <h1><?php echo esc_html(aaroh_get('articles_title')); ?></h1>
        <p><?php echo esc_html(aaroh_get('articles_intro')); ?></p>
      </div>
      <div class="row g-4 align-items-start">
        <div class="col-lg-8">
          <?php if ($articles->have_posts()) : ?>
            <div class="row g-4">
              <?php
              while ($articles->have_posts()) :
                  $articles->the_post();
                  get_template_part('template-parts/article-card');
              endwhile;
              ?>
            </div>
            <?php
            $pagination = paginate_links([
                'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                'format'    => '?paged=%#%',
                'total'     => (int) $articles->max_num_pages,
                'current'   => $paged,
                'type'      => 'array',
                'prev_text' => 'Previous',
                'next_text' => 'Next',
            ]);
            if ($pagination) :
                ?>
            <nav class="articles-pagination" aria-label="Articles">
              <ul class="pagination">
                <?php foreach ($pagination as $link) : ?>
                  <li class="page-item"><?php echo wp_kses_post($link); ?></li>
                <?php endforeach; ?>
              </ul>
            </nav>
                <?php
            endif;
            wp_reset_postdata();
            ?>
          <?php else : ?>
            <div class="article-card">
              <h2>No articles yet</h2>
              <p>New articles from Aaroh Care will appear here once they are published.</p>
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
