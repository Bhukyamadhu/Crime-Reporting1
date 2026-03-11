<?php
include("../config/security.php");
include("../config/db.php");
security_require_user("login.php");

$user_id = (int)$_SESSION["user_id"];
$flash = get_flash_message();

$user_stmt = mysqli_prepare($conn, "SELECT id, name, email, phone FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = $user_result ? mysqli_fetch_assoc($user_result) : null;

if (!$user) {
    session_unset();
    session_destroy();
    security_start_session();
    set_flash_message("warning", "Your account session could not be validated. Please log in again.");
    header("Location: login.php");
    exit();
}

$has_address = db_has_column($conn, "complaints", "address");
$address_select = $has_address ? "address" : "NULL AS address";
$complaints_stmt = mysqli_prepare(
    $conn,
    "SELECT id, crime_type, description, {$address_select}, evidence, status, created_at
     FROM complaints
     WHERE user_id=?
     ORDER BY created_at DESC"
);
mysqli_stmt_bind_param($complaints_stmt, "i", $user_id);
mysqli_stmt_execute($complaints_stmt);
$complaints_result = mysqli_stmt_get_result($complaints_stmt);

$complaints = [];
$stats = ["total" => 0, "pending" => 0, "investigating" => 0, "resolved" => 0];
while ($complaints_result && $row = mysqli_fetch_assoc($complaints_result)) {
    $complaints[] = $row;
    $stats["total"]++;
    if ($row["status"] === "Pending") {
        $stats["pending"]++;
    } elseif ($row["status"] === "Under Investigation" || $row["status"] === "Investigating") {
        $stats["investigating"]++;
    } elseif ($row["status"] === "Resolved") {
        $stats["resolved"]++;
    }
}

$notifications = [];
if (db_table_exists($conn, "notifications")) {
    $notif_stmt = mysqli_prepare(
        $conn,
        "SELECT message, created_at
         FROM notifications
         WHERE user_id=?
         ORDER BY created_at DESC
         LIMIT 8"
    );
    mysqli_stmt_bind_param($notif_stmt, "i", $user_id);
    mysqli_stmt_execute($notif_stmt);
    $notif_result = mysqli_stmt_get_result($notif_stmt);
    while ($notif_result && $n = mysqli_fetch_assoc($notif_result)) {
        $notifications[] = $n;
    }
}

function complaint_status_class($status) {
    if ($status === "Pending") return "status-pending";
    if ($status === "Under Investigation" || $status === "Investigating") return "status-investigating";
    if ($status === "Resolved") return "status-resolved";
    return "status-secondary";
}

function crime_icon($type) {
    $map = [
        "Theft" => "fa-solid fa-mask-face",
        "Assault" => "fa-solid fa-hand-fist",
        "Accident" => "fa-solid fa-car-burst",
        "Cyber Crime" => "fa-solid fa-laptop-code",
        "Vandalism" => "fa-solid fa-hammer",
        "Other" => "fa-solid fa-shield-halved"
    ];
    return $map[$type] ?? "fa-solid fa-shield-halved";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../chatbot/chatbot.css">
</head>
<body>
<nav class="navbar navbar-expand-lg glass-nav">
    <div class="container py-2">
        <a class="navbar-brand d-flex align-items-center gap-3 fw-semibold" href="../index.php">
            <span class="navbar-brand-mark"><i class="fa-solid fa-shield-halved"></i></span>
            <span class="navbar-brand-text">
                Crime Reporting System
                <small>Citizen service dashboard</small>
            </span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#userNav" aria-controls="userNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="userNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 me-lg-3">
                <li class="nav-item"><a class="nav-link app-nav-link" href="../index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="report.php">Report Crime</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link active" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="#complaint-history">Track Complaint</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="../public_stats.php">Statistics</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="logout.php">Logout</a></li>
            </ul>
            <div class="d-grid d-lg-flex gap-2">
                <a href="report.php" class="btn btn-primary">New Report</a>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4 py-lg-5">
    <div class="page-topbar">
        <div class="page-breadcrumb">
            <a href="../index.php">Home</a>
            <i class="fa-solid fa-chevron-right small"></i>
            <span class="current">Dashboard</span>
        </div>
        <div class="page-toolbar">
            <div class="nav-shortcuts">
                <a class="shortcut-pill" href="report.php"><i class="fa-solid fa-file-circle-plus"></i>Report Crime</a>
                <a class="shortcut-pill" href="#complaint-history"><i class="fa-solid fa-list-check"></i>Track Complaint</a>
                <a class="shortcut-pill" href="../public_stats.php"><i class="fa-solid fa-chart-column"></i>Statistics</a>
            </div>
        </div>
    </div>

    <?php if ($flash) { ?>
        <div class="alert alert-<?php echo h($flash["type"]); ?> mb-4"><?php echo h($flash["text"]); ?></div>
    <?php } ?>

    <section class="surface-card p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <p class="text-uppercase small fw-semibold text-primary mb-2">Citizen Dashboard</p>
                <h1 class="section-title mb-2">Welcome, <?php echo h($user["name"]); ?></h1>
                <p class="section-copy mb-0">Monitor your complaints, review evidence submissions, and use the shortcuts below to move quickly between reporting, tracking, and public statistics.</p>
            </div>
            <div class="col-lg-4">
                <div class="surface-card p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="feature-card-icon"><i class="fa-solid fa-user-shield"></i></span>
                        <div>
                            <h5 class="mb-0"><?php echo h($user["name"]); ?></h5>
                            <p class="muted mb-0">Verified account holder</p>
                        </div>
                    </div>
                    <div class="small">
                        <div class="mb-2"><strong>Email:</strong> <?php echo h($user["email"]); ?></div>
                        <div><strong>Phone:</strong> <?php echo h($user["phone"]); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-grid mb-4">
        <div class="metric-card card-hover">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label">Total Complaints</div>
                    <div class="metric-value"><?php echo (int)$stats["total"]; ?></div>
                </div>
                <span class="dashboard-icon feature-card-icon"><i class="fa-solid fa-folder-open"></i></span>
            </div>
            <div class="metric-trend">All submissions linked to your account</div>
        </div>
        <div class="metric-card card-hover">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label">Pending Complaints</div>
                    <div class="metric-value"><?php echo (int)$stats["pending"]; ?></div>
                </div>
                <span class="dashboard-icon" style="background:rgba(245,158,11,.14);color:#9a6700;"><i class="fa-solid fa-hourglass-half"></i></span>
            </div>
            <div class="metric-trend">Awaiting review by the response team</div>
        </div>
        <div class="metric-card card-hover">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label">Under Investigation</div>
                    <div class="metric-value"><?php echo (int)$stats["investigating"]; ?></div>
                </div>
                <span class="dashboard-icon" style="background:rgba(26,115,232,.14);color:#0f5fcc;"><i class="fa-solid fa-magnifying-glass"></i></span>
            </div>
            <div class="metric-trend">Active cases being processed</div>
        </div>
        <div class="metric-card card-hover">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label">Resolved Cases</div>
                    <div class="metric-value"><?php echo (int)$stats["resolved"]; ?></div>
                </div>
                <span class="dashboard-icon" style="background:rgba(22,163,74,.14);color:#15703a;"><i class="fa-solid fa-circle-check"></i></span>
            </div>
            <div class="metric-trend">Cases completed or closed successfully</div>
        </div>
    </section>

    <section class="row g-4 mb-4" id="complaint-history">
        <div class="col-lg-8">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <p class="text-uppercase small fw-semibold text-primary mb-1">Complaint History</p>
                        <h4 class="mb-0">Track every complaint in one table</h4>
                    </div>
                    <a href="report.php" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Report Crime</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>Complaint</th>
                                <th>Status</th>
                                <?php if ($has_address) { ?><th>Location</th><?php } ?>
                                <th>Submitted</th>
                                <th>Evidence</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($complaints) > 0) { ?>
                            <?php foreach ($complaints as $row) { ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-start gap-3">
                                            <span class="feature-card-icon flex-shrink-0" style="width:48px;height:48px;"><i class="<?php echo h(crime_icon($row["crime_type"])); ?>"></i></span>
                                            <div>
                                                <div class="fw-semibold"><?php echo h($row["crime_type"]); ?></div>
                                                <div class="muted small">#<?php echo (int)$row["id"]; ?> &middot; <?php echo h($row["description"]); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="status-pill <?php echo complaint_status_class($row["status"]); ?>"><?php echo h($row["status"]); ?></span></td>
                                    <?php if ($has_address) { ?><td class="small muted"><?php echo h($row["address"]); ?></td><?php } ?>
                                    <td class="small muted"><?php echo h($row["created_at"]); ?></td>
                                    <td>
                                        <?php if (!empty($row["evidence"])) { ?>
                                            <a href="../uploads/<?php echo urlencode($row["evidence"]); ?>" target="_blank" rel="noopener">
                                                <img src="../uploads/<?php echo urlencode($row["evidence"]); ?>" class="thumbnail-preview" alt="Evidence preview">
                                            </a>
                                        <?php } else { ?>
                                            <span class="muted small">No file</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="<?php echo $has_address ? 5 : 4; ?>" class="empty-state">
                                    <div class="mb-2"><i class="fa-regular fa-folder-open fa-2x"></i></div>
                                    <div>No complaints submitted yet.</div>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-4 h-100">
                <p class="text-uppercase small fw-semibold text-primary mb-1">Latest Updates</p>
                <h4 class="mb-3">Notifications</h4>
                <?php if (count($notifications) > 0) { ?>
                    <div class="vstack gap-3">
                        <?php foreach ($notifications as $n) { ?>
                            <div class="surface-card p-3">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="feature-card-icon flex-shrink-0" style="width:44px;height:44px;"><i class="fa-solid fa-bell"></i></span>
                                    <div>
                                        <div class="fw-semibold mb-1"><?php echo h($n["message"]); ?></div>
                                        <div class="small muted"><?php echo h($n["created_at"]); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="empty-state">
                        <div class="mb-2"><i class="fa-regular fa-bell-slash fa-2x"></i></div>
                        <div>No notifications yet.</div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>
</main>

<div id="crimeChatbot" class="chatbot-widget" data-api-url="../chatbot/chatbot_api.php">
    <div class="chatbot-window">
        <div class="chatbot-header">
            <span class="chatbot-title">AI Safety Assistant</span>
            <button type="button" class="chatbot-close" aria-label="Close chatbot">&times;</button>
        </div>
        <div class="chatbot-messages"></div>
        <div class="chatbot-input-wrap">
            <input type="text" class="chatbot-input" placeholder="Ask about your complaint or reporting" aria-label="Chat input">
            <button type="button" class="chatbot-send">Send</button>
        </div>
    </div>
    <button type="button" class="chatbot-toggle" aria-label="Open chatbot"><i class="fa-solid fa-comments"></i></button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../chatbot/chatbot.js"></script>
</body>
</html>
