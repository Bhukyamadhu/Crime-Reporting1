(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        var pageName = document.body.getAttribute("data-tour-page");
        if (!pageName) {
            return;
        }

        var steps = Array.prototype.slice.call(document.querySelectorAll("[data-tour-step]"))
            .sort(function (a, b) {
                return Number(a.getAttribute("data-tour-step")) - Number(b.getAttribute("data-tour-step"));
            });

        if (!steps.length) {
            return;
        }

        var storageKey = "crime_site_tour_seen_v2_" + pageName;
        var currentStepIndex = 0;
        var overlay = buildOverlay();
        var spotlight = overlay.querySelector(".site-tour-spotlight");
        var card = overlay.querySelector(".site-tour-card");
        var title = overlay.querySelector(".site-tour-title");
        var text = overlay.querySelector(".site-tour-text");
        var counter = overlay.querySelector(".site-tour-counter");
        var progress = overlay.querySelector(".site-tour-progress");
        var prevBtn = overlay.querySelector("[data-tour-prev]");
        var nextBtn = overlay.querySelector("[data-tour-next]");
        var finishBtn = overlay.querySelector("[data-tour-finish]");
        var skipBtn = overlay.querySelector("[data-tour-skip]");
        var restartBtn = buildRestartButton();
        var active = false;

        document.body.appendChild(overlay);
        document.body.appendChild(restartBtn);

        prevBtn.addEventListener("click", function () {
            if (currentStepIndex > 0) {
                currentStepIndex -= 1;
                renderStep();
            }
        });

        nextBtn.addEventListener("click", function () {
            if (currentStepIndex < steps.length - 1) {
                currentStepIndex += 1;
                renderStep();
            }
        });

        finishBtn.addEventListener("click", function () {
            closeTour(true);
        });

        skipBtn.addEventListener("click", function () {
            closeTour(true);
        });

        restartBtn.addEventListener("click", function () {
            openTour();
        });

        window.addEventListener("resize", function () {
            if (active) {
                renderStep();
            }
        });

        window.addEventListener("scroll", function () {
            if (active) {
                renderStep();
            }
        }, { passive: true });

        document.addEventListener("keydown", function (event) {
            if (!active) {
                return;
            }

            if (event.key === "Escape") {
                closeTour(true);
            } else if (event.key === "ArrowRight" && currentStepIndex < steps.length - 1) {
                currentStepIndex += 1;
                renderStep();
            } else if (event.key === "ArrowLeft" && currentStepIndex > 0) {
                currentStepIndex -= 1;
                renderStep();
            }
        });

        if (!localStorage.getItem(storageKey)) {
            window.setTimeout(function () {
                openTour();
            }, 700);
        }

        function openTour() {
            active = true;
            currentStepIndex = 0;
            overlay.hidden = false;
            document.body.classList.add("site-tour-active");
            renderStep();
        }

        function closeTour(markSeen) {
            active = false;
            overlay.hidden = true;
            document.body.classList.remove("site-tour-active");
            if (markSeen) {
                localStorage.setItem(storageKey, "1");
            }
        }

        function renderStep() {
            var step = steps[currentStepIndex];
            var rect = step.getBoundingClientRect();
            var titleText = step.getAttribute("data-tour-title") || "Website Guide";
            var bodyText = step.getAttribute("data-tour-text") || "";
            var viewportPadding = 14;
            var spotlightPadding = 10;
            var cardWidth = Math.min(320, window.innerWidth - 24);
            var arrowGap = 16;
            var cardHeight;
            var placement = "bottom";

            step.scrollIntoView({ behavior: "smooth", block: "center", inline: "nearest" });

            title.textContent = titleText;
            text.textContent = bodyText;
            counter.textContent = "Step " + (currentStepIndex + 1) + " of " + steps.length;
            progress.innerHTML = buildProgressDots(steps.length, currentStepIndex);

            prevBtn.hidden = currentStepIndex === 0;
            nextBtn.hidden = currentStepIndex === steps.length - 1;
            finishBtn.hidden = currentStepIndex !== steps.length - 1;

            spotlight.style.top = Math.max(rect.top - spotlightPadding, viewportPadding) + "px";
            spotlight.style.left = Math.max(rect.left - spotlightPadding, viewportPadding) + "px";
            spotlight.style.width = Math.min(rect.width + spotlightPadding * 2, window.innerWidth - viewportPadding * 2) + "px";
            spotlight.style.height = Math.min(rect.height + spotlightPadding * 2, window.innerHeight - viewportPadding * 2) + "px";

            card.style.width = cardWidth + "px";
            cardHeight = card.offsetHeight;

            var topSpace = rect.top;
            var bottomSpace = window.innerHeight - rect.bottom;
            var rightSpace = window.innerWidth - rect.right;
            var leftSpace = rect.left;
            var cardTop = rect.bottom + arrowGap;
            var cardLeft = rect.left;

            if (bottomSpace >= cardHeight + 30) {
                placement = "bottom";
                cardTop = rect.bottom + arrowGap;
                cardLeft = rect.left + rect.width / 2 - cardWidth / 2;
            } else if (topSpace >= cardHeight + 30) {
                placement = "top";
                cardTop = rect.top - cardHeight - arrowGap;
                cardLeft = rect.left + rect.width / 2 - cardWidth / 2;
            } else if (rightSpace >= cardWidth + 30) {
                placement = "right";
                cardTop = rect.top + rect.height / 2 - cardHeight / 2;
                cardLeft = rect.right + arrowGap;
            } else {
                placement = "left";
                cardTop = rect.top + rect.height / 2 - cardHeight / 2;
                cardLeft = rect.left - cardWidth - arrowGap;
            }

            cardTop = Math.max(12, Math.min(cardTop, window.innerHeight - cardHeight - 12));
            cardLeft = Math.max(12, Math.min(cardLeft, window.innerWidth - cardWidth - 12));

            card.className = "site-tour-card placement-" + placement;
            card.style.top = cardTop + "px";
            card.style.left = cardLeft + "px";
            card.style.transform = "translate3d(0,0,0)";
        }

        function buildOverlay() {
            var shell = document.createElement("div");
            shell.className = "site-tour-overlay";
            shell.hidden = true;
            shell.innerHTML = '' +
                '<div class="site-tour-backdrop"></div>' +
                '<div class="site-tour-spotlight" aria-hidden="true"></div>' +
                '<div class="site-tour-card" role="dialog" aria-modal="true" aria-label="Website guide">' +
                    '<span class="site-tour-arrow" aria-hidden="true"></span>' +
                    '<div class="site-tour-card-top">' +
                        '<span class="site-tour-counter"></span>' +
                        '<button type="button" class="site-tour-skip" data-tour-skip>Skip</button>' +
                    '</div>' +
                    '<h3 class="site-tour-title"></h3>' +
                    '<p class="site-tour-text"></p>' +
                    '<div class="site-tour-progress"></div>' +
                    '<div class="site-tour-actions">' +
                        '<button type="button" class="btn btn-outline-primary" data-tour-prev>Back</button>' +
                        '<button type="button" class="btn btn-primary" data-tour-next>Next</button>' +
                        '<button type="button" class="btn btn-primary" data-tour-finish hidden>Finish</button>' +
                    '</div>' +
                '</div>';
            return shell;
        }

        function buildProgressDots(total, activeIndex) {
            var html = "";
            var index;
            for (index = 0; index < total; index += 1) {
                html += '<span class="site-tour-dot' + (index === activeIndex ? ' active' : '') + '"></span>';
            }
            return html;
        }

        function buildRestartButton() {
            var button = document.createElement("button");
            button.type = "button";
            button.className = "site-tour-trigger";
            button.innerHTML = '<i class="fa-regular fa-circle-question" aria-hidden="true"></i><span>Replay Guide</span>';
            button.setAttribute("aria-label", "Replay website guide");
            return button;
        }
    });
})();
