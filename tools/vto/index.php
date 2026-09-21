<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/env_loader.php';
require_once __DIR__ . '/access_control.php';
$env = loadEnv(__DIR__ . '/.env');
$debugVal = strtolower(trim((string) ($env['VTO_DEBUG'] ?? '0')));
$vtoDebug = in_array($debugVal, ['1', 'true', 'on', 'yes'], true);
$accessError = vto_access_handle_form($env);
$hasAccess = vto_access_is_granted();
$attemptsRemaining = max(0, vto_access_limit($env) - vto_access_attempts($env));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Try-On Demo | myDharm Technologies</title>
    <meta name="description" content="Demo virtual try-on for apparel, jewellery, caps, and spectacles using uploaded photos.">
    <meta name="robots" content="noindex, nofollow">
    <script src="../../assets/js/subpage-head.js" data-base-path="../../" data-icons="true"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body data-base-path="../../" data-vto-debug="<?php echo $vtoDebug ? '1' : '0'; ?>">
    <div data-shared-nav></div>

    <main>
        <section class="section">
            <div class="container">
<?php if (!$hasAccess): ?>
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="custom-card vto-card">
                            <h1 class="section-title text-center">Virtual Try-On Access</h1>
                            <p class="section-subtitle text-center">Enter your VTO access code to use this demo.</p>
<?php if ($accessError !== null): ?>
                            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($accessError, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
                            <form method="post" novalidate>
                                <div class="mb-3">
                                    <label for="vto_access_code" class="form-label">VTO access code</label>
                                    <input class="form-control" type="password" id="vto_access_code" name="vto_access_code" autocomplete="off" required autofocus<?php echo $attemptsRemaining === 0 ? ' disabled' : ''; ?>>
                                </div>
                                <button class="btn btn-primary-custom" type="submit" name="vto_access_submit" value="1"<?php echo $attemptsRemaining === 0 ? ' disabled' : ''; ?>>Continue</button>
                            </form>
                            <p class="small text-secondary-custom mt-3 mb-0">Need an access code? Request one through our <a href="../../contact/">Contact Us form</a>.</p>
                        </div>
                    </div>
                </div>
<?php else: ?>
                <h1 class="section-title text-center">Virtual Try-On</h1>
                <p class="section-subtitle text-center">Upload a product photo and a person photo, pick a category, then generate a photorealistic try-on.</p>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="custom-card vto-card">
                            <form id="vtoForm" action="" method="post" enctype="multipart/form-data" novalidate>
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select category</option>
                                        <option value="apparel">Apparel</option>
                                        <option value="jewellery">Jewellery</option>
                                        <option value="cap">Cap</option>
                                        <option value="spectacles">Spectacles</option>
                                    </select>
                                </div>

                                <div class="vto-item-selection mb-4">
                                    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-2">
                                        <label class="form-label mb-0">Choose an item</label>
                                        <p class="small text-secondary-custom mb-0">Select a sample, or upload your own image below.</p>
                                    </div>
                                    <div class="vto-samples" id="itemSamples" role="listbox" aria-label="Sample items"></div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="vto-upload-panel">
                                            <label for="item_image" class="form-label">Upload item image</label>
                                        <input class="form-control mt-2" type="file" id="item_image" name="item_image" accept="image/jpeg,image/png,image/webp">
                                        <button type="button" class="btn btn-secondary-custom btn-sm mt-2" id="clearItemBtn" hidden>Use sample</button>
                                        <div class="vto-preview" id="itemPreview" aria-live="polite">Select a category to see the sample item.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="vto-upload-panel">
                                        <label for="user_image" class="form-label">User image</label>
                                        <input class="form-control" type="file" id="user_image" name="user_image" accept="image/jpeg,image/png,image/webp" required>
                                        <input type="file" id="userCameraNative" accept="image/*" capture="user" hidden>
                                        <div class="vto-preview" id="userPreview" aria-live="polite">No user selected</div>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <button type="button" class="btn btn-secondary-custom btn-sm" id="openCameraBtn">Take photo</button>
                                        </div>
                                        <div class="vto-camera" id="cameraPanel" hidden>
                                            <video id="cameraVideo" autoplay playsinline muted></video>
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                <button type="button" class="btn btn-primary-custom btn-sm" id="captureBtn">Capture</button>
                                                <button type="button" class="btn btn-secondary-custom btn-sm" id="flipCameraBtn">Flip camera</button>
                                                <button type="button" class="btn btn-secondary-custom btn-sm" id="closeCameraBtn">Close</button>
                                            </div>
                                            <p class="small text-secondary-custom mt-2 mb-0" id="cameraStatus"></p>
                                        </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-status-message" id="formStatusMessage" aria-live="polite"></div>
                                <button type="submit" class="btn btn-primary-custom" id="submitBtn">Generate try-on</button>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="custom-card vto-card">
                            <h2 class="h5 mb-3">Result</h2>
                            <div class="vto-result" id="resultBox">
                                <p class="text-secondary-custom mb-0">The generated image will appear here after submit.</p>
                            </div>
                        </div>
                    </div>
                </div>
<?php if ($vtoDebug): ?>
                <div class="custom-card vto-prompt-card mt-4" id="promptCard">
                    <h2 class="h5 mb-3">Prepared prompt</h2>
                    <pre class="vto-prompt" id="promptBox">The prompt will appear here after upload, before OpenAI is called.</pre>
                </div>

                <div class="custom-card vto-prompt-card mt-4" id="maskCard">
                    <h2 class="h5 mb-3">Face lock mask</h2>
                    <p class="small text-secondary-custom">Transparent / punched area is editable. White stays the original photo.</p>
                    <div class="vto-preview" id="maskBox">Mask preview will appear here.</div>
                </div>

                <div class="custom-card vto-prompt-card mt-4" id="rawCard">
                    <h2 class="h5 mb-3">Raw OpenAI response</h2>
                    <pre class="vto-prompt" id="rawBox">The unmodified OpenAI body will appear here before the result image is rendered.</pre>
                </div>
<?php endif; ?>
<?php endif; ?>
            </div>
        </section>
    </main>

    <div data-shared-footer></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/subpage-layout.js"></script>
    <script type="module" src="app.js"></script>
</body>
</html>
