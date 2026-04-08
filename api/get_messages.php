<?php
include("../config/security.php");
include("../config/db.php");
security_start_session();
header("Content-Type: application/json; charset=UTF-8");

$is_admin = isset($_SESSION["admin_id"]);
$is_user = !empty($_SESSION["user_id"]);
if (!$is_admin && !$is_user) {
    http_response_code(401);
    echo json_encode(["success" => false, "status" => "error", "error" => "Unauthorized"]);
    exit();
}

$complaint_id = (int)($_GET["complaint_id"] ?? 0);
if ($complaint_id <= 0) {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "error" => "Complaint ID is required."]);
    exit();
}

$complaint_stmt = mysqli_prepare($conn, "SELECT user_id FROM complaints WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($complaint_stmt, "i", $complaint_id);
mysqli_stmt_execute($complaint_stmt);
$complaint_result = mysqli_stmt_get_result($complaint_stmt);
$complaint = $complaint_result ? mysqli_fetch_assoc($complaint_result) : null;
if (!$complaint) {
    http_response_code(404);
    echo json_encode(["success" => false, "status" => "error", "error" => "Complaint not found."]);
    exit();
}

$complaint_user_id = (int)$complaint["user_id"];
if ($is_user && (int)$_SESSION["user_id"] !== $complaint_user_id) {
    http_response_code(403);
    echo json_encode(["success" => false, "status" => "error", "error" => "Access denied."]);
    exit();
}

$messages = [];
$stmt = mysqli_prepare($conn, "SELECT id, complaint_id, sender_id, receiver_id, message_text, attachment, sent_at, is_seen FROM messages WHERE complaint_id=? ORDER BY sent_at ASC, id ASC");
mysqli_stmt_bind_param($stmt, "i", $complaint_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($result && $row = mysqli_fetch_assoc($result)) {
    $is_admin_sender = ((int)$row["sender_id"] < 0);
    $row["sender_role"] = $is_admin_sender ? "admin" : "user";
    $row["sender_label"] = $is_admin_sender ? "Admin" : "You";
    if ($is_admin && !$is_admin_sender) $row["sender_label"] = "User";
    if ($is_user && $is_admin_sender) $row["sender_label"] = "Admin";
    $messages[] = $row;
}

$html = "";
foreach ($messages as $row) {
    $role = $row["sender_role"] === "admin" ? "admin" : "user";
    $sender = $role === "admin" ? "Admin" : "User";
    $status = $role === "user" ? (((int)$row["is_seen"] === 1) ? "Seen" : "Delivered") : "";
    $attachment = "";
    if (!empty($row["attachment"])) {
        $attachment = '<div class="mt-2"><a href="../uploads/' . rawurlencode($row["attachment"]) . '" target="_blank" rel="noopener">View attachment</a></div>';
    }
    $html .= '<div class="case-msg ' . $role . '"><div><div class="case-meta">' . h($sender) . ' · ' . h($row["sent_at"]) . ($status !== "" ? ' · ' . h($status) : '') . '</div><div class="case-bubble">' . nl2br(h($row["message_text"])) . $attachment . '</div></div></div>';
}
if ($html === "") {
    $html = '<div class="empty-state">No messages yet. Start the conversation.</div>';
}

echo json_encode(["success" => true, "status" => "success", "messages" => $messages, "html" => $html]);
?>

