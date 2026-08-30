    <section class="consult-topics-section" id="consult-topics" aria-label="<?php echo esc_attr(aaroh_get('consult_topics_title')); ?>">
      <div class="consult-topics-shell">
        <div class="consult-topics-box">
          <h2><?php echo esc_html(aaroh_get('consult_topics_title')); ?></h2>
          <div class="topic-pills">
            <?php foreach (aaroh_lines('consult_topics_items') as $topic) : ?>
              <span><?php echo esc_html($topic); ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>
