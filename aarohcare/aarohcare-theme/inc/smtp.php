<?php
/**
 * Admin SMTP settings for wp_mail / PHPMailer.
 *
 * @package AarohCare
 */

if (!defined('ABSPATH')) {
    exit;
}

function aaroh_smtp_defaults()
{
    return [
        'enabled'     => 0,
        'host'        => '',
        'port'        => 587,
        'encryption'  => 'tls',
        'username'    => '',
        'password'    => '',
        'from_email'  => '',
        'from_name'   => '',
        'force_from'  => 1,
        'notify_email' => '',
    ];
}

function aaroh_smtp_settings()
{
    $saved = get_option('aaroh_smtp', []);
    if (!is_array($saved)) {
        $saved = [];
    }
    return array_merge(aaroh_smtp_defaults(), $saved);
}

add_action('admin_menu', 'aaroh_smtp_menu');
function aaroh_smtp_menu()
{
    add_theme_page(
        'Aaroh Care Email',
        'Aaroh Care Email',
        'manage_options',
        'aaroh-smtp',
        'aaroh_smtp_render_page'
    );
}

add_action('admin_init', 'aaroh_smtp_handle_post');
function aaroh_smtp_handle_post()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $saving = isset($_POST['aaroh_smtp_save']) || isset($_POST['aaroh_smtp_test']);
    if (!$saving) {
        return;
    }

    check_admin_referer('aaroh_smtp_save');
    aaroh_smtp_save_from_post();

    if (isset($_POST['aaroh_smtp_save'])) {
        add_settings_error('aaroh_smtp', 'aaroh_smtp_saved', 'SMTP settings saved.', 'updated');
    }

    if (isset($_POST['aaroh_smtp_test'])) {
        $to = isset($_POST['test_email']) ? sanitize_email(wp_unslash($_POST['test_email'])) : '';
        if (!is_email($to)) {
            $to = get_option('admin_email');
        }

        $error = '';
        add_action('wp_mail_failed', static function ($wp_error) use (&$error) {
            if ($wp_error instanceof WP_Error) {
                $error = $wp_error->get_error_message();
            }
        });

        $sent = wp_mail(
            $to,
            'Aaroh Care SMTP test',
            "This is a test email from the Aaroh Care theme SMTP settings.\n\nIf you received this, PHP mail via SMTP is working."
        );

        if ($sent) {
            add_settings_error('aaroh_smtp', 'aaroh_smtp_test_ok', 'Settings saved. Test email sent to ' . $to . '.', 'updated');
        } else {
            $msg = $error ? $error : 'Test email failed. Check host, port, encryption, and login.';
            add_settings_error('aaroh_smtp', 'aaroh_smtp_test_fail', $msg, 'error');
        }
    }
}

function aaroh_smtp_save_from_post()
{
    $current = aaroh_smtp_settings();
    $enc     = isset($_POST['encryption']) ? sanitize_text_field(wp_unslash($_POST['encryption'])) : 'tls';
    if (!in_array($enc, ['none', 'tls', 'ssl'], true)) {
        $enc = 'tls';
    }

    $password = $current['password'];
    if (isset($_POST['password']) && $_POST['password'] !== '') {
        $password = (string) wp_unslash($_POST['password']);
    }

    $from_email = isset($_POST['from_email']) ? sanitize_email(wp_unslash($_POST['from_email'])) : '';

    update_option('aaroh_smtp', [
        'enabled'    => empty($_POST['enabled']) ? 0 : 1,
        'host'       => isset($_POST['host']) ? sanitize_text_field(wp_unslash($_POST['host'])) : '',
        'port'       => isset($_POST['port']) ? max(1, min(65535, absint($_POST['port']))) : 587,
        'encryption' => $enc,
        'username'   => isset($_POST['username']) ? sanitize_text_field(wp_unslash($_POST['username'])) : '',
        'password'   => $password,
        'from_email' => $from_email,
        'from_name'  => isset($_POST['from_name']) ? sanitize_text_field(wp_unslash($_POST['from_name'])) : '',
        'force_from' => empty($_POST['force_from']) ? 0 : 1,
        'notify_email' => isset($_POST['notify_email']) ? aaroh_sanitize_email_list(wp_unslash($_POST['notify_email'])) : '',
    ]);
}

function aaroh_sanitize_email_list($raw)
{
    $parts = preg_split('/[,;]+/', (string) $raw);
    $out   = [];
    foreach ($parts as $part) {
        $email = sanitize_email(trim($part));
        if ($email && is_email($email) && !in_array($email, $out, true)) {
            $out[] = $email;
        }
    }
    return implode(', ', $out);
}

function aaroh_smtp_render_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $s = aaroh_smtp_settings();
    $test_default = get_option('admin_email');
    if ($s['notify_email'] !== '') {
        $parts = array_map('trim', explode(',', $s['notify_email']));
        if (!empty($parts[0])) {
            $test_default = $parts[0];
        }
    }
    ?>
    <div class="wrap">
      <h1>Aaroh Care Email</h1>
      <p>Set where appointment requests are sent, then optionally send mail through SMTP.</p>
      <?php settings_errors('aaroh_smtp'); ?>
      <form method="post">
        <?php wp_nonce_field('aaroh_smtp_save'); ?>
        <h2>Appointment notifications</h2>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="aaroh_notify_email">Send appointments to</label></th>
            <td>
              <input class="regular-text" type="text" id="aaroh_notify_email" name="notify_email" value="<?php echo esc_attr($s['notify_email']); ?>" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
              <p class="description">Clinic inbox for new appointment requests. Separate multiple addresses with commas. If empty, WordPress admin email is used<?php echo aaroh_get('clinic_email') ? ', or the Customizer clinic email' : ''; ?>.</p>
            </td>
          </tr>
        </table>
        <h2>SMTP</h2>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row">Enable SMTP</th>
            <td>
              <label>
                <input type="checkbox" name="enabled" value="1" <?php checked($s['enabled']); ?>>
                Send WordPress email through SMTP
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="aaroh_smtp_host">SMTP host</label></th>
            <td><input class="regular-text" type="text" id="aaroh_smtp_host" name="host" value="<?php echo esc_attr($s['host']); ?>" placeholder="smtp.gmail.com"></td>
          </tr>
          <tr>
            <th scope="row"><label for="aaroh_smtp_port">Port</label></th>
            <td><input class="small-text" type="number" id="aaroh_smtp_port" name="port" value="<?php echo esc_attr((string) $s['port']); ?>" min="1" max="65535"> Common: 587 (TLS) or 465 (SSL)</td>
          </tr>
          <tr>
            <th scope="row"><label for="aaroh_smtp_encryption">Encryption</label></th>
            <td>
              <select id="aaroh_smtp_encryption" name="encryption">
                <option value="tls" <?php selected($s['encryption'], 'tls'); ?>>TLS (STARTTLS)</option>
                <option value="ssl" <?php selected($s['encryption'], 'ssl'); ?>>SSL</option>
                <option value="none" <?php selected($s['encryption'], 'none'); ?>>None</option>
              </select>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="aaroh_smtp_username">Username</label></th>
            <td><input class="regular-text" type="text" id="aaroh_smtp_username" name="username" value="<?php echo esc_attr($s['username']); ?>" autocomplete="off"></td>
          </tr>
          <tr>
            <th scope="row"><label for="aaroh_smtp_password">Password</label></th>
            <td>
              <input class="regular-text" type="password" id="aaroh_smtp_password" name="password" value="" autocomplete="new-password" placeholder="<?php echo $s['password'] !== '' ? 'Leave blank to keep saved password' : ''; ?>">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="aaroh_smtp_from_email">From email</label></th>
            <td><input class="regular-text" type="email" id="aaroh_smtp_from_email" name="from_email" value="<?php echo esc_attr($s['from_email']); ?>" placeholder="noreply@example.com"></td>
          </tr>
          <tr>
            <th scope="row"><label for="aaroh_smtp_from_name">From name</label></th>
            <td><input class="regular-text" type="text" id="aaroh_smtp_from_name" name="from_name" value="<?php echo esc_attr($s['from_name']); ?>" placeholder="Aaroh Care Homoeopathy"></td>
          </tr>
          <tr>
            <th scope="row">Force From address</th>
            <td>
              <label>
                <input type="checkbox" name="force_from" value="1" <?php checked($s['force_from']); ?>>
                Always use the From name and email above
              </label>
            </td>
          </tr>
        </table>
        <?php submit_button('Save email settings', 'primary', 'aaroh_smtp_save'); ?>
        <h2>Send test email</h2>
        <p>
          <label for="aaroh_smtp_test_email">Send to</label>
          <input class="regular-text" type="email" id="aaroh_smtp_test_email" name="test_email" value="<?php echo esc_attr($test_default); ?>">
        </p>
        <?php submit_button('Send test email', 'secondary', 'aaroh_smtp_test'); ?>
      </form>
    </div>
    <?php
}

add_action('phpmailer_init', 'aaroh_smtp_phpmailer');
function aaroh_smtp_phpmailer($phpmailer)
{
    $s = aaroh_smtp_settings();
    if (empty($s['enabled']) || $s['host'] === '') {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = $s['host'];
    $phpmailer->Port       = (int) $s['port'];
    $phpmailer->SMTPAuth   = ($s['username'] !== '' || $s['password'] !== '');
    $phpmailer->Username   = $s['username'];
    $phpmailer->Password   = $s['password'];
    $phpmailer->Timeout    = 15;

    if ('ssl' === $s['encryption']) {
        $phpmailer->SMTPSecure = 'ssl';
        $phpmailer->SMTPAutoTLS = true;
    } elseif ('tls' === $s['encryption']) {
        $phpmailer->SMTPSecure = 'tls';
        $phpmailer->SMTPAutoTLS = true;
    } else {
        $phpmailer->SMTPSecure = '';
        $phpmailer->SMTPAutoTLS = false;
    }

    if ($s['from_email'] && is_email($s['from_email'])) {
        $name = $s['from_name'] !== '' ? $s['from_name'] : $s['from_email'];
        $phpmailer->setFrom($s['from_email'], $name, false);
    }
}

add_filter('wp_mail_from', 'aaroh_smtp_from_email');
function aaroh_smtp_from_email($from)
{
    $s = aaroh_smtp_settings();
    if (empty($s['enabled']) || empty($s['force_from'])) {
        return $from;
    }
    if ($s['from_email'] && is_email($s['from_email'])) {
        return $s['from_email'];
    }
    return $from;
}

add_filter('wp_mail_from_name', 'aaroh_smtp_from_name');
function aaroh_smtp_from_name($name)
{
    $s = aaroh_smtp_settings();
    if (empty($s['enabled']) || empty($s['force_from'])) {
        return $name;
    }
    if ($s['from_name'] !== '') {
        return $s['from_name'];
    }
    return $name;
}
