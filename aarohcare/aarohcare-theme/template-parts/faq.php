    <section class="section-pad" id="faq">
      <div class="container">
        <div class="section-heading text-center mx-auto">
          <span class="eyebrow"><?php echo esc_html(aaroh_get('faq_eyebrow')); ?></span>
          <h2><?php echo esc_html(aaroh_get('faq_title')); ?></h2>
        </div>
        <div class="accordion faq-accordion" id="faqAccordion">
          <?php
          $faqs  = aaroh_query_ordered('aaroh_faq');
          $index = 0;
          if ($faqs->have_posts()) :
              while ($faqs->have_posts()) :
                  $faqs->the_post();
                  $collapse_id = 'faqItem' . get_the_ID();
                  $is_first    = 0 === $index;
                  $index++;
                  ?>
          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button<?php echo $is_first ? '' : ' collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr($collapse_id); ?>" aria-expanded="<?php echo $is_first ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr($collapse_id); ?>">
                <?php the_title(); ?>
              </button>
            </h3>
            <div id="<?php echo esc_attr($collapse_id); ?>" class="accordion-collapse collapse<?php echo $is_first ? ' show' : ''; ?>" data-bs-parent="#faqAccordion">
              <div class="accordion-body"><?php echo esc_html(get_post_field('post_content', get_the_ID())); ?></div>
            </div>
          </div>
                  <?php
              endwhile;
              wp_reset_postdata();
          endif;
          ?>
        </div>
      </div>
    </section>
