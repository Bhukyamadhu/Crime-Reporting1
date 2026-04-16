<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

function json_error(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode([
        "success" => false,
        "error" => $message
    ]);
    exit();
}

function json_success(string $reply, string $source = "fallback"): void
{
    echo json_encode([
        "success" => true,
        "reply" => $reply,
        "source" => $source
    ]);
    exit();
}

function fallback_reply(string $message): string
{
    $text = mb_strtolower($message);

    $manual_answers = [
        "how do i report a crime?" => "To report a crime, open the Report Crime page, choose the incident type, enter the location, add the incident details, upload any supporting evidence, then review and submit your complaint.",
        "how can i upload evidence?" => "You can upload evidence while submitting a complaint. Add clear details first, then attach relevant images or files that are readable and directly connected to the incident.",
        "how do i track my complaint?" => "Open Dashboard or Track Complaint to check your complaint history, current status, and any updates linked to your report.",
        "what should i do in an emergency?" => "If there is immediate danger or an urgent emergency, call 100 or 112 right away. This website is only for non-emergency reporting."
    ];

    if (isset($manual_answers[$text])) {
        return $manual_answers[$text];
    }

    if (str_contains($text, "emergency") || str_contains($text, "urgent") || str_contains($text, "help now")) {
        return "If this is an emergency or someone is in immediate danger, call 100 or 112 right away. This portal is meant for non-emergency reporting only.";
    }

    if (str_contains($text, "report") || str_contains($text, "complaint") || str_contains($text, "crime")) {
        return "To report a crime, open the Report Crime page, choose the incident type, enter the location, add details, upload any evidence, then submit the complaint for review.";
    }

    if (str_contains($text, "evidence") || str_contains($text, "photo") || str_contains($text, "image") || str_contains($text, "upload")) {
        return "You can attach supporting evidence while filing a complaint. Add clear incident details, upload relevant images, and make sure the files are readable and directly related to the report.";
    }

    if (str_contains($text, "track") || str_contains($text, "status") || str_contains($text, "dashboard")) {
        return "You can track complaint progress from your dashboard. Open Track Complaint or Dashboard to review complaint history, status updates, and any evidence linked to your report.";
    }

    if (str_contains($text, "login") || str_contains($text, "account") || str_contains($text, "register")) {
        return "Use the citizen login page to access your account. After signing in, you can submit reports, upload evidence, and monitor complaint updates from the dashboard.";
    }

    return "I can help with reporting crimes, uploading evidence, tracking complaint status, and emergency guidance. Tell me what you need help with.";
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_error(405, "Method not allowed. Use POST.");
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
    json_error(400, "Message is required.");
}

if (mb_strlen($message) > 1000) {
    $message = mb_substr($message, 0, 1000);
}

$system_prompt = "You are an assistant for a Crime Reporting System website. Help users report crimes, upload evidence, track complaints, and provide emergency guidance. Keep answers short, practical, and safe.";
$full_prompt = $system_prompt . "\n\nUser: " . $message . "\nAssistant:";

$payload = [
    "model" => "qwen2.5:1.5b",
    "prompt" => $full_prompt,
    "stream" => false
];

if (!function_exists("curl_init")) {
    json_success(fallback_reply($message), "manual");
}

$ch = curl_init("http://localhost:11434/api/generate");
if ($ch === false) {
    json_success(fallback_reply($message), "manual");
}

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 45);

$response = curl_exec($ch);

if ($response === false) {
    curl_close($ch);
    json_success(fallback_reply($message), "manual");
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$decoded = json_decode($response, true);

if ($http_code >= 400 || !is_array($decoded)) {
    json_success(fallback_reply($message), "manual");
}

$ai_text = trim((string)($decoded["response"] ?? ""));

if ($ai_text === "") {
    json_success(fallback_reply($message), "manual");
}

json_success($ai_text, "ollama");
