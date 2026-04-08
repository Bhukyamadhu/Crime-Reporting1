<?php

function translation_supported_languages() {
    static $languages = null;

    if ($languages !== null) {
        return $languages;
    }

    $languages = [
        "EN" => ["label" => "English", "native" => "English", "html" => "en"],
        "HI" => ["label" => "Hindi", "native" => "हिन्दी", "html" => "hi"],
        "TE" => ["label" => "Telugu", "native" => "తెలుగు", "html" => "te"],
        "TA" => ["label" => "Tamil", "native" => "தமிழ்", "html" => "ta"],
        "KN" => ["label" => "Kannada", "native" => "ಕನ್ನಡ", "html" => "kn"],
        "MR" => ["label" => "Marathi", "native" => "मराठी", "html" => "mr"],
        "DE" => ["label" => "German", "native" => "Deutsch", "html" => "de"],
        "FR" => ["label" => "French", "native" => "Francais", "html" => "fr"],
        "ES" => ["label" => "Spanish", "native" => "Espanol", "html" => "es"]
    ];

    return $languages;
}

function translation_supported_indic_languages() {
    return ["HI", "TE", "TA", "KN", "MR"];
}

function translation_default_language() {
    return "EN";
}

function translation_normalize_language($language) {
    $language = strtoupper(trim((string)$language));
    if ($language === "") {
        return translation_default_language();
    }

    $languages = translation_supported_languages();
    return isset($languages[$language]) ? $language : translation_default_language();
}

function translation_get_session_language() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        security_start_session();
    }

    if (empty($_SESSION["lang"])) {
        $_SESSION["lang"] = translation_default_language();
    }

    return translation_normalize_language($_SESSION["lang"]);
}

function translation_set_session_language($language) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        security_start_session();
    }

    $_SESSION["lang"] = translation_normalize_language($language);
    return $_SESSION["lang"];
}

function translation_get_html_lang() {
    $languages = translation_supported_languages();
    $current = translation_get_session_language();
    return $languages[$current]["html"] ?? "en";
}

function translation_is_indic_language($language) {
    return in_array(translation_normalize_language($language), translation_supported_indic_languages(), true);
}

function translation_config($key, $default = "") {
    $value = getenv($key);
    return ($value === false || $value === null || $value === "") ? $default : $value;
}

function translation_local_dictionary() {
    static $dictionary = null;

    if ($dictionary !== null) {
        return $dictionary;
    }

    $dictionary = [
        "Crime Reporting System" => [
            "HI" => "अपराध रिपोर्टिंग प्रणाली",
            "TE" => "నేర నివేదన వ్యవస్థ",
            "TA" => "குற்ற புகார் அமைப்பு",
            "KN" => "ಅಪರಾಧ ವರದಿ ವ್ಯವಸ್ಥೆ",
            "MR" => "गुन्हे नोंदणी प्रणाली"
        ],
        "Smart city incident response portal" => [
            "HI" => "स्मार्ट सिटी घटना प्रतिक्रिया पोर्टल",
            "TE" => "స్మార్ట్ సిటీ ఘటన ప్రతిస్పందన పోర్టల్",
            "TA" => "ஸ்மார்ட் நகர சம்பவ பதில் தளம்",
            "KN" => "ಸ್ಮಾರ್ಟ್ ಸಿಟಿ ಘಟನೆ ಪ್ರತಿಕ್ರಿಯೆ ಪೋರ್ಟಲ್",
            "MR" => "स्मार्ट सिटी घटना प्रतिसाद पोर्टल"
        ],
        "Citizen service dashboard" => [
            "HI" => "नागरिक सेवा डैशबोर्ड",
            "TE" => "పౌర సేవల డ్యాష్‌బోర్డ్",
            "TA" => "குடிமக்கள் சேவை டாஷ்போர்டு",
            "KN" => "ನಾಗರಿಕ ಸೇವಾ ಡ್ಯಾಶ್‌ಬೋರ್ಡ್",
            "MR" => "नागरिक सेवा डॅशबोर्ड"
        ],
        "Public statistics dashboard" => [
            "HI" => "सार्वजनिक सांख्यिकी डैशबोर्ड",
            "TE" => "పబ్లిక్ గణాంకాల డ్యాష్‌బోర్డ్",
            "TA" => "பொது புள்ளிவிவர டாஷ்போர்டு",
            "KN" => "ಸಾರ್ವಜನಿಕ ಅಂಕಿಅಂಶ ಡ್ಯಾಶ್‌ಬೋರ್ಡ್",
            "MR" => "सार्वजनिक सांख्यिकी डॅशबोर्ड"
        ],
        "Home" => ["HI" => "होम", "TE" => "హోమ్", "TA" => "முகப்பு", "KN" => "ಮುಖಪುಟ", "MR" => "मुख्यपृष्ठ"],
        "Report Crime" => ["HI" => "अपराध रिपोर्ट करें", "TE" => "నేరాన్ని నివేదించండి", "TA" => "குற்றத்தை புகாரளிக்கவும்", "KN" => "ಅಪರಾಧವನ್ನು ವರದಿ ಮಾಡಿ", "MR" => "गुन्ह्याची तक्रार करा"],
        "Dashboard" => ["HI" => "डैशबोर्ड", "TE" => "డ్యాష్‌బోర్డ్", "TA" => "டாஷ்போர்டு", "KN" => "ಡ್ಯಾಶ್‌ಬೋರ್ಡ್", "MR" => "डॅशबोर्ड"],
        "Track Complaint" => ["HI" => "शिकायत ट्रैक करें", "TE" => "ఫిర్యాదును ట్రాక్ చేయండి", "TA" => "புகாரை கண்காணிக்கவும்", "KN" => "ದೂರನ್ನು ಟ್ರಾಕ್ ಮಾಡಿ", "MR" => "तक्रार ट्रॅक करा"],
        "Statistics" => ["HI" => "सांख्यिकी", "TE" => "గణాంకాలు", "TA" => "புள்ளிவிவரங்கள்", "KN" => "ಅಂಕಿಅಂಶಗಳು", "MR" => "सांख्यिकी"],
        "Login" => ["HI" => "लॉगिन", "TE" => "లాగిన్", "TA" => "உள்நுழை", "KN" => "ಲಾಗಿನ್", "MR" => "लॉगिन"],
        "Logout" => ["HI" => "लॉगआउट", "TE" => "లాగౌట్", "TA" => "வெளியேறு", "KN" => "ಲಾಗ್ ಔಟ್", "MR" => "लॉगआउट"],
        "Citizen Login" => ["HI" => "नागरिक लॉगिन", "TE" => "పౌరుల లాగిన్", "TA" => "குடிமக்கள் உள்நுழைவு", "KN" => "ನಾಗರಿಕ ಲಾಗಿನ್", "MR" => "नागरिक लॉगिन"],
        "Admin Access" => ["HI" => "एडमिन एक्सेस", "TE" => "అడ్మిన్ యాక్సెస్", "TA" => "நிர்வாக அணுகல்", "KN" => "ನಿರ್ವಾಹಕ ಪ್ರವೇಶ", "MR" => "अ‍ॅडमिन प्रवेश"],
        "My Dashboard" => ["HI" => "मेरा डैशबोर्ड", "TE" => "నా డ్యాష్‌బోర్డ్", "TA" => "என் டாஷ்போர்டு", "KN" => "ನನ್ನ ಡ್ಯಾಶ್‌ಬೋರ್ಡ್", "MR" => "माझा डॅशबोर्ड"],
        "Admin Dashboard" => ["HI" => "एडमिन डैशबोर्ड", "TE" => "అడ్మిన్ డ్యాష్‌బోర్డ్", "TA" => "நிர்வாக டாஷ்போர்டு", "KN" => "ನಿರ್ವಾಹಕ ಡ್ಯಾಶ್‌ಬೋರ್ಡ್", "MR" => "अ‍ॅडमिन डॅशबोर्ड"],
        "Language" => ["HI" => "भाषा", "TE" => "భాష", "TA" => "மொழி", "KN" => "ಭಾಷೆ", "MR" => "भाषा"],
        "Original" => ["HI" => "मूल", "TE" => "అసలు", "TA" => "அசல்", "KN" => "ಮೂಲ", "MR" => "मूळ"],
        "Translated" => ["HI" => "अनुवादित", "TE" => "అనువాదం", "TA" => "மொழிபெயர்ப்பு", "KN" => "ಅನುವಾದ", "MR" => "अनुवादित"],
        "View original" => ["HI" => "मूल देखें", "TE" => "అసలును చూడండి", "TA" => "அசலை காண்க", "KN" => "ಮೂಲವನ್ನು ನೋಡಿ", "MR" => "मूळ पहा"],
        "View translated" => ["HI" => "अनुवाद देखें", "TE" => "అనువాదాన్ని చూడండి", "TA" => "மொழிபெயர்ப்பை காண்க", "KN" => "ಅನುವಾದವನ್ನು ನೋಡಿ", "MR" => "अनुवाद पहा"],
        "Translation unavailable" => ["HI" => "अनुवाद उपलब्ध नहीं है", "TE" => "అనువాదం అందుబాటులో లేదు", "TA" => "மொழிபெயர்ப்பு இல்லை", "KN" => "ಅನುವಾದ ಲಭ್ಯವಿಲ್ಲ", "MR" => "अनुवाद उपलब्ध नाही"],
        "Pending" => ["HI" => "लंबित", "TE" => "పెండింగ్", "TA" => "நிலுவை", "KN" => "ಬಾಕಿ", "MR" => "प्रलंबित"],
        "Under Investigation" => ["HI" => "जांच के अधीन", "TE" => "దర్యాప్తులో ఉంది", "TA" => "விசாரணையில்", "KN" => "ತನಿಖೆಯಲ್ಲಿದೆ", "MR" => "चौकशीअंतर्गत"],
        "Resolved" => ["HI" => "सुलझाया गया", "TE" => "పరిష్కరించబడింది", "TA" => "தீர்க்கப்பட்டது", "KN" => "ಪರಿಹರಿಸಲಾಗಿದೆ", "MR" => "निकाली काढले"],
        "No file" => ["HI" => "कोई फ़ाइल नहीं", "TE" => "ఫైల్ లేదు", "TA" => "கோப்பு இல்லை", "KN" => "ಫೈಲ್ ಇಲ್ಲ", "MR" => "फाइल नाही"],
        "No complaints found." => ["HI" => "कोई शिकायत नहीं मिली।", "TE" => "ఏ ఫిర్యాదులు కనబడలేదు.", "TA" => "புகார்கள் எதுவும் இல்லை.", "KN" => "ಯಾವ ದೂರುಗಳೂ ಸಿಗಲಿಲ್ಲ.", "MR" => "तक्रारी आढळल्या नाहीत."],
        "No complaints submitted yet." => ["HI" => "अभी तक कोई शिकायत दर्ज नहीं की गई है।", "TE" => "ఇంకా ఎలాంటి ఫిర్యాదులు సమర్పించలేదు.", "TA" => "இன்னும் எந்த புகாரும் சமர்ப்பிக்கப்படவில்லை.", "KN" => "ಇನ್ನೂ ಯಾವುದೇ ದೂರು ಸಲ್ಲಿಸಲಾಗಿಲ್ಲ.", "MR" => "अजून कोणतीही तक्रार सादर केलेली नाही."],
        "No notifications yet." => ["HI" => "अभी तक कोई सूचना नहीं।", "TE" => "ఇంకా నోటిఫికేషన్లు లేవు.", "TA" => "இன்னும் அறிவிப்புகள் இல்லை.", "KN" => "ಇನ್ನೂ ಸೂಚನೆಗಳಿಲ್ಲ.", "MR" => "अजून कोणत्याही सूचना नाहीत."]
    ];

    return $dictionary;
}

function translation_from_local_dictionary($text, $target_language) {
    $target_language = translation_normalize_language($target_language);
    $text = trim((string)$text);
    if ($text === "" || $target_language === "EN") {
        return $text;
    }

    $dictionary = translation_local_dictionary();
    return $dictionary[$text][$target_language] ?? null;
}

function translation_detect_source_language($text) {
    $text = (string)$text;
    if ($text === "") {
        return "EN";
    }

    if (preg_match('/[\x{0900}-\x{097F}]/u', $text)) {
        return "HI";
    }
    if (preg_match('/[\x{0C00}-\x{0C7F}]/u', $text)) {
        return "TE";
    }
    if (preg_match('/[\x{0B80}-\x{0BFF}]/u', $text)) {
        return "TA";
    }
    if (preg_match('/[\x{0C80}-\x{0CFF}]/u', $text)) {
        return "KN";
    }
    if (preg_match('/[\x{0900}-\x{097F}]/u', $text)) {
        return "MR";
    }
    return "EN";
}

function translation_http_post_json($url, $payload, $headers = []) {
    if (!function_exists("curl_init")) {
        return [null, "cURL is not available on this server."];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => array_merge(["Content-Type: application/json"], $headers),
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($response === false) {
        return [null, $error ?: "Translation request failed."];
    }

    $decoded = json_decode($response, true);
    if ($status >= 400) {
        return [null, $decoded["error"]["message"] ?? $decoded["error"] ?? ("HTTP " . $status)];
    }

    return [$decoded, null];
}

function translation_http_post_form($url, $payload, $headers = []) {
    if (!function_exists("curl_init")) {
        return [null, "cURL is not available on this server."];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_TIMEOUT => 15
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($response === false) {
        return [null, $error ?: "Translation request failed."];
    }

    $decoded = json_decode($response, true);
    if ($status >= 400) {
        return [null, $decoded["message"] ?? $decoded["error"] ?? ("HTTP " . $status)];
    }

    return [$decoded, null];
}

function translation_via_google($text, $target_language) {
    $api_key = translation_config("GOOGLE_TRANSLATE_API_KEY");
    if ($api_key === "") {
        return [null, "Google Translate API key not configured."];
    }

    $url = "https://translation.googleapis.com/language/translate/v2?key=" . rawurlencode($api_key);
    [$decoded, $error] = translation_http_post_json($url, [
        "q" => $text,
        "target" => strtolower($target_language),
        "format" => "text"
    ]);

    if ($error !== null) {
        return [null, $error];
    }

    $translated = $decoded["data"]["translations"][0]["translatedText"] ?? null;
    return [$translated, $translated ? null : "Google Translate returned an empty result."];
}

function translation_via_libretranslate($text, $target_language) {
    $base_url = rtrim(translation_config("LIBRETRANSLATE_URL"), "/");
    if ($base_url === "") {
        return [null, "LibreTranslate URL not configured."];
    }

    $api_key = translation_config("LIBRETRANSLATE_API_KEY");
    $payload = [
        "q" => $text,
        "source" => "auto",
        "target" => strtolower($target_language),
        "format" => "text"
    ];
    if ($api_key !== "") {
        $payload["api_key"] = $api_key;
    }

    [$decoded, $error] = translation_http_post_form($base_url . "/translate", $payload);
    if ($error !== null) {
        return [null, $error];
    }

    $translated = $decoded["translatedText"] ?? null;
    return [$translated, $translated ? null : "LibreTranslate returned an empty result."];
}

function translation_via_deepl($text, $target_language) {
    $api_key = translation_config("DEEPL_API_KEY");
    if ($api_key === "") {
        return [null, "DeepL API key not configured."];
    }

    $api_url = rtrim(translation_config("DEEPL_API_URL", "https://api-free.deepl.com/v2/translate"), "/");
    [$decoded, $error] = translation_http_post_form($api_url, [
        "text" => $text,
        "target_lang" => $target_language
    ], ["Authorization: DeepL-Auth-Key " . $api_key]);

    if ($error !== null) {
        return [null, $error];
    }

    $translated = $decoded["translations"][0]["text"] ?? null;
    return [$translated, $translated ? null : "DeepL returned an empty result."];
}

function translation_cache_key($text, $target_language) {
    return "translation_" . md5($target_language . "|" . $text);
}

function translation_text($text, $target_language) {
    $text = trim((string)$text);
    $target_language = translation_normalize_language($target_language);
    $source_language = translation_detect_source_language($text);

    if ($text === "") {
        return ["translated_text" => "", "source_language" => $source_language, "target_language" => $target_language, "provider" => "none", "error" => null];
    }

    if ($target_language === "EN" || $target_language === $source_language) {
        return ["translated_text" => $text, "source_language" => $source_language, "target_language" => $target_language, "provider" => "none", "error" => null];
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        security_start_session();
    }

    $cache_key = translation_cache_key($text, $target_language);
    if (!empty($_SESSION[$cache_key]) && is_array($_SESSION[$cache_key])) {
        return $_SESSION[$cache_key];
    }

    $local = translation_from_local_dictionary($text, $target_language);
    if ($local !== null) {
        $result = ["translated_text" => $local, "source_language" => $source_language, "target_language" => $target_language, "provider" => "local", "error" => null];
        $_SESSION[$cache_key] = $result;
        return $result;
    }

    $providers = translation_is_indic_language($target_language)
        ? ["google", "libretranslate", "local"]
        : ["deepl", "google", "libretranslate", "local"];

    $last_error = null;
    foreach ($providers as $provider) {
        if ($provider === "google") {
            [$translated, $error] = translation_via_google($text, $target_language);
        } elseif ($provider === "libretranslate") {
            [$translated, $error] = translation_via_libretranslate($text, $target_language);
        } elseif ($provider === "deepl") {
            [$translated, $error] = translation_via_deepl($text, $target_language);
        } else {
            $translated = translation_from_local_dictionary($text, $target_language);
            $error = $translated === null ? "Local translation not found." : null;
        }

        if (is_string($translated) && trim($translated) !== "") {
            $result = ["translated_text" => $translated, "source_language" => $source_language, "target_language" => $target_language, "provider" => $provider, "error" => null];
            $_SESSION[$cache_key] = $result;
            return $result;
        }

        $last_error = $error;
    }

    return ["translated_text" => $text, "source_language" => $source_language, "target_language" => $target_language, "provider" => "fallback", "error" => $last_error ?: "Translation unavailable."];
}

function translation_render_language_selector($path_prefix = "") {
    $path_prefix = (string)$path_prefix;
    $current_language = translation_get_session_language();
    $languages = translation_supported_languages();
    ?>
    <div class="language-switcher" data-no-translate="1">
        <label for="appLanguageSelect" class="form-label mb-0 small fw-semibold text-uppercase" data-i18n="Language">Language</label>
        <select id="appLanguageSelect" class="form-select form-select-sm app-language-select" aria-label="Select language">
            <?php foreach ($languages as $code => $meta) { ?>
                <?php if (!in_array($code, ["EN", "HI", "TE", "TA", "KN", "MR"], true)) { continue; } ?>
                <option value="<?php echo htmlspecialchars($code, ENT_QUOTES, "UTF-8"); ?>" <?php echo $code === $current_language ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($meta["label"] . " (" . $code . ")", ENT_QUOTES, "UTF-8"); ?>
                </option>
            <?php } ?>
        </select>
    </div>
    <?php
}

function translation_render_page_config($path_prefix = "") {
    $prefix = rtrim((string)$path_prefix, "/");
    if ($prefix !== "") {
        $prefix .= "/";
    }
    ?>
    <script>
    window.APP_TRANSLATION = {
        currentLanguage: <?php echo json_encode(translation_get_session_language()); ?>,
        defaultLanguage: <?php echo json_encode(translation_default_language()); ?>,
        supportedLanguages: <?php echo json_encode(array_intersect_key(translation_supported_languages(), array_flip(["EN", "HI", "TE", "TA", "KN", "MR"]))); ?>,
        translateEndpoint: <?php echo json_encode($prefix . "api/translate.php"); ?>,
        languageEndpoint: <?php echo json_encode($prefix . "api/set_language.php"); ?>,
        csrfToken: <?php echo json_encode(csrf_token()); ?>
    };
    </script>
    <?php
}

