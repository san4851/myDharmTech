    <section class="section-pad" id="gallery">
      <div class="container">
        <div class="section-heading text-center mx-auto">
          <span class="eyebrow"><?php echo esc_html(aaroh_get('gallery_eyebrow')); ?></span>
          <h2><?php echo esc_html(aaroh_get('gallery_title')); ?></h2>
          <p><?php echo esc_html(aaroh_get('gallery_intro')); ?></p>
        </div>
        <div class="row g-4">
          <?php
          $gallery = aaroh_query_ordered('aaroh_gallery');
          if ($gallery->have_posts()) :
              while ($gallery->have_posts()) :
                  $gallery->the_post();
                  ?>
          <div class="col-sm-6 col-xl-4">
            <figure class="gallery-card">
              <?php aaroh_gallery_image(get_the_ID()); ?>
              <figcaption>
                <h3><?php the_title(); ?></h3>
                <p><?php echo esc_html(get_the_excerpt()); ?></p>
              </figcaption>
            </figure>
          </div>
                  <?php
              endwhile;
              wp_reset_postdata();
          endif;
          ?>
        </div>
      </div>
    </section>
