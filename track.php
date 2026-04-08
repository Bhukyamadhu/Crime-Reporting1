<?php
include("config/security.php");
include("config/db.php");
security_start_session();
security_enforce_timeout();

$current_user_id = (int)($_SESSION["user_id"] ?? 0);
$complaint_id = trim($_GET["complaint_id"] ?? $_POST["complaint_id"] ?? "");
$complaint = null;
$searched = false;
$has_address = db_has_column($conn, "complaints", "address");
$address_select = $has_address ? "address" : "NULL AS address";

if ($complaint_id !== "") {
    $searched = true;
    if (ctype_digit($complaint_id)) {
        $stmt = mysqli_prepare($conn, "SELECT id, user_id, status, description, {$address_select}, created_at FROM complaints WHERE id=? LIMIT 1");
        $cid = (int)$complaint_id;
        mysqli_stmt_bind_param($stmt, "i", $cid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $complaint = $result ? mysqli_fetch_assoc($result) : null;
    }
}

function track_status_class($status) {
    if ($status === "Pending") return "status-pending";
    if ($status === "Under Investigation" || $status === "Investigating") return "status-investigating";
    if ($status === "Resolved") return "status-resolved";
    return "status-secondary";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track Complaint</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="chatbot/chatbot.css">
</head>
<body>
<nav class="navbar navbar-expand-lg glass-nav"><div class="container py-2"><a class="navbar-brand d-flex align-items-center gap-3 fw-semibold" href="index.php"><span class="navbar-brand-mark"><i class="fa-solid fa-shield-halved"></i></span><span class="navbar-brand-text">Crime Reporting System<small>Complaint tracking portal</small></span></a><button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#trackNav"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="trackNav"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 me-lg-3"><li class="nav-item"><a class="nav-link app-nav-link" href="index.php">Home</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="user/report.php">Report Crime</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="user/dashboard.php">Dashboard</a></li><li class="nav-item"><a class="nav-link app-nav-link active" href="track.php">Track Complaint</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="public_stats.php">Statistics</a></li></ul></div></div></nav>
<main class="container py-4 py-lg-5">
    <div class="page-breadcrumb mb-3"><a href="index.php">Home</a><i class="fa-solid fa-chevron-right small"></i><span class="current">Track Complaint</span></div>
    <section class="surface-card p-4 p-lg-5 mb-4"><p class="text-uppercase small fw-semibold text-primary mb-2">Complaint Tracking</p><h1 class="section-title mb-2">Track your complaint by ID</h1><p class="section-copy mb-0">Enter the complaint ID to check its status, description, submitted date, and location details.</p></section>
    <section class="card p-4 mb-4"><form method="GET" class="row g-3 align-items-end"><div class="col-md-9"><label class="form-label">Enter Complaint ID</label><input type="text" name="complaint_id" class="form-control" value="<?php echo h($complaint_id); ?>" placeholder="Enter Complaint ID"></div><div class="col-md-3 d-grid"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass me-2"></i>Search</button></div></form></section>
    <section class="card p-4"><?php if (!$searched) { ?><div class="empty-state">Enter a complaint ID to begin tracking.</div><?php } elseif (!$complaint) { ?><div class="alert alert-warning mb-0">No complaint was found for the entered ID.</div><?php } else { ?><div class="row g-4"><div class="col-md-6"><div class="surface-card p-3 h-100"><div class="small text-uppercase fw-semibold text-primary mb-2">Complaint ID</div><div class="fw-semibold">#<?php echo (int)$complaint["id"]; ?></div></div></div><div class="col-md-6"><div class="surface-card p-3 h-100"><div class="small text-uppercase fw-semibold text-primary mb-2">Status</div><div><span class="status-pill <?php echo track_status_class($complaint["status"]); ?>"><?php echo h($complaint["status"]); ?></span></div></div></div><div class="col-md-6"><div class="surface-card p-3 h-100"><div class="small text-uppercase fw-semibold text-primary mb-2">Date Submitted</div><div class="fw-semibold"><?php echo h($complaint["created_at"]); ?></div></div></div><div class="col-md-6"><div class="surface-card p-3 h-100"><div class="small text-uppercase fw-semibold text-primary mb-2">Location</div><div class="fw-semibold"><?php echo h($complaint["address"] ?: "Not available"); ?></div></div></div><div class="col-12"><div class="surface-card p-3"><div class="small text-uppercase fw-semibold text-primary mb-2">Description</div><div><?php echo h($complaint["description"]); ?></div></div></div><div class="col-12"><div class="d-flex gap-3 flex-wrap"><?php if ($current_user_id > 0 && $current_user_id === (int)$complaint["user_id"]) { ?><a class="btn btn-primary" href="user/chat.php?complaint_id=<?php echo (int)$complaint["id"]; ?>">Open Chat</a><a class="btn btn-outline-primary" href="user/report.php?complaint_id=<?php echo (int)$complaint["id"]; ?>">Update Details</a><?php } ?></div></div></div><?php } ?></section>
</main>
<div id="crimeChatbot" class="chatbot-widget" data-api-url="chatbot/chatbot_api.php"><div class="chatbot-window"><div class="chatbot-header"><span class="chatbot-title">AI Safety Assistant</span><button type="button" class="chatbot-close" aria-label="Close chatbot">&times;</button></div><div class="chatbot-messages"></div><div class="chatbot-input-wrap"><input type="text" class="chatbot-input" placeholder="Ask about complaint tracking" aria-label="Chat input"><button type="button" class="chatbot-send">Send</button></div></div><button type="button" class="chatbot-toggle" aria-label="Open chatbot"><i class="fa-solid fa-comments"></i></button></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script><script src="assets/js/app.js"></script><script src="chatbot/chatbot.js"></script>
</body>
</html>

