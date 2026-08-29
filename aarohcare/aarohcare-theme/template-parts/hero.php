    <section class="hero-section" id="top">
      <div class="container">
        <div class="row align-items-center g-5">
          <div class="col-lg-6">
            <span class="eyebrow"><?php echo esc_html(aaroh_get('hero_eyebrow')); ?></span>
            <h1><?php echo esc_html(aaroh_get('hero_title')); ?></h1>
            <p class="lead"><?php echo esc_html(aaroh_get('hero_lead')); ?></p>
            <div class="hero-actions d-flex flex-wrap gap-3">
              <a class="btn btn-brand btn-lg" href="#appointment"><?php echo esc_html(aaroh_get('hero_cta_primary')); ?></a>
              <a class="btn btn-outline-brand btn-lg" href="#services"><?php echo esc_html(aaroh_get('hero_cta_secondary')); ?></a>
            </div>
            <ul class="hero-metrics list-unstyled row g-3 mb-0">
              <li class="col-sm-4">
                <div class="metric-card">
                  <strong><?php echo esc_html(aaroh_get('metric_1_value')); ?></strong>
                  <span><?php echo esc_html(aaroh_get('metric_1_label')); ?></span>
                </div>
              </li>
              <li class="col-sm-4">
                <div class="metric-card">
                  <strong><?php echo esc_html(aaroh_get('metric_2_value')); ?></strong>
                  <span><?php echo esc_html(aaroh_get('metric_2_label')); ?></span>
                </div>
              </li>
              <li class="col-sm-4">
                <div class="metric-card">
                  <strong><?php echo esc_html(aaroh_get('metric_3_value')); ?></strong>
                  <span><?php echo esc_html(aaroh_get('metric_3_label')); ?></span>
                </div>
              </li>
            </ul>
          </div>
          <div class="col-lg-6">
            <div class="hero-showcase">
              <div class="hero-showcase-top">
                <?php aaroh_logo_img(['class' => 'hero-showcase-logo', 'width' => 96, 'height' => 96, 'alt' => 'Aaroh Care clinic logo']); ?>
                <div>
                  <p class="panel-label"><?php echo esc_html(aaroh_get('showcase_label')); ?></p>
                  <h2><?php echo esc_html(aaroh_get('showcase_title')); ?></h2>
                </div>
              </div>
              <div class="hero-showcase-grid">
                <article class="hero-showcase-card hero-showcase-card-primary">
                  <p class="panel-label"><?php echo esc_html(aaroh_get('showcase_card_1_label')); ?></p>
                  <ul class="list-unstyled mb-0">
                    <?php foreach (aaroh_lines('showcase_card_1_body') as $line) : ?>
                      <li><?php echo esc_html($line); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </article>
                <article class="hero-showcase-card">
                  <p class="panel-label"><?php echo esc_html(aaroh_get('showcase_card_2_label')); ?></p>
                  <ul class="list-unstyled mb-0">
                    <?php foreach (aaroh_lines('showcase_card_2_body') as $line) : ?>
                      <li><?php echo esc_html($line); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </article>
                <article class="hero-showcase-card hero-showcase-card-accent">
                  <p class="panel-label"><?php echo esc_html(aaroh_get('showcase_card_3_label')); ?></p>
                  <h3><?php echo esc_html(aaroh_get('showcase_card_3_title')); ?></h3>
                  <p class="mb-0"><?php echo esc_html(aaroh_get('showcase_card_3_body')); ?></p>
                </article>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
