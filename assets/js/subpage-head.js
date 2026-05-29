(function () {
    const script = document.currentScript;
    const basePath = script?.dataset.basePath ?? "../";
    const includeIcons = script?.dataset.icons === "true";

    const links = [
        { rel: "icon", href: basePath + "logo/favicon/favicon.ico", attrs: { type: "image/x-icon" } },
        { rel: "stylesheet", href: "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" },
        { rel: "stylesheet", href: basePath + "assets/css/subpages.css" }
    ];

    if (includeIcons) {
        links.push({ rel: "stylesheet", href: "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" });
    }

    for (const item of links) {
        const exists = Array.from(document.head.querySelectorAll('link')).some((link) => link.rel === item.rel && link.href === new URL(item.href, document.baseURI).href);
        if (exists) {
            continue;
        }

        const link = document.createElement("link");
        link.rel = item.rel;
        link.href = item.href;
        if (item.attrs) {
            Object.entries(item.attrs).forEach(([key, value]) => link.setAttribute(key, value));
        }
        document.head.appendChild(link);
    }
})();
