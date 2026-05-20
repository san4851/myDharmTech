(function () {
    const body = document.body;
    const activeNav = body.dataset.activeNav || "";
    const basePath = body.dataset.basePath || "../";

    const navItems = [
        { key: "home", label: "Home", href: "" },
        { key: "about", label: "About", href: "about/" },
        { key: "services", label: "Services", href: "services/" },
        { key: "industries", label: "Industries", href: "industries/" },
        { key: "approach", label: "Approach", href: "approach/" },
        { key: "why-mydharm", label: "Why myDharm", href: "why-choose/" },
        { key: "training", label: "Training", href: "training/" },
        { key: "contact", label: "Contact", href: "contact/" }
    ];

    const navTarget = document.querySelector("[data-shared-nav]");
    if (navTarget) {
        const navLinks = navItems.map((item) => {
            const activeClass = item.key === activeNav ? " active" : "";
            return '<li class="nav-item"><a class="nav-link' + activeClass + '" href="' + basePath + item.href + '">' + item.label + '</a></li>';
        }).join("");

        navTarget.innerHTML =
            '<nav class="navbar navbar-expand-lg navbar-dark bg-dark-custom fixed-top">' +
                '<div class="container">' +
                    '<a class="navbar-brand" href="' + basePath + '" aria-label="myDharm Technologies Home">' +
                        '<img src="' + basePath + 'logo/myDharm-Technologies-logo.png" alt="myDharm Technologies Logo" class="navbar-logo" width="40" height="40" loading="eager">' +
                    '</a>' +
                    '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">' +
                        '<span class="navbar-toggler-icon"></span>' +
                    '</button>' +
                    '<div class="collapse navbar-collapse" id="navMain">' +
                        '<ul class="navbar-nav ms-auto">' + navLinks + '</ul>' +
                    '</div>' +
                '</div>' +
            '</nav>';
    }

    const footerTarget = document.querySelector("[data-shared-footer]");
    if (footerTarget) {
        footerTarget.innerHTML =
            '<footer class="shared-footer text-center" role="contentinfo" itemscope itemtype="https://schema.org/WPFooter">' +
                '<div class="container">' +
                    '<div class="shared-footer-links">' +
                        '<a href="' + basePath + 'why-choose/">Why Choose Us</a>' +
                        '<a href="' + basePath + 'products-clients/">Products &amp; Clients</a>' +
                        '<a href="' + basePath + 'technology-stack/">Technology Stack</a>' +
                        '<a href="' + basePath + 'community/">Community</a>' +
                    '</div>' +
                    '<p class="mb-2">&copy; <span itemprop="copyrightYear">2024</span> <span itemprop="copyrightHolder" itemscope itemtype="https://schema.org/Organization"><span itemprop="name">myDharm Technologies</span></span>. All rights reserved.</p>' +
                    '<p class="mb-0">Tech for Business. Skills for Students.</p>' +
                '</div>' +
            '</footer>';
    }
})();
