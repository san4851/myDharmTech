<?php
declare(strict_types=1);

/**
 * Access-code gate shared by the Virtual Try-On page and API.
 *
 * This is intentionally a small, local gate. Session authorization protects
 * the tool; a signed, short-lived cookie keeps failed-attempt limits in place
 * when a visitor starts a new browser session.
 */

const VTO_ACCESS_SESSION_GRANTED = 'vto_access_granted';
const VTO_ACCESS_SESSION_ATTEMPTS = 'vto_access_attempts';
const VTO_ACCESS_ATTEMPTS_COOKIE = 'vto_access_attempts';
const VTO_ACCESS_ATTEMPTS_TTL = 86400;

function vto_access_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function vto_access_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('vto_access');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => vto_access_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function vto_access_codes(array $env): array
{
    $rawCodes = explode(',', (string) ($env['VTO_ACCESS_CODE'] ?? ''));
    $codes = [];
    foreach ($rawCodes as $code) {
        $code = trim($code);
        if ($code !== '' && !in_array($code, $codes, true)) {
            $codes[] = $code;
        }
    }
    return $codes;
}

function vto_access_limit(array $env): int
{
    return max(1, (int) ($env['VTO_ACCESS_CODE_REQUEST_LIMIT'] ?? 5));
}

function vto_access_cookie_key(array $env): string
{
    // Changing configured access codes also invalidates old attempt cookies.
    return hash('sha256', 'vto-access-attempts|' . (string) ($env['VTO_ACCESS_CODE'] ?? ''));
}

function vto_access_cookie_attempts(array $env): int
{
    $value = (string) ($_COOKIE[VTO_ACCESS_ATTEMPTS_COOKIE] ?? '');
    $parts = explode('.', $value);
    if (count($parts) !== 3 || !ctype_digit($parts[0]) || !ctype_digit($parts[1])) {
        return 0;
    }

    [$attempts, $expires, $signature] = $parts;
    if ((int) $expires < time()) {
        return 0;
    }

    $expected = hash_hmac('sha256', $attempts . '.' . $expires, vto_access_cookie_key($env));
    return hash_equals($expected, $signature) ? (int) $attempts : 0;
}

function vto_access_set_cookie_attempts(int $attempts, array $env): void
{
    $expires = time() + VTO_ACCESS_ATTEMPTS_TTL;
    $value = $attempts . '.' . $expires;
    $value .= '.' . hash_hmac('sha256', $value, vto_access_cookie_key($env));
    setcookie(VTO_ACCESS_ATTEMPTS_COOKIE, $value, [
        'expires' => $expires,
        'path' => '/',
        'secure' => vto_access_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function vto_access_attempts(array $env): int
{
    vto_access_start_session();
    return max((int) ($_SESSION[VTO_ACCESS_SESSION_ATTEMPTS] ?? 0), vto_access_cookie_attempts($env));
}

function vto_access_record_failed_attempt(array $env): int
{
    $attempts = vto_access_attempts($env) + 1;
    $_SESSION[VTO_ACCESS_SESSION_ATTEMPTS] = $attempts;
    vto_access_set_cookie_attempts($attempts, $env);
    return $attempts;
}

function vto_access_client_ip(): string
{
    // REMOTE_ADDR is the address reported by the web server and is not a
    // client-controlled forwarding header.
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
}

function vto_access_log_attempt(string $status, string $accessCode): void
{
    // Keep each entry on one line even if a submitted value contains control characters.
    $accessCode = str_replace(
        ['\\', "\r", "\n", "\t"],
        ['\\\\', '\\r', '\\n', '\\t'],
        $accessCode
    );
    $line = sprintf(
        "%s\tIP=%s\t%s\tACCESS_CODE=%s\n",
        gmdate('c'),
        vto_access_client_ip(),
        $status,
        $accessCode
    );
    @file_put_contents(__DIR__ . '/vt_access_code.log', $line, FILE_APPEND | LOCK_EX);
}

function vto_access_is_granted(): bool
{
    vto_access_start_session();
    return ($_SESSION[VTO_ACCESS_SESSION_GRANTED] ?? false) === true;
}

/**
 * Processes the form on index.php and returns an error suitable for display.
 */
function vto_access_handle_form(array $env): ?string
{
    vto_access_start_session();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !isset($_POST['vto_access_submit'])) {
        return null;
    }

    $submitted = trim((string) ($_POST['vto_access_code'] ?? ''));
    $limit = vto_access_limit($env);
    if (vto_access_attempts($env) >= $limit) {
        vto_access_log_attempt('access_denied', $submitted);
        return 'Too many access-code attempts. Please try again later.';
    }

    $valid = false;
    foreach (vto_access_codes($env) as $code) {
        if (hash_equals($code, $submitted)) {
            $valid = true;
            break;
        }
    }

    if (!$valid) {
        vto_access_log_attempt('access_denied', $submitted);
        $attempts = vto_access_record_failed_attempt($env);
        if ($attempts >= $limit) {
            return 'Too many access-code attempts. Please try again later.';
        }
        return 'That access code is not valid.';
    }

    session_regenerate_id(true);
    $_SESSION[VTO_ACCESS_SESSION_GRANTED] = true;
    vto_access_log_attempt('access_granted', $submitted);
    header('Location: index.php', true, 303);
    exit;
}

function vto_access_require_api(array $env): void
{
    if (vto_access_is_granted()) {
        return;
    }

    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access code required. Open the Virtual Try-On page to continue.',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
