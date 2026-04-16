<?php
include("auth.php");
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: complaints.php");
    exit();
}
csrf_verify_or_die();

$complaint_id = (int)($_POST["complaint_id"] ?? 0);
$status = trim($_POST["status"] ?? "");
$allowed_status = ["Pending", "Under Investigation", "Resolved"];

if ($complaint_id <= 0 || !in_array($status, $allowed_status, true)) {
    header("Location: complaints.php");
    exit();
}

$complaint_stmt = mysqli_prepare($conn, "SELECT user_id, status FROM complaints WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($complaint_stmt, "i", $complaint_id);
mysqli_stmt_execute($complaint_stmt);
$complaint_result = mysqli_stmt_get_result($complaint_stmt);
$complaint = $complaint_result ? mysqli_fetch_assoc($complaint_result) : null;
if (!$complaint) {
    header("Location: complaints.php");
    exit();
}

$stmt = mysqli_prepare($conn, "UPDATE complaints SET status=? WHERE id=?");
mysqli_stmt_bind_param($stmt, "si", $status, $complaint_id);
mysqli_stmt_execute($stmt);

$user_id = (int)$complaint["user_id"];
$old_status = (string)$complaint["status"];
$msg = "Complaint #{$complaint_id} status changed from {$old_status} to {$status}.";

if (db_table_exists($conn, "notifications")) {
    $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, complaint_id, message) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($notif_stmt, "iis", $user_id, $complaint_id, $msg);
    mysqli_stmt_execute($notif_stmt);
}

if (db_table_exists($conn, "case_updates")) {
    $admin_name = $_SESSION["admin_name"] ?? "Admin";
    $timeline = "Status updated to {$status} by {$admin_name}";
    $timeline_stmt = mysqli_prepare($conn, "INSERT INTO case_updates (complaint_id, status, note) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($timeline_stmt, "iss", $complaint_id, $status, $timeline);
    mysqli_stmt_execute($timeline_stmt);
}

// Optional email notification (requires configured mail server in XAMPP/PHP)
$user_stmt = mysqli_prepare(
    $conn,
    "SELECT u.email
     FROM complaints c
     JOIN users u ON c.user_id = u.id
     WHERE c.id=?
     LIMIT 1"
);
mysqli_stmt_bind_param($user_stmt, "i", $complaint_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
if ($user_result && $u = mysqli_fetch_assoc($user_result)) {
    @mail($u["email"], "Complaint Status Updated", $msg);
}

header("Location: complaints.php");
exit();
?>
