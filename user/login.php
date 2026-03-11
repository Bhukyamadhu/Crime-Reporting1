<?php
include("../config/security.php");
include("../config/db.php");
security_start_session();
security_enforce_timeout();

$message = "";
$flash = get_flash_message();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_verify_or_die();

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $message = "All fields are required!";
    } else {

        $stmt = mysqli_prepare($conn, "SELECT id, name, password FROM users WHERE email=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            if (!password_verify($password, $user["password"])) {
                $message = "Invalid password!";
            } else {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                session_regenerate_once();
                security_touch_session();

                header("Location: dashboard.php");
                exit();
            }
        } else {
            $message = "Email not found!";
        }
    }
   
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow p-4">
                <h3 class="text-center mb-4">User Login</h3>

                <?php if($message != "") { ?>
                    <div class="alert alert-danger"><?php echo h($message); ?></div>
                <?php } ?>
                <?php if($flash) { ?>
                    <div class="alert alert-<?php echo h($flash["type"]); ?>"><?php echo h($flash["text"]); ?></div>
                <?php } ?>

                <form method="POST">
                    <?php echo csrf_input(); ?>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Login
                    </button>

                    <p class="mt-3 text-center">
                        Don't have an account?
                        <a href="register.php">Register</a>
                    </p>
                </form>

            </div>

        </div>
    </div>
</div>

</body>
</html>
