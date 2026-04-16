<?php
include("../config/security.php");
include("../config/db.php");
security_start_session();
security_enforce_timeout();

if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit();
}

$message = "";
$flash = get_flash_message();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify_or_die();
    $identity = trim($_POST["identity"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($identity === "" || $password === "") {
        $message = "Please enter username/email and password.";
    } else {
        $admin = null;
        if (db_table_exists($conn, "admins")) {
            db_ensure_default_admin_account($conn);

            $sql = "SELECT id, username, name, email, password,
                           COALESCE(NULLIF(name, ''), NULLIF(username, ''), NULLIF(email, ''), 'Admin') AS display_name
                    FROM admins
                    WHERE email=? OR username=?
                    LIMIT 1";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ss", $identity, $identity);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($result && mysqli_num_rows($result) === 1) {
                    $admin = mysqli_fetch_assoc($result);
                }
            }
        }

        $password_ok = false;
        if ($admin) {
            $stored_password = (string)($admin["password"] ?? "");
            if ($stored_password !== "" && password_verify($password, $stored_password)) {
                $password_ok = true;
                if (password_needs_rehash($stored_password, PASSWORD_DEFAULT)) {
                    db_update_admin_password($conn, (int)$admin["id"], password_hash($password, PASSWORD_DEFAULT));
                }
            } elseif ($stored_password !== "" && hash_equals($stored_password, $password)) {
                $password_ok = true;
                db_update_admin_password($conn, (int)$admin["id"], password_hash($password, PASSWORD_DEFAULT));
            }
        }

        if ($admin && $password_ok) {
            $_SESSION["admin_id"] = (int)$admin["id"];
            $_SESSION["admin_name"] = (string)$admin["display_name"];
            session_regenerate_once();
            security_touch_session();
            header("Location: dashboard.php");
            exit();
        }

        if ($message === "") {
            $message = "Invalid admin credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="mb-4 text-center"><i class="bi bi-shield-lock me-2"></i>Admin Login</h3>

                    <?php if ($message !== "") { ?>
                        <div class="alert alert-danger"><?php echo h($message); ?></div>
                    <?php } ?>
                    <?php if ($flash) { ?>
                        <div class="alert alert-<?php echo h($flash["type"]); ?>"><?php echo h($flash["text"]); ?></div>
                    <?php } ?>

                    <form method="POST">
                        <?php echo csrf_input(); ?>
                        <div class="mb-3">
                            <label class="form-label">Username / Email</label>
                            <input type="text" name="identity" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Login</button>
                    </form>
                    <p class="small text-muted mt-3 mb-0">
                        Default fallback: <code>admin@crime.local</code> / <code>Admin@123</code>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
