<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/qrcode.php';

$errors = [];
$product = null;
$qrSvg = null;
$old = [
    'name' => '',
    'sku' => '',
    'price' => '',
    'qty' => '',
    'category' => '',
    'notes' => '',
];

function product_barcode_payload(array $src): array
{
    return [
        'name' => trim((string) ($src['name'] ?? '')),
        'sku' => trim((string) ($src['sku'] ?? '')),
        'price' => (float) ($src['price'] ?? 0),
        'qty' => (int) ($src['qty'] ?? 0),
        'category' => trim((string) ($src['category'] ?? '')),
        'notes' => trim((string) ($src['notes'] ?? '')),
        'encodedAt' => date('c'),
    ];
}

function product_barcode_validate(array $payload): array
{
    $errors = [];
    if ($payload['name'] === '' || strlen($payload['name']) > 80) {
        $errors[] = 'Product name is required (max 80 characters).';
    }
    if ($payload['sku'] === '' || strlen($payload['sku']) > 40) {
        $errors[] = 'SKU is required (max 40 characters).';
    }
    if ($payload['price'] < 0) {
        $errors[] = 'Price must be 0 or greater.';
    }
    if ($payload['qty'] < 0) {
        $errors[] = 'Qty must be 0 or greater.';
    }
    if (strlen($payload['category']) > 40) {
        $errors[] = 'Category is too long.';
    }
    if (strlen($payload['notes']) > 120) {
        $errors[] = 'Notes are too long.';
    }
    return $errors;
}

function product_barcode_svg(string $text): string
{
    $qr = QRCode::getMinimumQRCode($text, QR_ERROR_CORRECT_LEVEL_M);
    ob_start();
    $qr->printSVG(4);
    return (string) ob_get_clean();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'decode') {
    header('Content-Type: application/json; charset=utf-8');
    $raw = (string) ($_POST['text'] ?? '');
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) {
        echo json_encode(['ok' => true, 'data' => $parsed], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['ok' => true, 'data' => ['raw' => $raw]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    $old = [
        'name' => (string) ($_POST['name'] ?? ''),
        'sku' => (string) ($_POST['sku'] ?? ''),
        'price' => (string) ($_POST['price'] ?? ''),
        'qty' => (string) ($_POST['qty'] ?? ''),
        'category' => (string) ($_POST['category'] ?? ''),
        'notes' => (string) ($_POST['notes'] ?? ''),
    ];
    $product = product_barcode_payload($_POST);
    $errors = product_barcode_validate($product);
    if ($errors === []) {
        $qrSvg = product_barcode_svg(json_encode($product, JSON_UNESCAPED_UNICODE));
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Product barcode demo | myDharm Technologies</title>
    <link rel="icon" type="image/png" href="../../logo/favicon/favicon-32x32.png">
    <style>
        :root {
            --primary: #6366f1;
            --accent: #ec4899;
            --bg: #0f172a;
            --bg-card: #1e293b;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --border: #334155;
            --ok: #34d399;
            --err: #f87171;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 1rem;
            padding-bottom: max(2.5rem, env(safe-area-inset-bottom));
        }
        .wrap { max-width: 420px; margin: 0 auto; }
        h1 {
            font-size: 1.25rem;
            margin: 0.25rem 0 0.35rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .lede, .note { color: var(--muted); font-size: 0.85rem; line-height: 1.45; margin: 0 0 1.1rem; }
        section {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        h2 { font-size: 1rem; margin: 0 0 0.85rem; }
        label { display: block; font-size: 0.78rem; color: var(--muted); margin: 0.65rem 0 0.3rem; }
        input, textarea {
            width: 100%;
            background: #0f172a;
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.7rem 0.8rem;
            font-size: 1rem;
        }
        textarea { min-height: 72px; resize: vertical; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; }
        button {
            width: 100%;
            margin-top: 0.95rem;
            border: 0;
            border-radius: 10px;
            padding: 0.85rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            cursor: pointer;
        }
        button.secondary {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
        }
        .errors { color: var(--err); font-size: 0.85rem; margin: 0 0 0.75rem; padding: 0; }
        .qr-box { margin-top: 1rem; text-align: center; }
        .qr-box svg { width: min(100%, 240px); height: auto; background: #fff; border-radius: 8px; padding: 10px; }
        #reader { display: none; margin-top: 0.75rem; overflow: hidden; border-radius: 10px; }
        #scan-json {
            display: none;
            margin-top: 0.85rem;
            background: #0f172a;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.75rem;
            font-size: 0.8rem;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .status { font-size: 0.82rem; margin-top: 0.6rem; color: var(--muted); }
        .status.ok { color: var(--ok); }
        .status.err { color: var(--err); }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Product barcode demo</h1>
        <p class="lede">PHP encodes product details as a QR code (2D barcode). Scan it to recover the same JSON.</p>

        <section>
            <h2>1. Enter product details</h2>
            <?php if ($errors): ?>
                <ul class="errors">
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="action" value="generate">
                <label for="name">Product name</label>
                <input id="name" name="name" required maxlength="80" autocomplete="off" placeholder="Organic cotton shirt" value="<?= h($old['name']) ?>">

                <label for="sku">SKU</label>
                <input id="sku" name="sku" required maxlength="40" autocomplete="off" placeholder="SHIRT-001" value="<?= h($old['sku']) ?>">

                <div class="row">
                    <div>
                        <label for="price">Price</label>
                        <input id="price" name="price" type="number" required min="0" step="0.01" placeholder="499.00" value="<?= h($old['price']) ?>">
                    </div>
                    <div>
                        <label for="qty">Qty</label>
                        <input id="qty" name="qty" type="number" required min="0" step="1" placeholder="12" value="<?= h($old['qty']) ?>">
                    </div>
                </div>

                <label for="category">Category</label>
                <input id="category" name="category" maxlength="40" placeholder="Apparel" value="<?= h($old['category']) ?>">

                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" maxlength="120" placeholder="Size M, navy"><?= h($old['notes']) ?></textarea>

                <button type="submit">Generate barcode</button>
            </form>
            <?php if ($qrSvg): ?>
                <div class="qr-box">
                    <?= $qrSvg ?>
                    <p class="note" style="margin: 0.6rem 0 0;">Point the camera below at this code to decode it.</p>
                </div>
            <?php endif; ?>
        </section>

        <section>
            <h2>2. Scan barcode</h2>
            <p class="note">Uses the phone camera. HTTPS (or localhost) is required. Allow camera access when asked.</p>
            <button type="button" id="scan-btn">Start camera scan</button>
            <button type="button" id="stop-btn" class="secondary" hidden>Stop camera</button>
            <div id="reader"></div>
            <pre id="scan-json"></pre>
            <p id="scan-status" class="status"></p>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        const scanBtn = document.getElementById("scan-btn");
        const stopBtn = document.getElementById("stop-btn");
        const readerEl = document.getElementById("reader");
        const scanJson = document.getElementById("scan-json");
        const scanStatus = document.getElementById("scan-status");
        let scanner = null;

        function setStatus(msg, kind) {
            scanStatus.textContent = msg;
            scanStatus.className = "status" + (kind ? " " + kind : "");
        }

        async function stopScan() {
            if (scanner) {
                try { await scanner.stop(); } catch (_) {}
                scanner.clear();
                scanner = null;
            }
            readerEl.style.display = "none";
            scanBtn.hidden = false;
            stopBtn.hidden = true;
        }

        async function decodeOnServer(text) {
            const body = new URLSearchParams();
            body.set("action", "decode");
            body.set("text", text);
            const res = await fetch(window.location.href, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body
            });
            const json = await res.json();
            return JSON.stringify(json.data, null, 2);
        }

        scanBtn.addEventListener("click", async () => {
            scanJson.style.display = "none";
            setStatus("Starting camera…");
            readerEl.style.display = "block";
            scanBtn.hidden = true;
            stopBtn.hidden = false;
            scanner = new Html5Qrcode("reader");
            try {
                await scanner.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 220, height: 220 } },
                    async (decodedText) => {
                        try {
                            scanJson.textContent = await decodeOnServer(decodedText);
                            scanJson.style.display = "block";
                            setStatus("Scan complete.", "ok");
                        } catch (err) {
                            setStatus("Decode failed: " + err, "err");
                        }
                        stopScan();
                    }
                );
                setStatus("Aim at the QR code.");
            } catch (err) {
                setStatus("Camera failed: " + (err && err.message ? err.message : err), "err");
                await stopScan();
            }
        });

        stopBtn.addEventListener("click", () => {
            stopScan();
            setStatus("Camera stopped.");
        });
    </script>
</body>
</html>
