          <div class="col-12 col-md-6">
            <article <?php post_class('article-card h-100'); ?>>
              <?php if (has_post_thumbnail()) : ?>
                <a class="article-card-media" href="<?php the_permalink(); ?>">
                  <?php the_post_thumbnail('large', ['class' => 'img-fluid', 'alt' => get_the_title()]); ?>
                </a>
              <?php endif; ?>
              <div class="article-card-body">
                <span class="article-meta"><?php echo esc_html(get_the_date()); ?></span>
                <h2 class="h3"><a href="<?php the_permalink(); ?>"><?php echo esc_html(get_the_title()); ?></a></h2>
                <p><?php echo esc_html(wp_trim_words(get_the_excerpt() ? get_the_excerpt() : wp_strip_all_tags(get_the_content()), 28)); ?></p>
                <a class="btn btn-outline-brand" href="<?php the_permalink(); ?>">Read article</a>
              </div>
            </article>
          </div>
