<?php
/**
 * HTML email helpers matching the Aaroh Care theme.
 *
 * @package AarohCare
 */

if (!defined('ABSPATH')) {
    exit;
}

function aaroh_mail_altbody_set($phpmailer)
{
    if (!empty($GLOBALS['aaroh_mail_altbody'])) {
        $phpmailer->AltBody = $GLOBALS['aaroh_mail_altbody'];
    }
}

function aaroh_send_html_mail($to, $subject, $html, $plain, $headers = [])
{
    $headers   = (array) $headers;
    $headers[] = 'Content-Type: text/html; charset=UTF-8';

    $GLOBALS['aaroh_mail_altbody'] = $plain;
    add_action('phpmailer_init', 'aaroh_mail_altbody_set');
    $sent = wp_mail($to, $subject, $html, $headers);
    remove_action('phpmailer_init', 'aaroh_mail_altbody_set');
    unset($GLOBALS['aaroh_mail_altbody']);

    return $sent;
}

function aaroh_appointment_email_plain($data)
{
    return "AAROH CARE - HOMOEOPATHY\n"
        . "New online consultation request\n\n"
        . "Name: {$data['name']}\n"
        . "Phone: {$data['phone']}\n"
        . "Email: {$data['email']}\n"
        . "Preferred date: {$data['date_label']}\n"
        . "Preferred time: {$data['time']}\n\n"
        . "Health issue:\n{$data['issue']}\n";
}

function aaroh_appointment_email_html($data)
{
    $forest = '#146b2f';
    $cream  = '#fbf5e8';
    $clay   = '#b6922e';
    $ink    = '#173b24';
    $muted  = '#4e664f';
    $logo   = esc_url(aaroh_asset('img/AarohCareLogo.jpeg'));
    $brand  = esc_html(aaroh_get('brand_name'));
    $tag    = esc_html(aaroh_get('brand_tagline'));
    $admin  = !empty($data['admin_url']) ? esc_url($data['admin_url']) : '';

    $rows = [
        'Full name'        => $data['name'],
        'Contact number'   => $data['phone'],
        'Email address'    => $data['email'],
        'Preferred date'   => $data['date_label'],
        'Preferred time'   => $data['time'],
    ];

    $details = '';
    foreach ($rows as $label => $value) {
        $details .= '<tr>'
            . '<td style="padding:10px 0;border-bottom:1px solid rgba(35,64,52,0.12);font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:' . $clay . ';width:38%;font-family:Manrope,Arial,sans-serif;">' . esc_html($label) . '</td>'
            . '<td style="padding:10px 0;border-bottom:1px solid rgba(35,64,52,0.12);font-size:16px;color:' . $ink . ';font-family:Manrope,Arial,sans-serif;font-weight:600;">' . esc_html($value) . '</td>'
            . '</tr>';
    }

    $issue_html = nl2br(esc_html($data['issue']));
    $email_link = esc_url('mailto:' . $data['email']);
    $phone_link = esc_url('tel:' . preg_replace('/[^0-9+]/', '', $data['phone']));

    $cta = '';
    if ($admin) {
        $cta = '<a href="' . $admin . '" style="display:inline-block;margin-right:10px;padding:12px 22px;background:' . $forest . ';color:#ffffff;text-decoration:none;border-radius:10px;font-family:Manrope,Arial,sans-serif;font-weight:700;font-size:14px;">Open in admin</a>';
    }
    $cta .= '<a href="' . $email_link . '" style="display:inline-block;padding:12px 22px;background:#ffffff;color:' . $forest . ';text-decoration:none;border-radius:10px;border:1px solid ' . $forest . ';font-family:Manrope,Arial,sans-serif;font-weight:700;font-size:14px;">Reply to patient</a>';

    return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>New appointment request</title>
</head>
<body style="margin:0;padding:0;background:' . $cream . ';">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:' . $cream . ';padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;">
          <tr>
            <td style="padding:0 8px 18px;">
              <table role="presentation" cellspacing="0" cellpadding="0">
                <tr>
                  <td style="vertical-align:middle;padding-right:12px;">
                    <img src="' . $logo . '" alt="' . $brand . '" width="52" height="52" style="display:block;border-radius:12px;border:1px solid rgba(20,107,47,0.12);">
                  </td>
                  <td style="vertical-align:middle;">
                    <div style="font-family:Georgia,\'Times New Roman\',serif;font-size:22px;letter-spacing:0.08em;text-transform:uppercase;color:' . $ink . ';font-weight:700;">' . $brand . '</div>
                    <div style="font-family:Manrope,Arial,sans-serif;font-size:11px;letter-spacing:0.16em;text-transform:uppercase;color:' . $clay . ';">' . $tag . '</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#ffffff;border:1px solid rgba(35,64,52,0.12);border-radius:20px;padding:32px 28px;box-shadow:0 18px 38px rgba(23,59,36,0.08);">
              <div style="display:inline-block;padding:6px 12px;border-radius:999px;background:rgba(121,164,34,0.16);color:' . $forest . ';font-family:Manrope,Arial,sans-serif;font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;">New consultation request</div>
              <h1 style="margin:14px 0 8px;font-family:Georgia,\'Times New Roman\',serif;font-size:30px;line-height:1.15;color:' . $ink . ';font-weight:600;">A patient has requested an online appointment.</h1>
              <p style="margin:0 0 22px;font-family:Manrope,Arial,sans-serif;font-size:15px;line-height:1.6;color:' . $muted . ';">Review the details below and confirm the consultation during clinic hours.</p>
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0">' . $details . '</table>
              <div style="margin-top:22px;padding:16px 18px;background:rgba(251,245,232,0.95);border:1px solid rgba(35,64,52,0.10);border-radius:14px;">
                <div style="font-family:Manrope,Arial,sans-serif;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:' . $clay . ';font-weight:700;margin-bottom:8px;">Health issue</div>
                <div style="font-family:Manrope,Arial,sans-serif;font-size:15px;line-height:1.65;color:' . $ink . ';">' . $issue_html . '</div>
              </div>
              <div style="margin-top:26px;">' . $cta . '</div>
              <p style="margin:22px 0 0;font-family:Manrope,Arial,sans-serif;font-size:13px;color:' . $muted . ';">Patient email: <a href="' . $email_link . '" style="color:' . $forest . ';font-weight:700;">' . esc_html($data['email']) . '</a> · Phone: <a href="' . $phone_link . '" style="color:' . $forest . ';font-weight:700;">' . esc_html($data['phone']) . '</a></p>
            </td>
          </tr>
          <tr>
            <td style="padding:18px 8px 0;font-family:Manrope,Arial,sans-serif;font-size:12px;color:' . $muted . ';text-align:center;">' . esc_html(aaroh_get('footer_copy')) . '</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}
