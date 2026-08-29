    <nav class="navbar navbar-expand-lg navbar-light py-3">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3" href="<?php echo esc_url(is_front_page() ? '#top' : home_url('/')); ?>" aria-label="<?php echo esc_attr(aaroh_get('jsonld_name') . ' home'); ?>">
          <span class="brand-mark">
            <?php aaroh_logo_img(['width' => 56, 'height' => 56]); ?>
          </span>
          <span class="brand-copy">
            <strong class="brand-name d-block"><?php echo esc_html(aaroh_get('brand_name')); ?></strong>
            <span class="brand-tagline"><?php echo esc_html(aaroh_get('brand_tagline')); ?></span>
          </span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primaryNav" aria-controls="primaryNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="toggler-bars" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
          </span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="primaryNav">
          <ul class="navbar-nav align-items-lg-center gap-lg-2">
            <li class="nav-item"><a class="nav-link" href="<?php echo esc_url(aaroh_section_url('#services')); ?>"><?php echo esc_html(aaroh_get('nav_services')); ?></a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo esc_url(aaroh_section_url('#benefits')); ?>"><?php echo esc_html(aaroh_get('nav_benefits')); ?></a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo esc_url(aaroh_section_url('#gallery')); ?>"><?php echo esc_html(aaroh_get('nav_gallery')); ?></a></li>
            <li class="nav-item"><a class="<?php echo esc_attr(aaroh_nav_link_class('articles')); ?>" href="<?php echo esc_url(aaroh_articles_url()); ?>"><?php echo esc_html(aaroh_get('nav_articles')); ?></a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo esc_url(aaroh_section_url('#faq')); ?>"><?php echo esc_html(aaroh_get('nav_faq')); ?></a></li>
            <li class="nav-item ms-lg-2"><a class="btn btn-brand" href="<?php echo esc_url(aaroh_section_url('#appointment')); ?>"><?php echo esc_html(aaroh_get('nav_cta')); ?></a></li>
          </ul>
        </div>
      </div>
    </nav>
