<?php
include_once(__DIR__ . "/../config/security.php");

security_start_session();

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
    exit();
}

csrf_verify_or_die();

$language = translation_set_session_language($_POST["lang"] ?? translation_default_language());

echo json_encode([
    "success" => true,
    "lang" => $language,
    "html_lang" => translation_get_html_lang()
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
