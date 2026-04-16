<?php
include("../config/security.php");
security_start_session();
unset($_SESSION["admin_id"], $_SESSION["admin_name"]);
set_flash_message("success", "Admin session closed successfully.");
header("Location: ../index.php");
exit();
?>
