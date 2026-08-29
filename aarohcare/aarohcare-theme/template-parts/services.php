    <section class="section-pad" id="services">
      <div class="container">
        <div class="section-heading text-center mx-auto">
          <span class="eyebrow"><?php echo esc_html(aaroh_get('services_eyebrow')); ?></span>
          <h2><?php echo esc_html(aaroh_get('services_title')); ?></h2>
          <p><?php echo esc_html(aaroh_get('services_intro')); ?></p>
        </div>
        <div class="row g-4">
          <?php
          $services = aaroh_query_ordered('aaroh_service');
          if ($services->have_posts()) :
              while ($services->have_posts()) :
                  $services->the_post();
                  $chip = get_post_meta(get_the_ID(), '_aaroh_chip', true);
                  ?>
          <div class="col-md-6 col-xl-4">
            <article class="service-card h-100">
              <h3><?php the_title(); ?></h3>
              <p><?php echo esc_html(get_post_field('post_content', get_the_ID())); ?></p>
              <?php if ($chip) : ?>
              <span class="service-chip"><?php echo esc_html($chip); ?></span>
              <?php endif; ?>
            </article>
          </div>
                  <?php
              endwhile;
              wp_reset_postdata();
          endif;
          ?>
        </div>
      </div>
    </section>
