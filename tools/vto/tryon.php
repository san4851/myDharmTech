<?php
declare(strict_types=1);

/**
 * Virtual Try-On demo API.
 *
 * POST action=prepare  — save uploads, return prompt + image URLs (no OpenAI call)
 * POST action=generate — send prepared images/prompt to OpenAI, return raw + result
 * GET  action=config   — public debug flag for the page
 */

header('X-Content-Type-Options: nosniff');

set_time_limit(300);
ini_set('max_execution_time', '300');
ignore_user_abort(true);
ini_set('display_errors', '0');

require_once dirname(__DIR__, 2) . '/env_loader.php';
$env = loadEnv(__DIR__ . '/.env');

const VTO_MAX_BYTES = 8 * 1024 * 1024;
const VTO_ALLOWED_MIME = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

$identityLock = <<<'TXT'
LOCKED BASE PHOTO: Image 1 is the user photograph. A transparency mask marks the only pixels you may change.
- Outside the mask, copy Image 1 exactly: face, skin, eyes, pose, crop, lighting, and background.
- Inside the mask, composite the item from Image 2 with correct scale, perspective, occlusion, and lighting.
- Do not beautify or regenerate the person. No collage. No text.
TXT;

$categories = [
    'apparel' => [
        'label' => 'Apparel',
        'prompt' => $identityLock . <<<'TXT'


Task: a mask marks the body below the jaw. Change only that region: fit the garment from the item photo with natural folds and the existing pose. Do not alter the face or hair. No collage. No text.
TXT,
    ],
    'jewellery' => [
        'label' => 'Jewellery',
        'prompt' => $identityLock . <<<'TXT'


Task: a mask marks ears, neck, and hairline. Place the jewellery from the item photo only in that mask, scaled to anatomy, with metal/stone reflections. Do not change the face. No collage. No text.
TXT,
    ],
    'cap' => [
        'label' => 'Cap',
        'prompt' => $identityLock . <<<'TXT'


Task: a mask marks the crown and forehead. Seat the cap from the item photo only in that mask. Match head tilt, brim angle, and tuck hair under the cap. Do not change the face below the brows, clothing, or background. No collage. No text.
TXT,
    ],
    'spectacles' => [
        'label' => 'Spectacles',
        'prompt' => $identityLock . <<<'TXT'


Task: a mask marks the eye/temple band. Place the spectacles from the item photo only in that mask, aligned to the eyes and nose bridge, temples to the ears. Keep lenses clear unless the item is sunglasses. Do not change the rest of the face. No collage. No text.
TXT,
    ],
];

function vto_debug_on(array $env): bool
{
    $v = strtolower(trim((string) ($env['VTO_DEBUG'] ?? '0')));
    return in_array($v, ['1', 'true', 'on', 'yes'], true);
}

function vto_fail(int $status, string $message, array $extra = []): void
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode(array_merge([
        'success' => false,
        'message' => $message,
    ], $extra), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function vto_ok(array $payload): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => true], $payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function vto_public_base(array $env): string
{
    $configured = trim((string) ($env['VTO_PUBLIC_BASE_URL'] ?? ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $dir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/tools/vto/tryon.php')));
    $dir = rtrim($dir, '/');
    return $scheme . '://' . $host . $dir;
}

function vto_detect_mime(string $path, string $fallback): string
{
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string) finfo_file($finfo, $path);
            finfo_close($finfo);
            if ($mime !== '') {
                return $mime;
            }
        }
    }
    return $fallback;
}

function vto_save_upload(array $file, string $role, string $uploadDir): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        vto_fail(400, ucfirst($role) . ' image upload failed.', ['errors' => ['code' => $file['error'] ?? 0]]);
    }
    if (($file['size'] ?? 0) <= 0 || $file['size'] > VTO_MAX_BYTES) {
        vto_fail(400, ucfirst($role) . ' image must be between 1 byte and 8 MB.');
    }

    $tmp = (string) $file['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        vto_fail(400, 'Invalid ' . $role . ' upload.');
    }

    $mime = vto_detect_mime($tmp, (string) ($file['type'] ?? ''));
    if (!isset(VTO_ALLOWED_MIME[$mime])) {
        vto_fail(400, ucfirst($role) . ' image must be JPEG, PNG, or WebP.');
    }

    $name = $role . '_' . bin2hex(random_bytes(8)) . '.' . VTO_ALLOWED_MIME[$mime];
    $dest = $uploadDir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        vto_fail(500, 'Could not store ' . $role . ' image.');
    }

    return ['path' => $dest, 'name' => $name, 'mime' => $mime];
}


function vto_sample_path(string $category): string
{
    $file = __DIR__ . '/samples/' . $category . '.jpg';
    return is_file($file) ? $file : '';
}

function vto_copy_sample(string $category, string $uploadDir): array
{
    $src = vto_sample_path($category);
    if ($src === '') {
        vto_fail(400, 'No sample item for this category. Please upload an item image.');
    }
    $name = 'item_' . bin2hex(random_bytes(8)) . '.jpg';
    $dest = $uploadDir . '/' . $name;
    if (!copy($src, $dest)) {
        vto_fail(500, 'Could not use the sample item image.');
    }
    return ['path' => $dest, 'name' => $name, 'mime' => 'image/jpeg'];
}

function vto_item_from_request(string $category, string $uploadDir): array
{
    $file = $_FILES['item_image'] ?? [];
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_OK) {
        return vto_save_upload($file, 'item', $uploadDir);
    }
    if ($err !== UPLOAD_ERR_NO_FILE) {
        vto_fail(400, 'Item image upload failed.', ['errors' => ['code' => $err]]);
    }
    return vto_copy_sample($category, $uploadDir);
}

function vto_stored_file(string $name, string $role, string $uploadDir): array
{
    if (!preg_match('/^' . preg_quote($role, '/') . '_[a-f0-9]{16}\\.(jpg|png|webp)$/', $name)) {
        vto_fail(400, 'Invalid ' . $role . ' file reference. Run prepare again.');
    }
    $path = $uploadDir . '/' . $name;
    if (!is_file($path)) {
        vto_fail(400, ucfirst($role) . ' image is missing. Run prepare again.');
    }
    $mime = vto_detect_mime($path, '');
    if (!isset(VTO_ALLOWED_MIME[$mime])) {
        vto_fail(400, ucfirst($role) . ' image type is not allowed.');
    }
    return ['path' => $path, 'name' => $name, 'mime' => $mime];
}

function vto_build_prompt(array $categories, string $category, string $userUrl, string $itemUrl): string
{
    return str_replace(
        ['{user_url}', '{item_url}'],
        [$userUrl, $itemUrl],
        $categories[$category]['prompt']
    ) . "\n\nUser photo URL (server): {$userUrl}\nItem photo URL (server): {$itemUrl}\n";
}

function vto_debug_raw(?string $raw, $decoded): string
{
    if (is_array($decoded) && isset($decoded['data'][0]['b64_json']) && is_string($decoded['data'][0]['b64_json'])) {
        $copy = $decoded;
        $len = strlen($copy['data'][0]['b64_json']);
        $copy['data'][0]['b64_json'] = '[omitted ' . $len . ' base64 chars]';
        return (string) json_encode($copy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    if (is_array($decoded)) {
        return (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    return (string) $raw;
}

function vto_web_optimize(string $binary, array $env): array
{
    $maxW = max(320, (int) ($env['VTO_WEB_MAX_WIDTH'] ?? 960));
    $quality = min(90, max(50, (int) ($env['VTO_WEB_QUALITY'] ?? $env['VTO_WEB_JPEG_QUALITY'] ?? 78)));

    if (!function_exists('imagecreatefromstring')) {
        return ['binary' => $binary, 'ext' => 'png'];
    }

    $src = @imagecreatefromstring($binary);
    if ($src === false) {
        return ['binary' => $binary, 'ext' => 'png'];
    }

    $width = imagesx($src);
    $height = imagesy($src);
    if ($width > $maxW) {
        $newW = $maxW;
        $newH = (int) max(1, round($height * ($maxW / $width)));
        $dst = imagecreatetruecolor($newW, $newH);
        if ($dst === false) {
            imagedestroy($src);
            return ['binary' => $binary, 'ext' => 'png'];
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($src);
        $src = $dst;
    }

    $out = '';
    $ext = 'jpg';
    if (function_exists('imagewebp')) {
        ob_start();
        imagewebp($src, null, $quality);
        $out = (string) ob_get_clean();
        $ext = 'webp';
    }
    if ($out === '') {
        ob_start();
        imagejpeg($src, null, $quality);
        $out = (string) ob_get_clean();
        $ext = 'jpg';
    }
    imagedestroy($src);
    if ($out === '') {
        return ['binary' => $binary, 'ext' => 'png'];
    }
    return ['binary' => $out, 'ext' => $ext];
}

function vto_save_mask(array $file, string $uploadDir): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        vto_fail(400, 'Mask upload failed.');
    }
    if (($file['size'] ?? 0) <= 0 || $file['size'] > VTO_MAX_BYTES) {
        vto_fail(400, 'Mask must be a PNG up to 8 MB.');
    }
    $tmp = (string) $file['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        vto_fail(400, 'Invalid mask upload.');
    }
    $mime = vto_detect_mime($tmp, (string) ($file['type'] ?? ''));
    if ($mime !== 'image/png') {
        vto_fail(400, 'Mask must be a PNG with transparency.');
    }
    $name = 'mask_' . bin2hex(random_bytes(8)) . '.png';
    $dest = $uploadDir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        vto_fail(500, 'Could not store mask.');
    }
    return ['path' => $dest, 'name' => $name, 'mime' => 'image/png'];
}

function vto_openai_edit(string $apiKey, string $model, string $prompt, array $userFile, array $itemFile, array $opts, ?array $maskFile = null): array
{
    $ch = curl_init('https://api.openai.com/v1/images/edits');
    if ($ch === false) {
        return ['raw' => '', 'http' => 0, 'curl_error' => 'Could not start OpenAI request.', 'decoded' => null];
    }

    $post = [
        'model' => $model,
        'prompt' => $prompt,
        'n' => '1',
        'size' => $opts['size'],
        'quality' => $opts['quality'],
        'input_fidelity' => $opts['input_fidelity'],
        'output_format' => $opts['output_format'],
        'image[0]' => new CURLFile($userFile['path'], $userFile['mime'], $userFile['name']),
        'image[1]' => new CURLFile($itemFile['path'], $itemFile['mime'], $itemFile['name']),
    ];
    if ($maskFile !== null) {
        $post['mask'] = new CURLFile($maskFile['path'], 'image/png', $maskFile['name']);
    }
    if (in_array($opts['output_format'], ['jpeg', 'webp'], true)) {
        $post['output_compression'] = (string) $opts['output_compression'];
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 280,
    ]);

    $raw = curl_exec($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $rawText = $raw === false ? '' : (string) $raw;
    $decoded = json_decode($rawText, true);

    return [
        'raw' => $rawText,
        'http' => $http,
        'curl_error' => $curlError,
        'decoded' => is_array($decoded) ? $decoded : null,
    ];
}

set_exception_handler(static function (Throwable $e): void {
    vto_fail(500, 'Server error: ' . $e->getMessage());
});

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = strtolower(trim((string) ($_REQUEST['action'] ?? '')));

if ($method === 'GET' && ($action === 'config' || $action === '')) {
    if ($action === 'config') {
        vto_ok([
            'debug' => vto_debug_on($env),
        ]);
    }
}

if ($method !== 'POST') {
    vto_fail(405, 'Use POST.');
}

$action = strtolower(trim((string) ($_POST['action'] ?? 'prepare')));
$category = strtolower(trim((string) ($_POST['category'] ?? '')));
if (!isset($categories[$category])) {
    vto_fail(400, 'Select a valid category: Apparel, Jewellery, Cap, or Spectacles.');
}

$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    vto_fail(500, 'Could not create upload directory.');
}

$base = vto_public_base($env);
$debug = vto_debug_on($env);

if ($action === 'prepare') {
    $userFile = vto_save_upload($_FILES['user_image'] ?? [], 'user', $uploadDir);
    $itemFile = vto_item_from_request($category, $uploadDir);
    $userUrl = $base . '/uploads/' . $userFile['name'];
    $itemUrl = $base . '/uploads/' . $itemFile['name'];
    $prompt = vto_build_prompt($categories, $category, $userUrl, $itemUrl);

    $payload = [
        'message' => 'Prompt prepared. Sending to OpenAI next.',
        'action' => 'prepare',
        'category' => $category,
        'category_label' => $categories[$category]['label'],
        'item_name' => $itemFile['name'],
        'user_name' => $userFile['name'],
        'debug' => $debug,
    ];
    if ($debug) {
        $payload['prompt'] = $prompt;
        $payload['item_url'] = $itemUrl;
        $payload['user_url'] = $userUrl;
    }
    vto_ok($payload);
}

if ($action !== 'generate') {
    vto_fail(400, 'Unknown action. Use prepare or generate.');
}

$apiKey = trim((string) ($env['OPENAI_API_KEY'] ?? ''));
if ($apiKey === '') {
    vto_fail(500, 'OPENAI_API_KEY is not configured in tools/vto/.env.');
}

$userFile = vto_stored_file((string) ($_POST['user_name'] ?? ''), 'user', $uploadDir);
$itemFile = vto_stored_file((string) ($_POST['item_name'] ?? ''), 'item', $uploadDir);
$maskFile = vto_save_mask($_FILES['mask'] ?? [], $uploadDir);
$userUrl = $base . '/uploads/' . $userFile['name'];
$itemUrl = $base . '/uploads/' . $itemFile['name'];
$prompt = vto_build_prompt($categories, $category, $userUrl, $itemUrl);

$model = trim((string) ($env['OPENAI_IMAGE_MODEL'] ?? 'gpt-image-1'));
if ($model === '') {
    $model = 'gpt-image-1';
}

$opts = [
    'size' => trim((string) ($env['OPENAI_IMAGE_SIZE'] ?? 'auto')) ?: 'auto',
    'quality' => trim((string) ($env['OPENAI_IMAGE_QUALITY'] ?? 'medium')) ?: 'medium',
    'input_fidelity' => trim((string) ($env['OPENAI_INPUT_FIDELITY'] ?? 'high')) ?: 'high',
    'output_format' => strtolower(trim((string) ($env['OPENAI_OUTPUT_FORMAT'] ?? 'webp'))) ?: 'webp',
    'output_compression' => min(90, max(40, (int) ($env['OPENAI_OUTPUT_COMPRESSION'] ?? 70))),
];

$openai = vto_openai_edit($apiKey, $model, $prompt, $userFile, $itemFile, $opts, $maskFile);
if ($maskFile !== null && $openai['http'] >= 400) {
    $errMsg = strtolower((string) (($openai['decoded']['error']['message'] ?? '')));
    if (str_contains($errMsg, 'mask') || str_contains($errMsg, 'invalid')) {
        $openai = vto_openai_edit($apiKey, $model, $prompt, $userFile, $itemFile, $opts, null);
    }
}

$rawDebug = $debug ? vto_debug_raw($openai['raw'], $openai['decoded']) : '';
$debugExtra = $debug ? ['raw_response' => $rawDebug, 'openai_http' => $openai['http'], 'prompt' => $prompt] : [];

if ($openai['raw'] === '' && $openai['curl_error'] !== '') {
    vto_fail(502, 'OpenAI request failed: ' . $openai['curl_error'], $debugExtra);
}

$decoded = $openai['decoded'];
if (!is_array($decoded)) {
    vto_fail(502, 'OpenAI returned a non-JSON body.', $debugExtra);
}

if ($openai['http'] >= 400) {
    $msg = (string) ($decoded['error']['message'] ?? 'OpenAI error.');
    vto_fail(502, $msg, $debugExtra);
}

$b64 = $decoded['data'][0]['b64_json'] ?? null;
$url = $decoded['data'][0]['url'] ?? null;
if (!is_string($b64) && !is_string($url)) {
    vto_fail(502, 'OpenAI did not return an image.', $debugExtra);
}

if (is_string($b64)) {
    $binary = base64_decode($b64, true);
    if ($binary === false || $binary === '') {
        vto_fail(502, 'Could not decode generated image.', $debugExtra);
    }
    $optimized = vto_web_optimize($binary, $env);
    $resultName = 'result_' . bin2hex(random_bytes(8)) . '.' . $optimized['ext'];
    if (file_put_contents($uploadDir . '/' . $resultName, $optimized['binary']) === false) {
        vto_fail(500, 'Could not save generated image.', $debugExtra);
    }
    $resultUrl = $base . '/uploads/' . $resultName;
} else {
    $resultUrl = $url;
}

$ok = [
    'message' => 'Try-on image generated.',
    'action' => 'generate',
    'category' => $category,
    'category_label' => $categories[$category]['label'],
    'result_url' => $resultUrl,
    'debug' => $debug,
];
if ($debug) {
    $ok['prompt'] = $prompt;
    $ok['item_url'] = $itemUrl;
    $ok['user_url'] = $userUrl;
    $ok['raw_response'] = $rawDebug;
    $ok['openai_http'] = $openai['http'];
    if ($maskFile !== null) {
        $ok['mask_url'] = $base . '/uploads/' . $maskFile['name'];
    }
}
vto_ok($ok);
