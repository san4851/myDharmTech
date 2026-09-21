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

    function apiUrl() {
        let path = window.location.pathname.replace(/\/index\.html$/i, "/");
        if (!path.endsWith("/")) {
            path += "/";
        }
        return window.location.origin + path + "tryon.php";
    }

    function setStatus(type, text) {
        statusMessage.textContent = text;
        statusMessage.className = "form-status-message" + (type ? " " + type : "");
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
            throw Object.assign(new Error("Server did not return JSON. HTTP " + response.status), {
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
        submitBtn.textContent = "Preparing prompt…";
        resultBox.innerHTML = '<p class="text-secondary-custom mb-0">Uploading images and preparing prompt…</p>';
        promptBox.textContent = "Preparing prompt…";
        rawBox.textContent = "Waiting for OpenAI…";

        try {
            const prepareBody = new FormData(form);
            prepareBody.set("action", "prepare");
            const prepared = await postJson(prepareBody);
            promptBox.textContent = prepared.data.prompt || "No prompt returned.";
            setStatus("", "Prompt ready. Calling OpenAI…");

            submitBtn.textContent = "Calling OpenAI…";
            resultBox.innerHTML = '<p class="text-secondary-custom mb-0">Prompt is ready. Sending it to OpenAI…</p>';

            const generateBody = new FormData();
            generateBody.set("action", "generate");
            generateBody.set("category", prepared.data.category);
            generateBody.set("user_name", prepared.data.user_name);
            generateBody.set("item_name", prepared.data.item_name);
            const generated = await postJson(generateBody);

            rawBox.textContent = generated.data.raw_response || generated.text || "Empty OpenAI body.";

            resultBox.innerHTML =
                '<img src="' + generated.data.result_url + '" alt="Virtual try-on result">' +
                '<p class="small text-secondary-custom mt-3 mb-0">Category: ' +
                (generated.data.category_label || generated.data.category) +
                "</p>";
            setStatus("success", generated.data.message || "Try-on image generated.");
        } catch (err) {
            if (err.raw) {
                rawBox.textContent = err.raw;
            }
            if (!promptBox.textContent || promptBox.textContent === "Preparing prompt…") {
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
