<?php
declare(strict_types=1);

/**
 * Virtual Try-On demo API.
 *
 * POST action=prepare  — save uploads, return prompt + image URLs (no OpenAI call)
 * POST action=generate — send prepared images/prompt to OpenAI, return raw + result
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

set_time_limit(180);
ini_set('max_execution_time', '180');
ini_set('display_errors', '0');

require_once dirname(__DIR__, 2) . '/env_loader.php';
$env = loadEnv(__DIR__ . '/.env');

const VTO_MAX_BYTES = 8 * 1024 * 1024;
const VTO_ALLOWED_MIME = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

$categories = [
    'apparel' => [
        'label' => 'Apparel',
        'size' => '1024x1536',
        'prompt' => <<<'TXT'
You are a professional virtual try-on compositor.

Task: dress the person in the user photo with the exact garment from the item photo.

User photo URL (server): {user_url}
Item photo URL (server): {item_url}

Hard constraints:
- Preserve the person's identity: face, skin tone, hair, body shape, pose, camera angle, and background.
- Reproduce the garment faithfully: color, pattern, fabric texture, cut, logos, embroidery, and prints. Do not invent a different design.
- Fit the garment naturally to the body with correct draping, folds, occlusion, and lighting that matches the user photo.
- Replace only the clothing region that the item is meant to occupy. Keep other clothing that should remain visible unless the item is a full outfit.
- Photorealistic result. No text, watermarks, extra people, or collage layout.
TXT,
    ],
    'jewellery' => [
        'label' => 'Jewellery',
        'size' => '1024x1024',
        'prompt' => <<<'TXT'
You are a professional virtual try-on compositor for jewellery.

Task: place the exact jewellery from the item photo onto the person in the user photo.

User photo URL (server): {user_url}
Item photo URL (server): {item_url}

Hard constraints:
- Preserve the person's identity, pose, lighting, and background.
- Keep the jewellery design identical: metal color, stones, shape, and details.
- Scale and place it on the correct body part (necklace on neck, earrings on ears, ring on finger, bracelet on wrist, maang tikka on hairline, etc.).
- Match perspective, occlusion by skin/hair/clothing, and realistic metal/stone reflections.
- Photorealistic. No text, watermarks, or collage.
TXT,
    ],
    'cap' => [
        'label' => 'Cap',
        'size' => '1024x1024',
        'prompt' => <<<'TXT'
You are a professional virtual try-on compositor for headwear.

Task: put the exact cap/hat from the item photo on the person's head in the user photo.

User photo URL (server): {user_url}
Item photo URL (server): {item_url}

Hard constraints:
- Preserve the person's identity, face, skin, body, pose, lighting, and background.
- Reproduce the cap exactly: color, logo, shape, brim, and material.
- Sit it on the head with correct scale, tilt, and perspective. Hair should tuck under or around the cap naturally.
- Photorealistic. No text, watermarks, or collage.
TXT,
    ],
    'spectacles' => [
        'label' => 'Spectacles',
        'size' => '1024x1024',
        'prompt' => <<<'TXT'
You are a professional virtual try-on compositor for eyewear.

Task: wear the exact spectacles from the item photo on the person in the user photo.

User photo URL (server): {user_url}
Item photo URL (server): {item_url}

Hard constraints:
- Preserve the person's identity, eyes, face shape, pose, lighting, and background.
- Reproduce the frames exactly: shape, color, temples, hinges, and any logos.
- Align the glasses to the eyes and nose bridge. Temples should go over the ears. Lenses should be transparent unless the item is sunglasses.
- Include subtle reflections and slight shadow on the face. Do not hide the eyes behind opaque lenses unless the item is sunglasses.
- Photorealistic. No text, watermarks, or collage.
TXT,
    ],
];

function vto_fail(int $status, string $message, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(array_merge([
        'success' => false,
        'message' => $message,
    ], $extra), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function vto_ok(array $payload): void
{
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
    );
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

function vto_openai_edit(string $apiKey, string $model, string $prompt, string $size, array $userFile, array $itemFile): array
{
    $ch = curl_init('https://api.openai.com/v1/images/edits');
    if ($ch === false) {
        return ['raw' => '', 'http' => 0, 'curl_error' => 'Could not start OpenAI request.', 'decoded' => null];
    }

    $post = [
        'model' => $model,
        'prompt' => $prompt,
        'n' => '1',
        'size' => $size,
        'quality' => 'high',
        'image[0]' => new CURLFile($userFile['path'], $userFile['mime'], $userFile['name']),
        'image[1]' => new CURLFile($itemFile['path'], $itemFile['mime'], $itemFile['name']),
    ];

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 150,
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

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
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

if ($action === 'prepare') {
    $userFile = vto_save_upload($_FILES['user_image'] ?? [], 'user', $uploadDir);
    $itemFile = vto_save_upload($_FILES['item_image'] ?? [], 'item', $uploadDir);
    $userUrl = $base . '/uploads/' . $userFile['name'];
    $itemUrl = $base . '/uploads/' . $itemFile['name'];
    $prompt = vto_build_prompt($categories, $category, $userUrl, $itemUrl);

    vto_ok([
        'message' => 'Prompt prepared. Sending to OpenAI next.',
        'action' => 'prepare',
        'category' => $category,
        'category_label' => $categories[$category]['label'],
        'prompt' => $prompt,
        'item_url' => $itemUrl,
        'user_url' => $userUrl,
        'item_name' => $itemFile['name'],
        'user_name' => $userFile['name'],
    ]);
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
$userUrl = $base . '/uploads/' . $userFile['name'];
$itemUrl = $base . '/uploads/' . $itemFile['name'];
$prompt = vto_build_prompt($categories, $category, $userUrl, $itemUrl);

$model = trim((string) ($env['OPENAI_IMAGE_MODEL'] ?? 'gpt-image-1'));
if ($model === '') {
    $model = 'gpt-image-1';
}

$openai = vto_openai_edit($apiKey, $model, $prompt, $categories[$category]['size'], $userFile, $itemFile);
$rawDebug = vto_debug_raw($openai['raw'], $openai['decoded']);

if ($openai['raw'] === '' && $openai['curl_error'] !== '') {
    vto_fail(502, 'OpenAI request failed: ' . $openai['curl_error'], [
        'raw_response' => $rawDebug,
        'openai_http' => $openai['http'],
        'prompt' => $prompt,
    ]);
}

$decoded = $openai['decoded'];
if (!is_array($decoded)) {
    vto_fail(502, 'OpenAI returned a non-JSON body.', [
        'raw_response' => $rawDebug !== '' ? $rawDebug : '(empty)',
        'openai_http' => $openai['http'],
        'prompt' => $prompt,
    ]);
}

if ($openai['http'] >= 400) {
    $msg = (string) ($decoded['error']['message'] ?? 'OpenAI error.');
    vto_fail(502, $msg, [
        'raw_response' => $rawDebug,
        'openai_http' => $openai['http'],
        'prompt' => $prompt,
    ]);
}

$b64 = $decoded['data'][0]['b64_json'] ?? null;
$url = $decoded['data'][0]['url'] ?? null;
if (!is_string($b64) && !is_string($url)) {
    vto_fail(502, 'OpenAI did not return an image.', [
        'raw_response' => $rawDebug,
        'openai_http' => $openai['http'],
        'prompt' => $prompt,
    ]);
}

if (is_string($b64)) {
    $binary = base64_decode($b64, true);
    if ($binary === false || $binary === '') {
        vto_fail(502, 'Could not decode generated image.', [
            'raw_response' => $rawDebug,
            'prompt' => $prompt,
        ]);
    }
    $resultName = 'result_' . bin2hex(random_bytes(8)) . '.png';
    if (file_put_contents($uploadDir . '/' . $resultName, $binary) === false) {
        vto_fail(500, 'Could not save generated image.', [
            'raw_response' => $rawDebug,
            'prompt' => $prompt,
        ]);
    }
    $resultUrl = $base . '/uploads/' . $resultName;
} else {
    $resultUrl = $url;
}

vto_ok([
    'message' => 'Try-on image generated.',
    'action' => 'generate',
    'category' => $category,
    'category_label' => $categories[$category]['label'],
    'prompt' => $prompt,
    'item_url' => $itemUrl,
    'user_url' => $userUrl,
    'result_url' => $resultUrl,
    'raw_response' => $rawDebug,
    'openai_http' => $openai['http'],
]);
