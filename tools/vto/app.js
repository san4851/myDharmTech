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

    itemInput.addEventListener("change", function () {
        previewFile(itemInput, itemPreview, "No item selected");
    });
    userInput.addEventListener("change", function () {
        previewFile(userInput, userPreview, "No user selected");
    });

    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        setStatus("", "");

        if (!form.reportValidity()) {
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = debug ? "Preparing prompt…" : "Generating…";
        resultBox.innerHTML = '<p class="text-secondary-custom mb-0">Uploading images…</p>';
        setDebugText(promptBox, "Preparing prompt…");
        setDebugText(rawBox, "Waiting for OpenAI…");

        try {
            const prepareBody = new FormData(form);
            prepareBody.set("action", "prepare");
            const prepared = await postJson(prepareBody);
            setDebugText(promptBox, prepared.data.prompt || "No prompt returned.");
            setStatus("", debug ? "Prompt ready. Calling OpenAI…" : "Generating try-on…");

            submitBtn.textContent = "Calling OpenAI…";
            resultBox.innerHTML = '<p class="text-secondary-custom mb-0">Sending to OpenAI…</p>';

            const generateBody = new FormData();
            generateBody.set("action", "generate");
            generateBody.set("category", prepared.data.category);
            generateBody.set("user_name", prepared.data.user_name);
            generateBody.set("item_name", prepared.data.item_name);
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
            if (debug && promptBox && (!promptBox.textContent || promptBox.textContent === "Preparing prompt…")) {
                promptBox.textContent = "Prompt was not prepared. See the raw response below.";
            }
            resultBox.innerHTML = '<p class="text-secondary-custom mb-0">No result yet.</p>';
            setStatus("error", err.message || "Try-on failed.");
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = "Generate try-on";
        }
    });
})();
