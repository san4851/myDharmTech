<?php
$carousel = new WP_Query([
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 10,
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
]);

if (!$carousel->have_posts()) {
    return;
}
?>
    <section class="article-carousel-section" id="home-articles" aria-label="<?php echo esc_attr(aaroh_get('articles_carousel_title')); ?>">
      <div class="article-carousel-shell">
        <div class="article-carousel-head">
          <div>
            <span class="eyebrow"><?php echo esc_html(aaroh_get('articles_carousel_eyebrow')); ?></span>
            <h2><?php echo esc_html(aaroh_get('articles_carousel_title')); ?></h2>
          </div>
          <div class="article-carousel-actions">
            <a class="footer-link" href="<?php echo esc_url(aaroh_articles_url()); ?>"><?php echo esc_html(aaroh_get('articles_carousel_link')); ?></a>
            <div class="article-carousel-nav">
              <button type="button" class="article-carousel-btn article-carousel-prev" aria-label="Previous articles">
                <span aria-hidden="true">&larr;</span>
              </button>
              <button type="button" class="article-carousel-btn article-carousel-next" aria-label="Next articles">
                <span aria-hidden="true">&rarr;</span>
              </button>
            </div>
          </div>
        </div>
        <div class="article-carousel" data-article-carousel>
          <div class="article-carousel-track">
            <?php
            while ($carousel->have_posts()) :
                $carousel->the_post();
                ?>
            <article class="article-carousel-slide article-card">
              <a class="article-card-media" href="<?php the_permalink(); ?>">
                <?php if (has_post_thumbnail()) : ?>
                  <?php the_post_thumbnail('medium_large', ['class' => 'img-fluid', 'alt' => get_the_title()]); ?>
                <?php else : ?>
                  <img src="<?php echo esc_url(aaroh_asset('img/og-clinic-care.svg')); ?>" class="img-fluid" alt="<?php echo esc_attr(get_the_title()); ?>">
                <?php endif; ?>
              </a>
              <div class="article-card-body">
                <span class="article-meta"><?php echo esc_html(get_the_date()); ?></span>
                <h3><a href="<?php the_permalink(); ?>"><?php echo esc_html(get_the_title()); ?></a></h3>
                <p><?php echo esc_html(wp_trim_words(get_the_excerpt() ? get_the_excerpt() : wp_strip_all_tags(get_the_content()), 18)); ?></p>
                <a class="btn btn-outline-brand" href="<?php the_permalink(); ?>">Read article</a>
              </div>
            </article>
                <?php
            endwhile;
            wp_reset_postdata();
            ?>
          </div>
        </div>
      </div>
    </section>
