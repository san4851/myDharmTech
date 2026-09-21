import { createCategoryMask } from "./mask.js";

(function () {
    const form = document.getElementById("vtoForm");
    const statusMessage = document.getElementById("formStatusMessage");
    const submitBtn = document.getElementById("submitBtn");
    const resultBox = document.getElementById("resultBox");
    const itemInput = document.getElementById("item_image");
    const userInput = document.getElementById("user_image");
    const itemPreview = document.getElementById("itemPreview");
    const userPreview = document.getElementById("userPreview");
    const promptBox = document.getElementById("promptBox");
    const rawBox = document.getElementById("rawBox");
    const maskBox = document.getElementById("maskBox");
    const debug = document.body.dataset.vtoDebug === "1";

    function apiUrl() {
        let path = window.location.pathname.replace(/\/index\.(html|php)$/i, "/");
        if (!path.endsWith("/")) {
            path += "/";
        }
        return window.location.origin + path + "tryon.php";
    }

    function setStatus(type, text) {
        statusMessage.textContent = text;
        statusMessage.className = "form-status-message" + (type ? " " + type : "");
    }

    function setDebugText(el, text) {
        if (debug && el) {
            el.textContent = text;
        }
    }

    function previewFile(input, target, emptyText) {
        const file = input.files && input.files[0];
        target.innerHTML = "";
        if (!file) {
            target.textContent = emptyText;
            return;
        }
        const img = document.createElement("img");
        img.alt = file.name;
        img.src = URL.createObjectURL(file);
        img.onload = function () {
            URL.revokeObjectURL(img.src);
        };
        target.appendChild(img);
    }

    const SAMPLE_ITEMS = {
        apparel: "samples/apparel.jpg",
        jewellery: "samples/jewellery.jpg",
        cap: "samples/cap.jpg",
        spectacles: "samples/spectacles.jpg",
    };
    const categorySelect = document.getElementById("category");
    const itemSamples = document.getElementById("itemSamples");
    const clearItemBtn = document.getElementById("clearItemBtn");

    function sampleUrl(category) {
        return SAMPLE_ITEMS[category] || "";
    }

    function hasCustomItem() {
        return !!(itemInput.files && itemInput.files[0]);
    }

    function showSamplePreview(category) {
        const url = sampleUrl(category);
        itemPreview.innerHTML = "";
        if (!url) {
            itemPreview.textContent = "Select a category to see the sample item.";
            return;
        }
        const img = document.createElement("img");
        img.alt = category + " sample";
        img.src = url;
        itemPreview.appendChild(img);
        if (clearItemBtn) {
            clearItemBtn.hidden = !hasCustomItem();
        }
        if (itemSamples) {
            Array.from(itemSamples.querySelectorAll(".vto-sample-btn")).forEach(function (btn) {
                btn.classList.toggle("is-active", btn.dataset.category === category);
            });
        }
    }

    function renderSampleButtons() {
        if (!itemSamples) {
            return;
        }
        itemSamples.innerHTML = "";
        Object.keys(SAMPLE_ITEMS).forEach(function (key) {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "vto-sample-btn";
            btn.dataset.category = key;
            btn.title = key;
            btn.setAttribute("aria-label", "Use " + key + " sample");
            const img = document.createElement("img");
            img.alt = key;
            img.src = SAMPLE_ITEMS[key];
            btn.appendChild(img);
            btn.addEventListener("click", function () {
                categorySelect.value = key;
                itemInput.value = "";
                showSamplePreview(key);
            });
            itemSamples.appendChild(btn);
        });
    }

    async function postJson(body) {
        const response = await fetch(apiUrl(), {
            method: "POST",
            body: body,
        });
        const text = await response.text();
        let data = null;
        try {
            data = JSON.parse(text);
        } catch (err) {
            const snippet = text.slice(0, 2000);
            const timedOut = /request timeout|takes too long to process|timed out by the server/i.test(text);
            const message = timedOut
                ? "The host timed out while OpenAI was generating the image. Raise LiteSpeed Connection Timeout to 300 seconds, then retry."
                : "Server did not return JSON. HTTP " + response.status;
            throw Object.assign(new Error(message), {
                raw: snippet,
            });
        }
        if (!response.ok || !data.success) {
            throw Object.assign(new Error(data.message || ("Request failed (HTTP " + response.status + ")")), {
                raw: data.raw_response || text,
                data: data,
            });
        }
        return { response: response, data: data, text: text };
    }

    const openCameraBtn = document.getElementById("openCameraBtn");
    const closeCameraBtn = document.getElementById("closeCameraBtn");
    const captureBtn = document.getElementById("captureBtn");
    const flipCameraBtn = document.getElementById("flipCameraBtn");
    const cameraPanel = document.getElementById("cameraPanel");
    const cameraVideo = document.getElementById("cameraVideo");
    const cameraStatus = document.getElementById("cameraStatus");
    const nativeCameraInput = document.getElementById("userCameraNative");

    let cameraStream = null;
    let facingMode = "user";

    function setCameraStatus(text) {
        if (cameraStatus) {
            cameraStatus.textContent = text || "";
        }
    }

    function stopCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(function (track) {
                track.stop();
            });
            cameraStream = null;
        }
        if (cameraVideo) {
            cameraVideo.srcObject = null;
        }
        if (cameraPanel) {
            cameraPanel.hidden = true;
        }
        setCameraStatus("");
    }

    async function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (nativeCameraInput) {
                nativeCameraInput.click();
                return;
            }
            throw new Error("Camera is not supported in this browser.");
        }
        stopCamera();
        cameraPanel.hidden = false;
        cameraPanel.dataset.facing = facingMode;
        setCameraStatus("Starting camera…");
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    facingMode: { ideal: facingMode },
                    width: { ideal: 1280 },
                    height: { ideal: 1280 },
                },
            });
            cameraVideo.srcObject = cameraStream;
            setCameraStatus(facingMode === "user" ? "Front camera" : "Rear camera");
        } catch (err) {
            stopCamera();
            if (nativeCameraInput) {
                nativeCameraInput.click();
                return;
            }
            throw err;
        }
    }

    function assignUserFile(file) {
        const transfer = new DataTransfer();
        transfer.items.add(file);
        userInput.files = transfer.files;
        previewFile(userInput, userPreview, "No user selected");
    }

    async function capturePhoto() {
        if (!cameraVideo || cameraVideo.readyState < 2) {
            setCameraStatus("Camera is not ready yet.");
            return;
        }
        const canvas = document.createElement("canvas");
        canvas.width = cameraVideo.videoWidth || 720;
        canvas.height = cameraVideo.videoHeight || 720;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(cameraVideo, 0, 0, canvas.width, canvas.height);
        const blob = await new Promise(function (resolve) {
            canvas.toBlob(resolve, "image/jpeg", 0.9);
        });
        if (!blob) {
            setCameraStatus("Could not capture the photo.");
            return;
        }
        assignUserFile(new File([blob], "user-camera.jpg", { type: "image/jpeg" }));
        stopCamera();
        setStatus("", "Photo captured. You can generate the try-on.");
    }

    renderSampleButtons();
    categorySelect.addEventListener("change", function () {
        itemInput.value = "";
        showSamplePreview(categorySelect.value);
    });
    itemInput.addEventListener("change", function () {
        if (hasCustomItem()) {
            previewFile(itemInput, itemPreview, "No item selected");
            if (clearItemBtn) {
                clearItemBtn.hidden = false;
            }
        } else {
            showSamplePreview(categorySelect.value);
        }
    });
    if (clearItemBtn) {
        clearItemBtn.addEventListener("click", function () {
            itemInput.value = "";
            showSamplePreview(categorySelect.value);
        });
    }
    userInput.addEventListener("change", function () {
        previewFile(userInput, userPreview, "No user selected");
    });
    if (nativeCameraInput) {
        nativeCameraInput.addEventListener("change", function () {
            const file = nativeCameraInput.files && nativeCameraInput.files[0];
            if (file) {
                assignUserFile(file);
            }
        });
    }
    if (openCameraBtn) {
        openCameraBtn.addEventListener("click", function () {
            startCamera().catch(function (err) {
                setStatus("error", err.message || "Could not open the camera.");
            });
        });
    }
    if (closeCameraBtn) {
        closeCameraBtn.addEventListener("click", stopCamera);
    }
    if (captureBtn) {
        captureBtn.addEventListener("click", function () {
            capturePhoto().catch(function (err) {
                setStatus("error", err.message || "Could not capture the photo.");
            });
        });
    }
    if (flipCameraBtn) {
        flipCameraBtn.addEventListener("click", function () {
            facingMode = facingMode === "user" ? "environment" : "user";
            startCamera().catch(function (err) {
                setStatus("error", err.message || "Could not switch camera.");
            });
        });
    }
    window.addEventListener("pagehide", stopCamera);

    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        setStatus("", "");

        if (!form.reportValidity()) {
            return;
        }
        if (!categorySelect.value) {
            setStatus("error", "Select a category.");
            return;
        }
        if (!hasCustomItem() && !sampleUrl(categorySelect.value)) {
            setStatus("error", "Upload an item image or pick a category sample.");
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = "Detecting face…";
        resultBox.innerHTML = '<p class="text-secondary-custom mb-0">Building a category mask so the face stays locked…</p>';
        setDebugText(promptBox, "Preparing prompt…");
        setDebugText(rawBox, "Waiting for OpenAI…");
        if (debug && maskBox) {
            maskBox.textContent = "Building mask…";
        }

        try {
            const category = form.category.value;
            const userFile = userInput.files[0];
            const mask = await createCategoryMask(userFile, category);
            if (debug && maskBox) {
                maskBox.innerHTML = "";
                const img = document.createElement("img");
                img.alt = "Edit mask (black/transparent = editable)";
                img.src = mask.previewUrl;
                maskBox.appendChild(img);
            }

            const prepareBody = new FormData(form);
            prepareBody.set("action", "prepare");
            const prepared = await postJson(prepareBody);
            setDebugText(promptBox, prepared.data.prompt || "No prompt returned.");
            setStatus("", "Mask ready. Calling OpenAI…");

            submitBtn.textContent = "Calling OpenAI…";
            resultBox.innerHTML = '<p class="text-secondary-custom mb-0">Sending masked edit to OpenAI…</p>';

            const generateBody = new FormData();
            generateBody.set("action", "generate");
            generateBody.set("category", prepared.data.category);
            generateBody.set("user_name", prepared.data.user_name);
            generateBody.set("item_name", prepared.data.item_name);
            generateBody.set("mask", mask.blob, "mask.png");
            const generated = await postJson(generateBody);

            setDebugText(rawBox, generated.data.raw_response || generated.text || "Empty OpenAI body.");

            resultBox.innerHTML =
                '<img src="' + generated.data.result_url + '" alt="Virtual try-on result">' +
                '<p class="small text-secondary-custom mt-3 mb-0">Category: ' +
                (generated.data.category_label || generated.data.category) +
                "</p>";
            setStatus("success", generated.data.message || "Try-on image generated.");
        } catch (err) {
            setDebugText(rawBox, err.raw || "");
            resultBox.innerHTML = '<p class="text-secondary-custom mb-0">No result yet.</p>';
            setStatus("error", err.message || "Try-on failed.");
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = "Generate try-on";
        }
    });
})();
