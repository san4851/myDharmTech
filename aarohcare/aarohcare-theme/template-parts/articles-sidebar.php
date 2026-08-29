          <div class="article-sidebar">
            <section class="sidebar-card">
              <h2>Recent posts</h2>
              <ul class="list-unstyled recent-posts mb-0">
                <?php
                $recent = new WP_Query([
                    'post_type'           => 'post',
                    'post_status'         => 'publish',
                    'posts_per_page'      => 5,
                    'ignore_sticky_posts' => true,
                    'post__not_in'        => is_singular('post') ? [get_the_ID()] : [],
                ]);
                if ($recent->have_posts()) :
                    while ($recent->have_posts()) :
                        $recent->the_post();
                        ?>
                <li>
                  <a href="<?php the_permalink(); ?>"><?php echo esc_html(get_the_title()); ?></a>
                  <span><?php echo esc_html(get_the_date()); ?></span>
                </li>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                <li>No posts published yet.</li>
                    <?php
                endif;
                ?>
              </ul>
            </section>
            <section class="sidebar-card">
              <h2>Tags</h2>
              <div class="tag-cloud">
                <?php
                $cloud = wp_tag_cloud([
                    'smallest' => 0.82,
                    'largest'  => 1.05,
                    'unit'     => 'rem',
                    'echo'     => false,
                    'taxonomy' => 'post_tag',
                ]);
                echo $cloud ? wp_kses_post($cloud) : '<p class="mb-0">Tags will appear here as articles are tagged.</p>';
                ?>
              </div>
            </section>
          </div>
