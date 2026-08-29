<?php
/**
 * Appointment form AJAX, storage, and email.
 *
 * @package AarohCare
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_aaroh_submit_appointment', 'aaroh_handle_appointment');
add_action('wp_ajax_nopriv_aaroh_submit_appointment', 'aaroh_handle_appointment');

function aaroh_handle_appointment()
{
    check_ajax_referer('aaroh_appointment', 'nonce');

    $name  = isset($_POST['patientName']) ? sanitize_text_field(wp_unslash($_POST['patientName'])) : '';
    $phone = isset($_POST['patientPhone']) ? sanitize_text_field(wp_unslash($_POST['patientPhone'])) : '';
    $email = isset($_POST['patientEmail']) ? sanitize_email(wp_unslash($_POST['patientEmail'])) : '';
    $issue = isset($_POST['healthIssue']) ? sanitize_textarea_field(wp_unslash($_POST['healthIssue'])) : '';
    $date  = isset($_POST['appointmentDate']) ? sanitize_text_field(wp_unslash($_POST['appointmentDate'])) : '';
    $time  = isset($_POST['appointmentTime']) ? sanitize_text_field(wp_unslash($_POST['appointmentTime'])) : '';

    $errors = [];

    if ($name === '') {
        $errors[] = 'name';
    }
    if ($phone === '' || !preg_match('/^[0-9+\-() ]{10,18}$/', $phone)) {
        $errors[] = 'phone';
    }
    if (!is_email($email)) {
        $errors[] = 'email';
    }
    if ($issue === '') {
        $errors[] = 'issue';
    }
    if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errors[] = 'date';
    } else {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dt || $dt->format('Y-m-d') !== $date) {
            $errors[] = 'date';
        } elseif ((int) $dt->format('w') === 0) {
            wp_send_json_error(['message' => 'Consultations are available Monday to Saturday only.'], 400);
        }
        $today = new DateTime('today');
        if ($dt < $today) {
            $errors[] = 'date';
        }
    }

    $slots = aaroh_lines('form_time_slots');
    if ($time === '' || !in_array($time, $slots, true)) {
        $errors[] = 'time';
    }

    if ($errors) {
        wp_send_json_error(['message' => 'Please check the appointment form and try again.', 'fields' => $errors], 400);
    }

    $post_id = wp_insert_post([
        'post_type'   => 'aaroh_appointment',
        'post_status' => 'private',
        'post_title'  => $name,
    ], true);

    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => 'Unable to save the appointment request.'], 500);
    }

    update_post_meta($post_id, '_aaroh_phone', $phone);
    update_post_meta($post_id, '_aaroh_email', $email);
    update_post_meta($post_id, '_aaroh_issue', $issue);
    update_post_meta($post_id, '_aaroh_date', $date);
    update_post_meta($post_id, '_aaroh_time', $time);
    update_post_meta($post_id, '_aaroh_status', 'pending');

    $clinic = aaroh_clinic_email();
    $subject = sprintf('New appointment request from %s', $name);

    $date_label = $date;
    $stamp      = strtotime($date . ' 12:00:00');
    if ($stamp) {
        $date_label = date_i18n('l, j F Y', $stamp);
    }

    $mail_data = [
        'name'       => $name,
        'phone'      => $phone,
        'email'      => $email,
        'issue'      => $issue,
        'date'       => $date,
        'date_label' => $date_label,
        'time'       => $time,
        'admin_url'  => admin_url('post.php?post=' . (int) $post_id . '&action=edit'),
    ];

    $headers = [];
    if (is_email($email)) {
        $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
    }

    aaroh_send_html_mail(
        $clinic,
        $subject,
        aaroh_appointment_email_html($mail_data),
        aaroh_appointment_email_plain($mail_data),
        $headers
    );

    wp_send_json_success([
        'message' => aaroh_appointment_success_message($name, $date, $time),
    ]);
}
