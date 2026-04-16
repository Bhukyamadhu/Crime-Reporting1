<?php
include("config/db.php");

$result = mysqli_query($conn, "SELECT DATABASE()");
$row = mysqli_fetch_row($result);

echo "Currently connected to database: " . $row[0];
?>