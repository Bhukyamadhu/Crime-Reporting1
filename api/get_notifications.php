<?php
include("../config/security.php");
include("../config/db.php");
security_start_session();

header("Content-Type: application/json; charset=UTF-8");
if (empty($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit();
}

$user_id = (int)$_SESSION["user_id"];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = trim($_POST["action"] ?? "");
    if ($action === "mark_read") {
        $notification_id = (int)($_POST["notification_id"] ?? 0);
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
        mysqli_stmt_execute($stmt);
    } elseif ($action === "mark_all_read") {
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read=1 WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
    }
}

$notifications = [];
$stmt = mysqli_prepare($conn, "SELECT id, complaint_id, message, type, is_read, created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($result && $row = mysqli_fetch_assoc($result)) $notifications[] = $row;

echo json_encode(["success" => true, "notifications" => $notifications]);
?>
