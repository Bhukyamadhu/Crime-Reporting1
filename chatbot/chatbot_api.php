<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed. Use POST."
    ]);
    exit();
}

$raw_input = file_get_contents("php://input");
$json_data = json_decode($raw_input, true);

$message = "";
if (is_array($json_data) && isset($json_data["message"])) {
    $message = trim((string)$json_data["message"]);
} elseif (isset($_POST["message"])) {
    $message = trim((string)$_POST["message"]);
}

if ($message === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Message is required."
    ]);
    exit();
}

if (mb_strlen($message) > 1000) {
    $message = mb_substr($message, 0, 1000);
}

$system_prompt = "You are an assistant for a Crime Reporting System website. Help users report crimes, upload evidence, track complaints, and provide emergency guidance.";
$full_prompt = $system_prompt . "\n\nUser: " . $message . "\nAssistant:";

$payload = [
    "model" => "qwen2.5:1.5b",
    "prompt" => $full_prompt,
    "stream" => false
];

$ch = curl_init("http://localhost:11434/api/generate");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 45);

$response = curl_exec($ch);

if ($response === false) {
    $curl_error = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Unable to connect to Ollama API: " . $curl_error
    ]);
    exit();
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$decoded = json_decode($response, true);

if ($http_code >= 400 || !is_array($decoded)) {
    http_response_code(502);
    echo json_encode([
        "success" => false,
        "error" => "Invalid response from Ollama API."
    ]);
    exit();
}

$ai_text = trim((string)($decoded["response"] ?? ""));

if ($ai_text === "") {
    http_response_code(502);
    echo json_encode([
        "success" => false,
        "error" => "Ollama returned an empty response."
    ]);
    exit();
}

echo json_encode([
    "success" => true,
    "reply" => $ai_text
]);

