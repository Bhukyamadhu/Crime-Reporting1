<?php
include("../config/security.php");
include("../config/db.php");
security_start_session();

$message = "";
$message_type = "danger";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify_or_die();
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if ($name === "" || $email === "" || $password === "" || $phone === "") {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? LIMIT 1");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $message = "Email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($insert_stmt, "ssss", $name, $email, $hashed_password, $phone);

            if (mysqli_stmt_execute($insert_stmt)) {
                $message = "Registration successful. Please login.";
                $message_type = "success";
            } else {
                $message = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <h3 class="text-center mb-4">User Registration</h3>

                <?php if ($message !== "") { ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo h($message); ?>
                    </div>
                <?php } ?>

                <form method="POST" novalidate>
                    <?php echo csrf_input(); ?>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Register</button>

                    <p class="mt-3 text-center mb-0">
                        Already have an account?
                        <a href="login.php">Login</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
