<?php
include("../config/security.php");
security_start_session();
session_unset();
session_destroy();
security_start_session();
set_flash_message("success", "You have been logged out successfully.");
header("Location: ../index.php");
exit();
?>
