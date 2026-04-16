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

$chatbot_css_version = (string)filemtime(__DIR__ . "/../chatbot/chatbot.css");
$chatbot_js_version = (string)filemtime(__DIR__ . "/../chatbot/chatbot.js");
$translate_js_version = (string)filemtime(__DIR__ . "/../assets/js/google-translate-switcher.js");
$tour_js_version = (string)filemtime(__DIR__ . "/../assets/js/site-tour.js");

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
    <link rel="stylesheet" href="../chatbot/chatbot.css?v=<?php echo h($chatbot_css_version); ?>">
</head>
<body data-tour-page="dashboard">
<div class="top-alert-strip">
    <strong data-i18n="dashboard.alert_title">Emergency?</strong> <span data-i18n="dashboard.alert_text">Call 100 or 112 immediately. This portal is for non-emergency reporting only.</span>
</div>
<nav class="navbar navbar-expand-lg glass-nav" data-tour-step="1" data-tour-title="Dashboard Navigation" data-tour-text="This top bar lets you move between home, report submission, complaint tracking, public statistics, logout, and language switching.">
    <div class="container py-2">
        <a class="navbar-brand d-flex align-items-center gap-3 fw-semibold" href="../index.php">
            <span class="navbar-brand-mark brand-logo-shell">
                <img class="brand-logo-img-contain" src="../assets/img/logo-new.png" alt="Crime Reporting System logo">
            </span>
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
                <li class="nav-item"><a class="nav-link app-nav-link" href="../index.php"><span data-i18n="dashboard.nav_home">Home</span></a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="report.php"><span data-i18n="dashboard.nav_report">Report Crime</span></a></li>
                <li class="nav-item"><a class="nav-link app-nav-link active" href="dashboard.php"><span data-i18n="dashboard.nav_dashboard">Dashboard</span></a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="#complaint-history"><span data-i18n="dashboard.nav_track">Track Complaint</span></a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="../public_stats.php"><span data-i18n="dashboard.nav_statistics">Statistics</span></a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="logout.php"><span data-i18n="dashboard.nav_logout">Logout</span></a></li>
            </ul>
            <div class="d-grid d-lg-flex gap-2">
                <div class="language-switcher-wrap">
                    <label class="visually-hidden" for="dashboardLanguageSwitcher">Language</label>
                    <div class="language-switcher-shell">
                        <i class="fa-solid fa-language" aria-hidden="true"></i>
                        <select id="dashboardLanguageSwitcher" class="language-switcher-select" aria-label="Language switcher" data-google-translate-switcher>
                            <option value="en">English</option>
                            <option value="hi">हिंदी</option>
                        </select>
                    </div>
                </div>
                <a href="report.php" class="btn btn-primary"><span data-i18n="dashboard.new_report">New Report</span></a>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4 py-lg-5">
    <div class="page-topbar">
        <div class="page-breadcrumb">
            <a href="../index.php" data-i18n="dashboard.breadcrumb_home">Home</a>
            <i class="fa-solid fa-chevron-right small"></i>
            <span class="current" data-i18n="dashboard.breadcrumb_current">Dashboard</span>
        </div>
        <div class="page-toolbar">
            <div class="nav-shortcuts">
                <a class="shortcut-pill" href="report.php"><i class="fa-solid fa-file-circle-plus"></i><span data-i18n="dashboard.shortcut_report">Report Crime</span></a>
                <a class="shortcut-pill" href="#complaint-history"><i class="fa-solid fa-list-check"></i><span data-i18n="dashboard.shortcut_track">Track Complaint</span></a>
                <a class="shortcut-pill" href="../public_stats.php"><i class="fa-solid fa-chart-column"></i><span data-i18n="dashboard.shortcut_statistics">Statistics</span></a>
            </div>
        </div>
    </div>

    <?php if ($flash) { ?>
        <div class="alert alert-<?php echo h($flash["type"]); ?> mb-4"><?php echo h($flash["text"]); ?></div>
    <?php } ?>

    <section class="surface-card p-4 p-lg-5 mb-4" data-tour-step="2" data-tour-title="Account Overview" data-tour-text="This section shows the logged-in citizen account, profile details, and the main summary of what this dashboard is used for.">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <p class="text-uppercase small fw-semibold text-primary mb-2" data-i18n="dashboard.hero_label">Citizen Dashboard</p>
                <h1 class="section-title mb-2" data-i18n="dashboard.hero_title" data-i18n-name="<?php echo h($user["name"]); ?>">Welcome, <?php echo h($user["name"]); ?></h1>
                <p class="section-copy mb-0" data-i18n="dashboard.hero_copy">Monitor your complaints, review evidence submissions, and use the shortcuts below to move quickly between reporting, tracking, and public statistics.</p>
            </div>
            <div class="col-lg-4">
                <div class="surface-card p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="feature-card-icon"><i class="fa-solid fa-user-shield"></i></span>
                        <div>
                            <h5 class="mb-0"><?php echo h($user["name"]); ?></h5>
                            <p class="muted mb-0" data-i18n="dashboard.account_verified">Verified account holder</p>
                        </div>
                    </div>
                    <div class="small">
                        <div class="mb-2"><strong data-i18n="dashboard.account_email">Email:</strong> <?php echo h($user["email"]); ?></div>
                        <div><strong data-i18n="dashboard.account_phone">Phone:</strong> <?php echo h($user["phone"]); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-grid mb-4" data-tour-step="3" data-tour-title="Complaint Summary Cards" data-tour-text="These cards give a quick count of total, pending, active, and resolved complaints so you can understand your case activity at a glance.">
        <div class="metric-card card-hover">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label" data-i18n="dashboard.metric_total_label">Total Complaints</div>
                    <div class="metric-value"><?php echo (int)$stats["total"]; ?></div>
                </div>
                <span class="dashboard-icon feature-card-icon"><i class="fa-solid fa-folder-open"></i></span>
            </div>
            <div class="metric-trend" data-i18n="dashboard.metric_total_trend">All submissions linked to your account</div>
        </div>
        <div class="metric-card card-hover">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label" data-i18n="dashboard.metric_pending_label">Pending Complaints</div>
                    <div class="metric-value"><?php echo (int)$stats["pending"]; ?></div>
                </div>
                <span class="dashboard-icon" style="background:rgba(245,158,11,.14);color:#9a6700;"><i class="fa-solid fa-hourglass-half"></i></span>
            </div>
            <div class="metric-trend" data-i18n="dashboard.metric_pending_trend">Awaiting review by the response team</div>
        </div>
        <div class="metric-card card-hover">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label" data-i18n="dashboard.metric_investigating_label">Under Investigation</div>
                    <div class="metric-value"><?php echo (int)$stats["investigating"]; ?></div>
                </div>
                <span class="dashboard-icon" style="background:rgba(26,115,232,.14);color:#0f5fcc;"><i class="fa-solid fa-magnifying-glass"></i></span>
            </div>
            <div class="metric-trend" data-i18n="dashboard.metric_investigating_trend">Active cases being processed</div>
        </div>
        <div class="metric-card card-hover">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label" data-i18n="dashboard.metric_resolved_label">Resolved Cases</div>
                    <div class="metric-value"><?php echo (int)$stats["resolved"]; ?></div>
                </div>
                <span class="dashboard-icon" style="background:rgba(22,163,74,.14);color:#15703a;"><i class="fa-solid fa-circle-check"></i></span>
            </div>
            <div class="metric-trend" data-i18n="dashboard.metric_resolved_trend">Cases completed or closed successfully</div>
        </div>
    </section>

    <section class="row g-4 mb-4" id="complaint-history" data-tour-step="4" data-tour-title="Complaint History" data-tour-text="This table is where you review every submitted complaint, its status, location, submit time, and any uploaded evidence.">
        <div class="col-lg-8">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <p class="text-uppercase small fw-semibold text-primary mb-1" data-i18n="dashboard.history_label">Complaint History</p>
                        <h4 class="mb-0" data-i18n="dashboard.history_title">Track every complaint in one table</h4>
                    </div>
                    <a href="report.php" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i><span data-i18n="dashboard.history_action">Report Crime</span></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th data-i18n="dashboard.table_complaint">Complaint</th>
                                <th data-i18n="dashboard.table_status">Status</th>
                                <?php if ($has_address) { ?><th data-i18n="dashboard.table_location">Location</th><?php } ?>
                                <th data-i18n="dashboard.table_submitted">Submitted</th>
                                <th data-i18n="dashboard.table_evidence">Evidence</th>
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
                                            <span class="muted small" data-i18n="dashboard.no_file">No file</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="<?php echo $has_address ? 5 : 4; ?>" class="empty-state">
                                    <div class="mb-2"><i class="fa-regular fa-folder-open fa-2x"></i></div>
                                    <div data-i18n="dashboard.empty_complaints">No complaints submitted yet.</div>
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
                <p class="text-uppercase small fw-semibold text-primary mb-1" data-i18n="dashboard.updates_label">Latest Updates</p>
                <h4 class="mb-3" data-i18n="dashboard.updates_title">Notifications</h4>
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
                        <div data-i18n="dashboard.empty_notifications">No notifications yet.</div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>
</main>
<?php
$footer_base_path = "..";
$footer_variant = "public";
include("../includes/footer.php");
?>
<div id="google_translate_element" class="visually-hidden" aria-hidden="true"></div>

<div id="crimeChatbot" class="chatbot-widget" data-api-url="../chatbot/chatbot_api.php" data-logo-src="../assets/img/bot_logo.png" data-tour-step="5" data-tour-title="Assistant Support" data-tour-text="If you need help understanding statuses, reporting steps, or evidence rules, use the chatbot from inside the dashboard.">
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
    <button type="button" class="chatbot-toggle" aria-label="Open chatbot">
        <img class="chatbot-toggle-logo" src="../assets/img/bot_logo.png" alt="Chatbot logo">
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/site-tour.js?v=<?php echo h($tour_js_version); ?>"></script>
<script src="../assets/js/google-translate-switcher.js?v=<?php echo h($translate_js_version); ?>"></script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<script src="../chatbot/chatbot.js?v=<?php echo h($chatbot_js_version); ?>"></script>
</body>
</html>
