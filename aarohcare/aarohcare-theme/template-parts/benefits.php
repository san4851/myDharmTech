    <section class="section-pad section-soft" id="benefits">
      <div class="container">
        <div class="row g-4 align-items-center">
          <div class="col-lg-5">
            <span class="eyebrow"><?php echo esc_html(aaroh_get('benefits_eyebrow')); ?></span>
            <h2><?php echo esc_html(aaroh_get('benefits_title')); ?></h2>
            <p><?php echo esc_html(aaroh_get('benefits_intro')); ?></p>
          </div>
          <div class="col-lg-7">
            <div class="row g-3">
              <?php
              $benefits = aaroh_query_ordered('aaroh_benefit');
              if ($benefits->have_posts()) :
                  while ($benefits->have_posts()) :
                      $benefits->the_post();
                      ?>
              <div class="col-md-6">
                <div class="benefit-card">
                  <h3><?php the_title(); ?></h3>
                  <p><?php echo esc_html(get_the_excerpt()); ?></p>
                </div>
              </div>
                      <?php
                  endwhile;
                  wp_reset_postdata();
              endif;
              ?>
            </div>
          </div>
        </div>
      </div>
    </section>
