    <section class="section-pad section-soft" id="appointment">
      <div class="container">
        <div class="row g-5 align-items-start">
          <div class="col-lg-5">
            <span class="eyebrow"><?php echo esc_html(aaroh_get('appt_eyebrow')); ?></span>
            <h2><?php echo esc_html(aaroh_get('appt_title')); ?></h2>
            <p><?php echo esc_html(aaroh_get('appt_intro')); ?></p>
            <div class="appointment-note">
              <h3><?php echo esc_html(aaroh_get('appt_note_title')); ?></h3>
              <ul class="mb-0">
                <?php foreach (aaroh_lines('appt_note_items') as $item) : ?>
                  <li><?php echo esc_html($item); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <div class="col-lg-7">
            <div class="appointment-card">
              <form class="row g-3 needs-validation" id="appointmentForm" novalidate>
                <div class="col-md-6">
                  <label class="form-label" for="patientName"><?php echo esc_html(aaroh_get('form_name_label')); ?></label>
                  <input class="form-control" type="text" id="patientName" name="patientName" placeholder="<?php echo esc_attr(aaroh_get('form_name_placeholder')); ?>" required>
                  <div class="invalid-feedback"><?php echo esc_html(aaroh_get('form_name_error')); ?></div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="patientPhone"><?php echo esc_html(aaroh_get('form_phone_label')); ?></label>
                  <input class="form-control" type="tel" id="patientPhone" name="patientPhone" placeholder="<?php echo esc_attr(aaroh_get('form_phone_placeholder')); ?>" pattern="[0-9+\-() ]{10,18}" required>
                  <div class="invalid-feedback"><?php echo esc_html(aaroh_get('form_phone_error')); ?></div>
                </div>
                <div class="col-12">
                  <label class="form-label" for="patientEmail"><?php echo esc_html(aaroh_get('form_email_label')); ?></label>
                  <input class="form-control" type="email" id="patientEmail" name="patientEmail" placeholder="<?php echo esc_attr(aaroh_get('form_email_placeholder')); ?>" required>
                  <div class="invalid-feedback"><?php echo esc_html(aaroh_get('form_email_error')); ?></div>
                </div>
                <div class="col-12">
                  <label class="form-label" for="healthIssue"><?php echo esc_html(aaroh_get('form_issue_label')); ?></label>
                  <textarea class="form-control" id="healthIssue" name="healthIssue" rows="4" placeholder="<?php echo esc_attr(aaroh_get('form_issue_placeholder')); ?>" required></textarea>
                  <div class="invalid-feedback"><?php echo esc_html(aaroh_get('form_issue_error')); ?></div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="appointmentDate"><?php echo esc_html(aaroh_get('form_date_label')); ?></label>
                  <input class="form-control" type="date" id="appointmentDate" name="appointmentDate" required>
                  <div class="invalid-feedback"><?php echo esc_html(aaroh_get('form_date_error')); ?></div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="appointmentTime"><?php echo esc_html(aaroh_get('form_time_label')); ?></label>
                  <select class="form-select" id="appointmentTime" name="appointmentTime" required>
                    <option value="" selected disabled><?php echo esc_html(aaroh_get('form_time_placeholder')); ?></option>
                    <?php foreach (aaroh_lines('form_time_slots') as $slot) : ?>
                      <option><?php echo esc_html($slot); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback"><?php echo esc_html(aaroh_get('form_time_error')); ?></div>
                </div>
                <div class="col-12">
                  <button class="btn btn-brand btn-lg w-100" type="submit"><?php echo esc_html(aaroh_get('appt_submit')); ?></button>
                </div>
              </form>
              <div class="alert alert-success mt-4 d-none" id="appointmentSuccess" role="status" aria-live="polite"></div>
            </div>
          </div>
        </div>
      </div>
    </section>
