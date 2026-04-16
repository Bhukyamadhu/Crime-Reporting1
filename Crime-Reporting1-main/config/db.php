<?php
$conn = mysqli_connect("localhost", "root", "", "crime_db");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

function db_has_column($conn, $table, $column) {
    static $cache = [];
    $key = $table . "." . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $table_safe = mysqli_real_escape_string($conn, $table);
    $column_safe = mysqli_real_escape_string($conn, $column);
    $sql = "SHOW COLUMNS FROM `{$table_safe}` LIKE '{$column_safe}'";
    $res = mysqli_query($conn, $sql);
    $cache[$key] = ($res && mysqli_num_rows($res) > 0);
    return $cache[$key];
}

function db_table_exists($conn, $table) {
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    $table_safe = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '{$table_safe}'");
    $cache[$table] = ($res && mysqli_num_rows($res) > 0);
    return $cache[$table];
}
?>
