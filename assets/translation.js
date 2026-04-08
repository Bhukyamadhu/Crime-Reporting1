document.addEventListener("DOMContentLoaded", function () {
    initAppTranslation();
});

function initAppTranslation() {
    if (!window.APP_TRANSLATION) {
        return;
    }

    var config = window.APP_TRANSLATION;
    var languageSelect = document.getElementById("appLanguageSelect");
    var translationCache = {};
    var attributeMap = {
        placeholder: "data-i18n-placeholder-original",
        title: "data-i18n-title-original",
        "aria-label": "data-i18n-aria-label-original"
    };

    function hasMeaningfulText(text) {
        return typeof text === "string" && text.replace(/\s+/g, " ").trim().length > 0;
    }

    function setElementText(el, value) {
        var textNode = null;
        Array.prototype.forEach.call(el.childNodes, function (node) {
            if (!textNode && node.nodeType === Node.TEXT_NODE && hasMeaningfulText(node.textContent)) {
                textNode = node;
            }
        });

        if (!textNode) {
            el.appendChild(document.createTextNode(value));
            return;
        }

        var prefix = textNode.textContent.match(/^\s*/);
        var suffix = textNode.textContent.match(/\s*$/);
        textNode.textContent = (prefix ? prefix[0] : "") + value + (suffix ? suffix[0] : "");
    }

    function rememberAttribute(el, attribute) {
        var key = attributeMap[attribute];
        if (key && !el.hasAttribute(key)) {
            el.setAttribute(key, el.getAttribute(attribute) || "");
        }
    }

    function rememberOriginalText(el) {
        if (!el.hasAttribute("data-i18n-original")) {
            el.setAttribute("data-i18n-original", (el.getAttribute("data-i18n") || el.textContent || "").replace(/\s+/g, " ").trim());
        }
    }

    function getOriginalText(el) {
        rememberOriginalText(el);
        return el.getAttribute("data-i18n-original") || "";
    }

    function collectTranslatableElements() {
        return Array.prototype.slice.call(
            document.querySelectorAll("[data-i18n], [data-i18n-placeholder], [data-i18n-title], [data-i18n-aria-label]")
        );
    }

    function postForm(url, payload) {
        var body = new URLSearchParams();
        Object.keys(payload).forEach(function (key) {
            body.append(key, payload[key]);
        });

        return fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
            },
            credentials: "same-origin",
            body: body.toString()
        }).then(function (response) {
            return response.json().catch(function () {
                return {};
            }).then(function (data) {
                if (!response.ok) {
                    throw new Error(data.message || "Request failed");
                }
                return data;
            });
        });
    }

    function translateText(text, targetLanguage) {
        var normalizedText = (text || "").replace(/\s+/g, " ").trim();
        if (!hasMeaningfulText(normalizedText) || targetLanguage === config.defaultLanguage) {
            return Promise.resolve(normalizedText);
        }

        var cacheKey = targetLanguage + "::" + normalizedText;
        if (translationCache[cacheKey]) {
            return Promise.resolve(translationCache[cacheKey]);
        }

        return postForm(config.translateEndpoint, {
            csrf_token: config.csrfToken,
            text: normalizedText,
            target_language: targetLanguage
        }).then(function (data) {
            var translated = data.translated_text || normalizedText;
            translationCache[cacheKey] = translated;
            return translated;
        }).catch(function () {
            translationCache[cacheKey] = normalizedText;
            return normalizedText;
        });
    }

    function updateStaticElement(el, targetLanguage) {
        var tasks = [];

        if (el.hasAttribute("data-i18n")) {
            var baseText = getOriginalText(el);
            if (targetLanguage === config.defaultLanguage) {
                setElementText(el, baseText);
            } else {
                tasks.push(translateText(baseText, targetLanguage).then(function (translated) {
                    setElementText(el, translated);
                }));
            }
        }

        [
            ["placeholder", "data-i18n-placeholder"],
            ["title", "data-i18n-title"],
            ["aria-label", "data-i18n-aria-label"]
        ].forEach(function (pair) {
            var attribute = pair[0];
            var marker = pair[1];
            if (!el.hasAttribute(marker)) {
                return;
            }

            rememberAttribute(el, attribute);
            var original = el.getAttribute(attributeMap[attribute]) || "";
            if (targetLanguage === config.defaultLanguage) {
                el.setAttribute(attribute, original);
            } else if (hasMeaningfulText(original)) {
                tasks.push(translateText(original, targetLanguage).then(function (translated) {
                    el.setAttribute(attribute, translated);
                }));
            }
        });

        return Promise.all(tasks);
    }

    function initContentBlocks() {
        document.querySelectorAll("[data-translate-content]").forEach(function (block) {
            if (!block.hasAttribute("data-original-text")) {
                block.setAttribute("data-original-text", (block.textContent || "").replace(/\s+/g, " ").trim());
            }

            if (block.querySelector(".translation-original")) {
                return;
            }

            var original = block.getAttribute("data-original-text") || "";
            block.innerHTML = ""
                + '<div class="translation-content-body translation-original"></div>'
                + '<div class="translation-content-body translation-translated d-none"></div>'
                + '<button type="button" class="btn btn-link btn-sm p-0 mt-2 translation-toggle d-none">View original</button>';

            var originalNode = block.querySelector(".translation-original");
            if (originalNode) {
                originalNode.textContent = original;
            }
        });

        document.querySelectorAll(".translation-toggle").forEach(function (button) {
            if (button.dataset.bound === "1") {
                return;
            }

            button.dataset.bound = "1";
            button.addEventListener("click", function () {
                var block = button.closest("[data-translate-content]");
                if (!block) {
                    return;
                }

                var originalNode = block.querySelector(".translation-original");
                var translatedNode = block.querySelector(".translation-translated");
                if (!originalNode || !translatedNode) {
                    return;
                }

                var showingOriginal = button.getAttribute("data-view") === "original";
                originalNode.classList.toggle("d-none", !showingOriginal);
                translatedNode.classList.toggle("d-none", showingOriginal);
                button.setAttribute("data-view", showingOriginal ? "translated" : "original");

                var nextLabel = showingOriginal ? "View translated" : "View original";
                if (config.currentLanguage === config.defaultLanguage) {
                    button.textContent = nextLabel;
                    return;
                }

                translateText(nextLabel, config.currentLanguage).then(function (translated) {
                    button.textContent = translated || nextLabel;
                });
            });
        });
    }

    function updateContentBlocks(targetLanguage) {
        initContentBlocks();

        var tasks = [];
        document.querySelectorAll("[data-translate-content]").forEach(function (block) {
            var original = block.getAttribute("data-original-text") || "";
            var originalNode = block.querySelector(".translation-original");
            var translatedNode = block.querySelector(".translation-translated");
            var toggle = block.querySelector(".translation-toggle");

            if (!originalNode || !translatedNode || !toggle) {
                return;
            }

            originalNode.textContent = original;

            if (targetLanguage === config.defaultLanguage) {
                originalNode.classList.remove("d-none");
                translatedNode.classList.add("d-none");
                translatedNode.textContent = "";
                toggle.classList.add("d-none");
                toggle.textContent = "View original";
                toggle.setAttribute("data-view", "original");
                return;
            }

            tasks.push(
                translateText(original, targetLanguage).then(function (translated) {
                    translatedNode.textContent = translated || original;
                    originalNode.classList.add("d-none");
                    translatedNode.classList.remove("d-none");
                    toggle.classList.remove("d-none");
                    toggle.setAttribute("data-view", "original");
                    return translateText("View original", targetLanguage);
                }).then(function (translatedLabel) {
                    toggle.textContent = translatedLabel || "View original";
                }).catch(function () {
                    translatedNode.textContent = original;
                    toggle.classList.add("d-none");
                })
            );
        });

        return Promise.all(tasks);
    }

    function initializeElements() {
        collectTranslatableElements().forEach(function (el) {
            if (el.hasAttribute("data-i18n")) {
                rememberOriginalText(el);
            }
            if (el.hasAttribute("data-i18n-placeholder")) {
                rememberAttribute(el, "placeholder");
            }
            if (el.hasAttribute("data-i18n-title")) {
                rememberAttribute(el, "title");
            }
            if (el.hasAttribute("data-i18n-aria-label")) {
                rememberAttribute(el, "aria-label");
            }
        });
        initContentBlocks();
    }

    function applyLanguage(targetLanguage) {
        config.currentLanguage = targetLanguage;
        document.documentElement.setAttribute("lang", (config.supportedLanguages[targetLanguage] || {}).html || "en");

        var tasks = collectTranslatableElements().map(function (el) {
            return updateStaticElement(el, targetLanguage);
        });
        tasks.push(updateContentBlocks(targetLanguage));

        return Promise.all(tasks);
    }

    initializeElements();

    if (languageSelect) {
        languageSelect.value = config.currentLanguage;
        languageSelect.addEventListener("change", function () {
            var nextLanguage = languageSelect.value || config.defaultLanguage;
            postForm(config.languageEndpoint, {
                csrf_token: config.csrfToken,
                lang: nextLanguage
            }).then(function (data) {
                return applyLanguage(data.lang || nextLanguage);
            }).catch(function () {
                languageSelect.value = config.currentLanguage;
            });
        });
    }

    applyLanguage(config.currentLanguage);
}
