<?php
include("auth.php");
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: complaints.php");
    exit();
}
csrf_verify_or_die();

$complaint_id = (int)($_POST["complaint_id"] ?? 0);
if ($complaint_id <= 0) {
    header("Location: complaints.php");
    exit();
}

$evidence_stmt = mysqli_prepare($conn, "SELECT evidence FROM complaints WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($evidence_stmt, "i", $complaint_id);
mysqli_stmt_execute($evidence_stmt);
$evidence_result = mysqli_stmt_get_result($evidence_stmt);
if ($evidence_result && $row = mysqli_fetch_assoc($evidence_result)) {
    if (!empty($row["evidence"])) {
        $file = "../uploads/" . $row["evidence"];
        if (is_file($file)) {
            unlink($file);
        }
    }
}

$delete_stmt = mysqli_prepare($conn, "DELETE FROM complaints WHERE id=?");
mysqli_stmt_bind_param($delete_stmt, "i", $complaint_id);
mysqli_stmt_execute($delete_stmt);

header("Location: complaints.php");
exit();
?>
