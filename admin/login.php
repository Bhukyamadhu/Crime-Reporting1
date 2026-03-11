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
            $has_email = db_has_column($conn, "admins", "email");
            $has_name = db_has_column($conn, "admins", "name");
            $has_username = db_has_column($conn, "admins", "username");

            $display_col = $has_name ? "name" : ($has_username ? "username" : "id");
            $identity_col = $has_email ? "email" : ($has_username ? "username" : "id");

            $sql = "SELECT id, {$display_col} AS display_name, password FROM admins WHERE {$identity_col}=? LIMIT 1";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $identity);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) === 1) {
                $admin = mysqli_fetch_assoc($result);
            }
        }

        // First-time fallback
        if (!$admin && $identity === "admin@crime.local" && $password === "Admin@123") {
            $_SESSION["admin_id"] = 0;
            $_SESSION["admin_name"] = "System Admin";
            session_regenerate_once();
            security_touch_session();
            header("Location: dashboard.php");
            exit();
        }

        if ($admin && password_verify($password, $admin["password"])) {
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
