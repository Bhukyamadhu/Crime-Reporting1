<?php
$conn = mysqli_connect("localhost", "root", "", "crime_db");
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

function db_has_column($conn, $table, $column) {
    static $cache = [];
    $key = $table . "." . $column;
    if (isset($cache[$key])) return $cache[$key];
    $table_safe = mysqli_real_escape_string($conn, $table);
    $column_safe = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `{$table_safe}` LIKE '{$column_safe}'");
    $cache[$key] = ($res && mysqli_num_rows($res) > 0);
    return $cache[$key];
}

function db_table_exists($conn, $table) {
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];
    $table_safe = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '{$table_safe}'");
    $cache[$table] = ($res && mysqli_num_rows($res) > 0);
    return $cache[$table];
}

function db_exec_safely($conn, $sql) {
    @mysqli_query($conn, $sql);
}

function db_ensure_schema($conn) {
    static $bootstrapped = false;
    if ($bootstrapped) return;
    $bootstrapped = true;

    if (db_table_exists($conn, "complaints")) {
        if (!db_has_column($conn, "complaints", "address")) db_exec_safely($conn, "ALTER TABLE complaints ADD COLUMN address VARCHAR(255) NULL AFTER longitude");
        if (!db_has_column($conn, "complaints", "full_name")) db_exec_safely($conn, "ALTER TABLE complaints ADD COLUMN full_name VARCHAR(120) NULL AFTER user_id");
        if (!db_has_column($conn, "complaints", "age")) db_exec_safely($conn, "ALTER TABLE complaints ADD COLUMN age INT NULL AFTER full_name");
        if (!db_has_column($conn, "complaints", "nationality")) db_exec_safely($conn, "ALTER TABLE complaints ADD COLUMN nationality VARCHAR(80) NULL AFTER age");
        if (!db_has_column($conn, "complaints", "phone_number")) db_exec_safely($conn, "ALTER TABLE complaints ADD COLUMN phone_number VARCHAR(20) NULL AFTER nationality");
        if (!db_has_column($conn, "complaints", "reporter_address")) db_exec_safely($conn, "ALTER TABLE complaints ADD COLUMN reporter_address VARCHAR(255) NULL AFTER phone_number");
        if (!db_has_column($conn, "complaints", "created_at")) db_exec_safely($conn, "ALTER TABLE complaints ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }

    db_exec_safely($conn, "CREATE TABLE IF NOT EXISTS notifications (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, complaint_id INT UNSIGNED NULL, message VARCHAR(255) NOT NULL, type VARCHAR(30) NOT NULL DEFAULT 'update', is_read TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_notifications_user (user_id), INDEX idx_notifications_read (is_read), INDEX idx_notifications_created (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if (db_table_exists($conn, "notifications") && !db_has_column($conn, "notifications", "complaint_id")) db_exec_safely($conn, "ALTER TABLE notifications ADD COLUMN complaint_id INT UNSIGNED NULL AFTER user_id");
    if (db_table_exists($conn, "notifications") && !db_has_column($conn, "notifications", "type")) db_exec_safely($conn, "ALTER TABLE notifications ADD COLUMN type VARCHAR(30) NOT NULL DEFAULT 'update' AFTER message");

    db_exec_safely($conn, "CREATE TABLE IF NOT EXISTS messages (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, complaint_id INT UNSIGNED NOT NULL, sender_id INT NOT NULL, receiver_id INT NOT NULL, message_text TEXT NOT NULL, attachment VARCHAR(255) NULL, sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, is_seen TINYINT(1) NOT NULL DEFAULT 0, INDEX idx_messages_complaint (complaint_id), INDEX idx_messages_seen (is_seen), INDEX idx_messages_sent (sent_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

db_ensure_schema($conn);
?>
