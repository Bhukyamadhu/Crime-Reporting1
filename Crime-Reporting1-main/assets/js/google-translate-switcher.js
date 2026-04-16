document.addEventListener("DOMContentLoaded", function () {
    var switchers = Array.prototype.slice.call(document.querySelectorAll("[data-google-translate-switcher]"));
    if (!switchers.length) {
        return;
    }

    var storageKey = "crime_site_language";
    var savedLang = localStorage.getItem(storageKey) || getLanguageFromCookie() || "en";

    setLanguageCookie(savedLang);
    syncSwitchers(savedLang);

    switchers.forEach(function (switcher) {
        switcher.addEventListener("change", function () {
            var targetLang = switcher.value || "en";
            localStorage.setItem(storageKey, targetLang);
            setLanguageCookie(targetLang);
            syncSwitchers(targetLang);
            window.location.reload();
        });
    });
});

function googleTranslateElementInit() {
    if (!window.google || !window.google.translate) {
        return;
    }

    new window.google.translate.TranslateElement(
        {
            pageLanguage: "en",
            includedLanguages: "en,hi",
            autoDisplay: false,
            multilanguagePage: false
        },
        "google_translate_element"
    );
}

function syncSwitchers(lang) {
    document.querySelectorAll("[data-google-translate-switcher]").forEach(function (switcher) {
        switcher.value = lang;
    });
}

function setLanguageCookie(lang) {
    var cookieValue = lang === "hi" ? "/en/hi" : "/en/en";
    document.cookie = "googtrans=" + cookieValue + ";path=/";
}

function getLanguageFromCookie() {
    var match = document.cookie.match(/(?:^|;\\s*)googtrans=([^;]+)/);
    if (!match) {
        return "";
    }

    if (match[1].indexOf("/en/hi") !== -1) {
        return "hi";
    }

    return "en";
}
