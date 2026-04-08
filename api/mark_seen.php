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
if ($complaint_id <= 0) {
    http_response_code(422);
    echo json_encode(["success" => false, "error" => "Complaint ID is required."]);
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

if ($is_admin) {
    $stmt = mysqli_prepare($conn, "UPDATE messages SET is_seen=1 WHERE complaint_id=? AND sender_id > 0 AND receiver_id < 0");
    mysqli_stmt_bind_param($stmt, "i", $complaint_id);
} else {
    $receiver_id = (int)$_SESSION["user_id"];
    $stmt = mysqli_prepare($conn, "UPDATE messages SET is_seen=1 WHERE complaint_id=? AND sender_id < 0 AND receiver_id=?");
    mysqli_stmt_bind_param($stmt, "ii", $complaint_id, $receiver_id);
}
mysqli_stmt_execute($stmt);

echo json_encode(["success" => true]);
?>
