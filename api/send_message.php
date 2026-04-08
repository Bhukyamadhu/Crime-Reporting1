<?php
include("../config/security.php");
include("../config/db.php");
security_start_session();
header("Content-Type: application/json; charset=UTF-8");

$is_admin = isset($_SESSION["admin_id"]);
$is_user = !empty($_SESSION["user_id"]);
if (!$is_admin && !$is_user) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed"]);
    exit();
}

$complaint_id = (int)($_POST["complaint_id"] ?? 0);
$message_text = trim($_POST["message_text"] ?? "");
$attachment = null;
if ($complaint_id <= 0 || $message_text === "") {
    http_response_code(422);
    echo json_encode(["success" => false, "error" => "Complaint and message are required."]);
    exit();
}

$complaint_stmt = mysqli_prepare($conn, "SELECT user_id FROM complaints WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($complaint_stmt, "i", $complaint_id);
mysqli_stmt_execute($complaint_stmt);
$complaint_result = mysqli_stmt_get_result($complaint_stmt);
$complaint = $complaint_result ? mysqli_fetch_assoc($complaint_result) : null;
if (!$complaint) {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Complaint not found."]);
    exit();
}

$complaint_user_id = (int)$complaint["user_id"];
if ($is_user && (int)$_SESSION["user_id"] !== $complaint_user_id) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Access denied."]);
    exit();
}

if (isset($_FILES["attachment"]) && $_FILES["attachment"]["error"] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES["attachment"]["error"] === UPLOAD_ERR_OK) {
        $original_name = basename($_FILES["attachment"]["name"]);
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $safe_ext = preg_match('/^[a-z0-9]+$/', $ext) ? $ext : 'dat';
        $attachment = 'chat_' . hash('sha256', microtime(true) . $original_name . random_bytes(8)) . '.' . $safe_ext;
        if (!move_uploaded_file($_FILES["attachment"]["tmp_name"], "../uploads/" . $attachment)) {
            $attachment = null;
        }
    }
}

if ($is_admin) {
    $sender_id = -max(1, (int)$_SESSION["admin_id"]);
    $receiver_id = $complaint_user_id;
    $notification_text = "New message from admin on complaint #{$complaint_id}.";
} else {
    $sender_id = (int)$_SESSION["user_id"];
    $receiver_id = -1;
    $notification_text = null;
}

$insert = mysqli_prepare($conn, "INSERT INTO messages (complaint_id, sender_id, receiver_id, message_text, attachment, is_seen) VALUES (?, ?, ?, ?, ?, 0)");
mysqli_stmt_bind_param($insert, "iiiss", $complaint_id, $sender_id, $receiver_id, $message_text, $attachment);
if (!mysqli_stmt_execute($insert)) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Unable to send message."]);
    exit();
}

if ($notification_text !== null) {
    $notif = mysqli_prepare($conn, "INSERT INTO notifications (user_id, complaint_id, message, type, is_read) VALUES (?, ?, ?, 'chat', 0)");
    mysqli_stmt_bind_param($notif, "iis", $complaint_user_id, $complaint_id, $notification_text);
    mysqli_stmt_execute($notif);
}

echo json_encode(["success" => true, "message_id" => (int)mysqli_insert_id($conn)]);
?>
