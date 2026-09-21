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
        submitBtn.textContent = "Generating…";
        resultBox.innerHTML = '<p class="text-secondary-custom mb-0">Working. This can take up to a minute.</p>';
        promptBox.textContent = 'Generating prompt…';

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
            });
            const data = await response.json();
            if (!response.ok || !data.success || !data.result_url) {
                throw new Error(data.message || "Try-on failed.");
            }

            resultBox.innerHTML =
                '<img src="' + data.result_url + '" alt="Virtual try-on result">' +
                '<p class="small text-secondary-custom mt-3 mb-0">Category: ' + (data.category_label || data.category) + "</p>";
            promptBox.textContent = data.prompt || "No prompt returned.";
            setStatus("success", data.message || "Try-on image generated.");
        } catch (err) {
            resultBox.innerHTML = '<p class="text-secondary-custom mb-0">No result yet.</p>';
            promptBox.textContent = 'The prompt sent to OpenAI will appear here after submit.';
            setStatus("error", err.message || "Try-on failed.");
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = "Generate try-on";
        }
    });
})();
