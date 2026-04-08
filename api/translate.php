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

$text = isset($_POST["text"]) ? trim((string)$_POST["text"]) : "";
$target_language = translation_normalize_language($_POST["target_language"] ?? translation_get_session_language());

if ($text === "") {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "Text is required."]);
    exit();
}

$result = translation_text($text, $target_language);

echo json_encode([
    "success" => true,
    "text" => $text,
    "translated_text" => $result["translated_text"],
    "source_language" => $result["source_language"],
    "target_language" => $result["target_language"],
    "provider" => $result["provider"],
    "error" => $result["error"]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

