<?php
include("../config/security.php");
include("../config/db.php");
security_start_session();

if (!isset($_SESSION["admin_id"])) {
    http_response_code(401);
    if (($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "XMLHttpRequest") {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(["success" => false, "error" => "Unauthorized"]);
        exit();
    }
    set_flash_message("danger", "Admin login required.");
    header("Location: ../admin/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") { header("Location: ../admin/complaints.php"); exit(); }
csrf_verify_or_die();

$complaint_id = (int)($_POST["complaint_id"] ?? 0);
$message = trim($_POST["message"] ?? "");
if ($complaint_id <= 0 || $message === "") { set_flash_message("danger", "Complaint and message are required."); header("Location: ../admin/complaints.php"); exit(); }

$stmt = mysqli_prepare($conn, "SELECT user_id FROM complaints WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $complaint_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$complaint = $result ? mysqli_fetch_assoc($result) : null;
if (!$complaint) { set_flash_message("danger", "Complaint not found."); header("Location: ../admin/complaints.php"); exit(); }

$user_id = (int)$complaint["user_id"];
$insert = mysqli_prepare($conn, "INSERT INTO notifications (user_id, complaint_id, message, type, is_read) VALUES (?, ?, ?, 'update', 0)");
mysqli_stmt_bind_param($insert, "iis", $user_id, $complaint_id, $message);
mysqli_stmt_execute($insert);
set_flash_message("success", "Notification sent to the user.");
header("Location: ../admin/complaints.php");
exit();
?>

