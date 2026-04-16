<?php
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "crime_db";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

if (!defined("DB_DEFAULT_ADMIN_USERNAME")) {
    define("DB_DEFAULT_ADMIN_USERNAME", "admin");
}
if (!defined("DB_DEFAULT_ADMIN_NAME")) {
    define("DB_DEFAULT_ADMIN_NAME", "Admin");
}
if (!defined("DB_DEFAULT_ADMIN_EMAIL")) {
    define("DB_DEFAULT_ADMIN_EMAIL", "admin@crime.local");
}
if (!defined("DB_DEFAULT_ADMIN_PASSWORD")) {
    define("DB_DEFAULT_ADMIN_PASSWORD", "Admin@123");
}
if (!defined("DB_DEFAULT_ADMIN_LEGACY_BAD_HASH")) {
    define(
        "DB_DEFAULT_ADMIN_LEGACY_BAD_HASH",
        '$2y$10.BOYxBMZwm394nafwiUurq65844/MAveoy.g4m/zZBKprlbJBXe'
    );
}

$GLOBALS["_db_table_exists_cache"] = [];
$GLOBALS["_db_column_exists_cache"] = [];

function db_quote_identifier($name) {
    return "`" . str_replace("`", "``", (string)$name) . "`";
}

function db_reset_schema_cache($table = null) {
    if ($table === null) {
        $GLOBALS["_db_table_exists_cache"] = [];
        $GLOBALS["_db_column_exists_cache"] = [];
        return;
    }

    unset($GLOBALS["_db_table_exists_cache"][$table]);
    foreach (array_keys($GLOBALS["_db_column_exists_cache"]) as $key) {
        if (strpos($key, $table . ".") === 0) {
            unset($GLOBALS["_db_column_exists_cache"][$key]);
        }
    }
}

function db_query_safely($conn, $sql) {
    try {
        return mysqli_query($conn, $sql);
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

function db_table_exists($conn, $table, $refresh = false) {
    if (
        !$refresh &&
        array_key_exists($table, $GLOBALS["_db_table_exists_cache"])
    ) {
        return $GLOBALS["_db_table_exists_cache"][$table];
    }

    $table_sql = db_quote_identifier($table);
    $res = db_query_safely($conn, "SELECT 1 FROM {$table_sql} LIMIT 0");
    if ($res instanceof mysqli_result) {
        mysqli_free_result($res);
    }

    $GLOBALS["_db_table_exists_cache"][$table] = ($res !== false);
    return $GLOBALS["_db_table_exists_cache"][$table];
}

function db_has_column($conn, $table, $column, $refresh = false) {
    $key = $table . "." . $column;
    if (
        !$refresh &&
        array_key_exists($key, $GLOBALS["_db_column_exists_cache"])
    ) {
        return $GLOBALS["_db_column_exists_cache"][$key];
    }

    if (!db_table_exists($conn, $table, $refresh)) {
        $GLOBALS["_db_column_exists_cache"][$key] = false;
        return false;
    }

    $table_sql = db_quote_identifier($table);
    $column_safe = mysqli_real_escape_string($conn, $column);
    $res = db_query_safely(
        $conn,
        "SHOW COLUMNS FROM {$table_sql} LIKE '{$column_safe}'"
    );
    if (!$res instanceof mysqli_result) {
        $GLOBALS["_db_column_exists_cache"][$key] = false;
        return false;
    }

    $GLOBALS["_db_column_exists_cache"][$key] = (mysqli_num_rows($res) > 0);
    mysqli_free_result($res);
    return $GLOBALS["_db_column_exists_cache"][$key];
}

function db_exec_safely($conn, $sql) {
    return db_query_safely($conn, $sql) !== false;
}

function db_add_column_if_missing($conn, $table, $column, $sql) {
    if (!db_table_exists($conn, $table, true)) {
        return false;
    }
    if (db_has_column($conn, $table, $column, true)) {
        return true;
    }

    $ok = db_exec_safely($conn, $sql);
    if ($ok) {
        db_reset_schema_cache($table);
    }
    return $ok;
}

function db_default_admin_hash() {
    return password_hash(DB_DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT);
}

function db_fetch_default_admin($conn) {
    if (!db_table_exists($conn, "admins", true)) {
        return null;
    }

    $sql = "SELECT id, username, name, email, password
            FROM admins
            WHERE email=? OR username=?
            ORDER BY id ASC
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }

    $email = DB_DEFAULT_ADMIN_EMAIL;
    $username = DB_DEFAULT_ADMIN_USERNAME;
    mysqli_stmt_bind_param($stmt, "ss", $email, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result ? mysqli_fetch_assoc($result) : null;
}

function db_update_admin_password($conn, $admin_id, $password_hash) {
    $stmt = mysqli_prepare($conn, "UPDATE admins SET password=? WHERE id=?");
    if (!$stmt) {
        return false;
    }

    $admin_id = (int)$admin_id;
    mysqli_stmt_bind_param($stmt, "si", $password_hash, $admin_id);
    return mysqli_stmt_execute($stmt);
}

function db_ensure_default_admin_account($conn) {
    if (!db_table_exists($conn, "admins", true)) {
        return false;
    }

    $admin = db_fetch_default_admin($conn);
    if (!$admin) {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO admins (username, name, email, password) VALUES (?, ?, ?, ?)"
        );
        if (!$stmt) {
            return false;
        }

        $username = DB_DEFAULT_ADMIN_USERNAME;
        $name = DB_DEFAULT_ADMIN_NAME;
        $email = DB_DEFAULT_ADMIN_EMAIL;
        $password_hash = db_default_admin_hash();
        mysqli_stmt_bind_param($stmt, "ssss", $username, $name, $email, $password_hash);
        return mysqli_stmt_execute($stmt);
    }

    $admin_id = (int)($admin["id"] ?? 0);
    if ($admin_id <= 0) {
        return false;
    }

    $current_username = trim((string)($admin["username"] ?? ""));
    $current_name = trim((string)($admin["name"] ?? ""));
    $current_email = trim((string)($admin["email"] ?? ""));
    $current_password = (string)($admin["password"] ?? "");

    $target_username = ($current_username !== "") ? $current_username : DB_DEFAULT_ADMIN_USERNAME;
    $target_name = ($current_name !== "") ? $current_name : DB_DEFAULT_ADMIN_NAME;
    $target_email = ($current_email !== "") ? $current_email : DB_DEFAULT_ADMIN_EMAIL;

    if (
        $target_username !== $current_username ||
        $target_name !== $current_name ||
        $target_email !== $current_email
    ) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE admins SET username=?, name=?, email=? WHERE id=?"
        );
        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "sssi",
                $target_username,
                $target_name,
                $target_email,
                $admin_id
            );
            mysqli_stmt_execute($stmt);
        }
    }

    if (
        $current_password === "" ||
        $current_password === DB_DEFAULT_ADMIN_PASSWORD ||
        $current_password === DB_DEFAULT_ADMIN_LEGACY_BAD_HASH
    ) {
        return db_update_admin_password($conn, $admin_id, db_default_admin_hash());
    }

    return true;
}

function db_create_core_tables($conn) {
    db_exec_safely(
        $conn,
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_users_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    db_reset_schema_cache("users");

    db_exec_safely(
        $conn,
        "CREATE TABLE IF NOT EXISTS complaints (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            full_name VARCHAR(120) NULL,
            age INT NULL,
            nationality VARCHAR(80) NULL,
            phone_number VARCHAR(20) NULL,
            reporter_address VARCHAR(255) NULL,
            crime_type VARCHAR(80) NOT NULL,
            description TEXT NOT NULL,
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            address VARCHAR(255) NULL,
            evidence VARCHAR(255) NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_crime_type (crime_type),
            INDEX idx_created_at (created_at),
            INDEX idx_lat_lng (latitude, longitude)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    db_reset_schema_cache("complaints");

    db_exec_safely(
        $conn,
        "CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(80) NOT NULL UNIQUE,
            name VARCHAR(120) NULL,
            email VARCHAR(190) NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    db_reset_schema_cache("admins");

    db_exec_safely(
        $conn,
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            complaint_id INT UNSIGNED NULL,
            message VARCHAR(255) NOT NULL,
            type VARCHAR(30) NOT NULL DEFAULT 'update',
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notifications_user (user_id),
            INDEX idx_notifications_read (is_read),
            INDEX idx_notifications_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    db_reset_schema_cache("notifications");

    db_exec_safely(
        $conn,
        "CREATE TABLE IF NOT EXISTS messages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            complaint_id INT UNSIGNED NOT NULL,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            message_text TEXT NOT NULL,
            attachment VARCHAR(255) NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_seen TINYINT(1) NOT NULL DEFAULT 0,
            INDEX idx_messages_complaint (complaint_id),
            INDEX idx_messages_seen (is_seen),
            INDEX idx_messages_sent (sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    db_reset_schema_cache("messages");

    db_exec_safely(
        $conn,
        "CREATE TABLE IF NOT EXISTS case_updates (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            complaint_id INT UNSIGNED NOT NULL,
            status VARCHAR(40) NOT NULL,
            note VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_case_updates_complaint (complaint_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    db_reset_schema_cache("case_updates");
}

function db_ensure_schema($conn) {
    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }
    $bootstrapped = true;

    db_create_core_tables($conn);

    db_add_column_if_missing(
        $conn,
        "complaints",
        "address",
        "ALTER TABLE complaints ADD COLUMN address VARCHAR(255) NULL AFTER longitude"
    );
    db_add_column_if_missing(
        $conn,
        "complaints",
        "full_name",
        "ALTER TABLE complaints ADD COLUMN full_name VARCHAR(120) NULL AFTER user_id"
    );
    db_add_column_if_missing(
        $conn,
        "complaints",
        "age",
        "ALTER TABLE complaints ADD COLUMN age INT NULL AFTER full_name"
    );
    db_add_column_if_missing(
        $conn,
        "complaints",
        "nationality",
        "ALTER TABLE complaints ADD COLUMN nationality VARCHAR(80) NULL AFTER age"
    );
    db_add_column_if_missing(
        $conn,
        "complaints",
        "phone_number",
        "ALTER TABLE complaints ADD COLUMN phone_number VARCHAR(20) NULL AFTER nationality"
    );
    db_add_column_if_missing(
        $conn,
        "complaints",
        "reporter_address",
        "ALTER TABLE complaints ADD COLUMN reporter_address VARCHAR(255) NULL AFTER phone_number"
    );
    db_add_column_if_missing(
        $conn,
        "complaints",
        "created_at",
        "ALTER TABLE complaints ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    );

    db_add_column_if_missing(
        $conn,
        "notifications",
        "complaint_id",
        "ALTER TABLE notifications ADD COLUMN complaint_id INT UNSIGNED NULL AFTER user_id"
    );
    db_add_column_if_missing(
        $conn,
        "notifications",
        "type",
        "ALTER TABLE notifications ADD COLUMN type VARCHAR(30) NOT NULL DEFAULT 'update' AFTER message"
    );

    db_add_column_if_missing(
        $conn,
        "admins",
        "email",
        "ALTER TABLE admins ADD COLUMN email VARCHAR(190) NULL AFTER name"
    );
    db_add_column_if_missing(
        $conn,
        "admins",
        "name",
        "ALTER TABLE admins ADD COLUMN name VARCHAR(120) NULL AFTER username"
    );

    db_ensure_default_admin_account($conn);
}

db_ensure_schema($conn);
?>
