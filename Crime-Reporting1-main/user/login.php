<?php
include("../config/security.php");
include("../config/db.php");
security_start_session();
security_enforce_timeout();

$message = "";
$flash = get_flash_message();
$translate_js_version = (string)filemtime(__DIR__ . "/../assets/js/google-translate-switcher.js");

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
<div class="top-alert-strip">
    <strong>Emergency?</strong> Call 100 or 112 immediately. This portal is for non-emergency reporting only.
</div>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="surface-card p-4 p-lg-5">
                <p class="text-uppercase small fw-semibold text-primary mb-2 text-center">Citizen Access</p>
                <h1 class="section-title text-center mb-3">Login to your account</h1>
                <p class="section-copy text-center mx-auto mb-4">Access your dashboard to submit complaints, track progress, and review updates.</p>

                <?php if($message != "") { ?>
                    <div class="alert alert-danger"><?php echo h($message); ?></div>
                <?php } ?>
                <?php if($flash) { ?>
                    <div class="alert alert-<?php echo h($flash["type"]); ?>"><?php echo h($flash["text"]); ?></div>
                <?php } ?>

                <form method="POST">
                    <?php echo csrf_input(); ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Login
                    </button>

                    <p class="mt-3 text-center mb-0">
                        Don't have an account?
                        <a href="register.php">Register</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</main>
<?php
$footer_base_path = "..";
$footer_variant = "public";
include("../includes/footer.php");
?>
<div id="google_translate_element" class="visually-hidden" aria-hidden="true"></div>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/google-translate-switcher.js?v=<?php echo h($translate_js_version); ?>"></script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>
