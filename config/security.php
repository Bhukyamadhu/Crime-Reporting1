<?php
// Shared security helpers used across user and admin modules.

function security_start_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "domain" => "",
        "secure" => $secure,
        "httponly" => true,
        "samesite" => "Lax"
    ]);
    session_start();
}

function security_touch_session($key = "last_activity") {
    $_SESSION[$key] = time();
}

function security_enforce_timeout($timeout_seconds = 1800, $key = "last_activity") {
    if (!empty($_SESSION[$key]) && (time() - (int)$_SESSION[$key]) > $timeout_seconds) {
        session_unset();
        session_destroy();
        security_start_session();
        $_SESSION["flash_message"] = [
            "type" => "warning",
            "text" => "Your session expired due to inactivity. Please log in again."
        ];
        return false;
    }

    security_touch_session($key);
    return true;
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function set_flash_message($type, $text) {
    $_SESSION["flash_message"] = [
        "type" => (string)$type,
        "text" => (string)$text
    ];
}

function get_flash_message() {
    if (empty($_SESSION["flash_message"]) || !is_array($_SESSION["flash_message"])) {
        return null;
    }

    $flash = $_SESSION["flash_message"];
    unset($_SESSION["flash_message"]);
    return $flash;
}

function csrf_token() {
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_verify_or_die() {
    $token = $_POST["csrf_token"] ?? "";
    if (!is_string($token) || empty($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Invalid CSRF token.");
    }
}

function session_regenerate_once() {
    if (empty($_SESSION["sid_regenerated"])) {
        session_regenerate_id(true);
        $_SESSION["sid_regenerated"] = 1;
    }
}

function security_require_user($redirect = "login.php") {
    security_start_session();
    if (!security_enforce_timeout()) {
        header("Location: " . $redirect);
        exit();
    }

    if (empty($_SESSION["user_id"])) {
        set_flash_message("warning", "Please log in to continue.");
        header("Location: " . $redirect);
        exit();
    }
}

function security_require_admin($redirect = "login.php") {
    security_start_session();
    if (!security_enforce_timeout()) {
        header("Location: " . $redirect);
        exit();
    }

    if (!isset($_SESSION["admin_id"])) {
        set_flash_message("warning", "Please log in as admin to continue.");
        header("Location: " . $redirect);
        exit();
    }
}

include_once(__DIR__ . "/translation.php");
?>
