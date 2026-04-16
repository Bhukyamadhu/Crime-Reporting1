document.addEventListener("DOMContentLoaded", function () {
    document.body.classList.add("page-ready");

    var navbars = document.querySelectorAll(".glass-nav");

    function syncNavbarState() {
        var scrolled = window.scrollY > 12;
        navbars.forEach(function (navbar) {
            navbar.classList.toggle("nav-scrolled", scrolled);
        });
    }

    syncNavbarState();
    window.addEventListener("scroll", syncNavbarState, { passive: true });

    document.querySelectorAll("[data-loading-target]").forEach(function (form) {
        form.addEventListener("submit", function () {
            if (typeof form.checkValidity === "function" && !form.checkValidity()) {
                return;
            }
            var selector = form.getAttribute("data-loading-target");
            var target = selector ? document.querySelector(selector) : null;
            if (target) {
                target.hidden = false;
            }
        });
    });
});
