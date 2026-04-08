<?php
include("../config/security.php");
include("../config/db.php");
security_require_user("login.php");

$user_id = (int)$_SESSION["user_id"];
$flash = get_flash_message();
$has_address = db_has_column($conn, "complaints", "address");
$user_stmt = mysqli_prepare($conn, "SELECT id, name, email, phone FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = $user_result ? mysqli_fetch_assoc($user_result) : null;
if (!$user) { session_unset(); session_destroy(); security_start_session(); set_flash_message("warning", "Your account session could not be validated. Please log in again."); header("Location: login.php"); exit(); }

$address_select = $has_address ? "address" : "NULL AS address";
$stmt = mysqli_prepare($conn, "SELECT id, crime_type, description, {$address_select}, evidence, status, created_at FROM complaints WHERE user_id=? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$complaints = [];
$stats = ["total" => 0, "pending" => 0, "investigating" => 0, "resolved" => 0];
while ($result && $row = mysqli_fetch_assoc($result)) { $complaints[] = $row; $stats["total"]++; if ($row["status"] === "Pending") $stats["pending"]++; elseif ($row["status"] === "Under Investigation" || $row["status"] === "Investigating") $stats["investigating"]++; elseif ($row["status"] === "Resolved") $stats["resolved"]++; }

$notifications = [];
$unread_count = 0;
if (db_table_exists($conn, "notifications")) {
    $notif_stmt = mysqli_prepare($conn, "SELECT id, complaint_id, message, type, is_read, created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 12");
    mysqli_stmt_bind_param($notif_stmt, "i", $user_id);
    mysqli_stmt_execute($notif_stmt);
    $notif_result = mysqli_stmt_get_result($notif_stmt);
    while ($notif_result && $n = mysqli_fetch_assoc($notif_result)) { $notifications[] = $n; if ((int)$n["is_read"] === 0) $unread_count++; }
}

function complaint_status_class($status) {
    if ($status === "Pending") return "status-pending";
    if ($status === "Under Investigation" || $status === "Investigating") return "status-investigating";
    if ($status === "Resolved") return "status-resolved";
    return "status-secondary";
}

function notification_href($n) {
    $cid = (int)($n["complaint_id"] ?? 0);
    $type = $n["type"] ?? "update";
    if ($cid <= 0) return "#";
    if ($type === "chat") return "chat.php?complaint_id=" . $cid;
    if ($type === "update") return "report.php?complaint_id=" . $cid;
    return "../track.php?complaint_id=" . $cid;
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
<nav class="navbar navbar-expand-lg glass-nav"><div class="container py-2"><a class="navbar-brand d-flex align-items-center gap-3 fw-semibold" href="../index.php"><span class="navbar-brand-mark"><i class="fa-solid fa-shield-halved"></i></span><span class="navbar-brand-text">Crime Reporting System<small>Citizen service dashboard</small></span></a><button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#userNav"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="userNav"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 me-lg-3"><li class="nav-item"><a class="nav-link app-nav-link" href="../index.php">Home</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="report.php">Report Crime</a></li><li class="nav-item"><a class="nav-link app-nav-link active" href="dashboard.php">Dashboard</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="../track.php">Track Complaint</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="../public_stats.php">Statistics</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="logout.php">Logout</a></li></ul><div class="dropdown"><button class="btn btn-outline-primary position-relative" type="button" data-bs-toggle="dropdown" id="notificationBell"><i class="fa-regular fa-bell"></i><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationCount" <?php echo $unread_count > 0 ? "" : "hidden"; ?>><?php echo (int)$unread_count; ?></span></button><div class="dropdown-menu dropdown-menu-end p-0 overflow-hidden" style="width:min(380px, 92vw);"><div class="p-3 border-bottom d-flex justify-content-between align-items-center"><strong>Notifications</strong><button class="btn btn-sm btn-link text-decoration-none" type="button" id="markAllRead">Mark all read</button></div><div class="list-group list-group-flush" id="notificationList"><?php if ($notifications) { foreach ($notifications as $n) { ?><a class="list-group-item list-group-item-action <?php echo ((int)$n["is_read"] === 0) ? "bg-light" : ""; ?>" href="<?php echo h(notification_href($n)); ?>" data-notification-id="<?php echo (int)$n["id"]; ?>"><div class="fw-semibold small"><?php echo h($n["message"]); ?></div><div class="small text-muted"><?php echo h($n["created_at"]); ?></div></a><?php }} else { ?><div class="p-3 text-muted small">No notifications yet.</div><?php } ?></div></div></div></div></div></nav>
<main class="container py-4 py-lg-5">
    <div class="page-breadcrumb mb-3"><a href="../index.php">Home</a><i class="fa-solid fa-chevron-right small"></i><span class="current">Dashboard</span></div>
    <?php if ($flash) { ?><div class="alert alert-<?php echo h($flash["type"]); ?> mb-4"><?php echo h($flash["text"]); ?></div><?php } ?>
    <section class="surface-card p-4 p-lg-5 mb-4"><div class="row align-items-center g-4"><div class="col-lg-8"><p class="text-uppercase small fw-semibold text-primary mb-2">Citizen Dashboard</p><h1 class="section-title mb-2">Welcome, <?php echo h($user["name"]); ?></h1><p class="section-copy mb-0">Track complaints, open live chat per complaint, and respond immediately when admin sends updates.</p></div><div class="col-lg-4"><div class="surface-card p-4"><div class="small text-uppercase fw-semibold text-primary mb-2">Quick Actions</div><div class="d-grid gap-2"><a class="btn btn-primary" href="report.php">New Complaint</a><a class="btn btn-outline-primary" href="../track.php">Track by ID</a></div></div></div></div></section>
    <section class="stats-grid mb-4"><div class="metric-card"><div class="metric-label">Total Complaints</div><div class="metric-value"><?php echo (int)$stats["total"]; ?></div><div class="metric-trend">All complaints linked to your account</div></div><div class="metric-card"><div class="metric-label">Pending</div><div class="metric-value"><?php echo (int)$stats["pending"]; ?></div><div class="metric-trend">Awaiting review</div></div><div class="metric-card"><div class="metric-label">Investigating</div><div class="metric-value"><?php echo (int)$stats["investigating"]; ?></div><div class="metric-trend">Active case handling</div></div><div class="metric-card"><div class="metric-label">Resolved</div><div class="metric-value"><?php echo (int)$stats["resolved"]; ?></div><div class="metric-trend">Completed complaints</div></div></section>
    <section class="row g-4" id="complaint-history"><div class="col-lg-8"><div class="card p-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><p class="text-uppercase small fw-semibold text-primary mb-1">Complaint History</p><h4 class="mb-0">Track every complaint and open live chat</h4></div><a href="report.php" class="btn btn-primary">Report Crime</a></div><div class="table-responsive"><table class="table table-modern align-middle"><thead><tr><th>ID</th><th>Crime</th><th>Status</th><?php if ($has_address) { ?><th>Location</th><?php } ?><th>Date</th><th>Actions</th></tr></thead><tbody><?php if ($complaints) { foreach ($complaints as $row) { ?><tr><td class="fw-semibold">#<?php echo (int)$row["id"]; ?></td><td><div class="fw-semibold"><?php echo h($row["crime_type"]); ?></div><div class="small muted"><?php echo h($row["description"]); ?></div></td><td><span class="status-pill <?php echo complaint_status_class($row["status"]); ?>"><?php echo h($row["status"]); ?></span></td><?php if ($has_address) { ?><td class="small muted"><?php echo h($row["address"]); ?></td><?php } ?><td class="small muted"><?php echo h($row["created_at"]); ?></td><td><div class="d-flex gap-2 flex-wrap"><a class="btn btn-sm btn-outline-primary" href="chat.php?complaint_id=<?php echo (int)$row["id"]; ?>">Open Chat</a><a class="btn btn-sm btn-outline-secondary" href="../track.php?complaint_id=<?php echo (int)$row["id"]; ?>">Track</a><?php if (!empty($row["evidence"])) { ?><a class="btn btn-sm btn-outline-dark" href="../uploads/<?php echo urlencode($row["evidence"]); ?>" target="_blank" rel="noopener">Evidence</a><?php } ?></div></td></tr><?php }} else { ?><tr><td colspan="<?php echo $has_address ? 6 : 5; ?>" class="empty-state">No complaints submitted yet.</td></tr><?php } ?></tbody></table></div></div></div><div class="col-lg-4"><div class="card p-4 h-100"><p class="text-uppercase small fw-semibold text-primary mb-1">Latest Notifications</p><h4 class="mb-3">Status and chat updates</h4><div class="vstack gap-3" id="notificationPanel"><?php if ($notifications) { foreach ($notifications as $n) { ?><a class="surface-card p-3 text-dark <?php echo ((int)$n["is_read"] === 0) ? "border border-primary-subtle" : ""; ?>" href="<?php echo h(notification_href($n)); ?>"><div class="fw-semibold mb-1"><?php echo h($n["message"]); ?></div><div class="small muted"><?php echo h($n["created_at"]); ?></div></a><?php }} else { ?><div class="empty-state">No notifications yet.</div><?php } ?></div></div></div></section>
</main>
<div id="crimeChatbot" class="chatbot-widget" data-api-url="../chatbot/chatbot_api.php"><div class="chatbot-window"><div class="chatbot-header"><span class="chatbot-title">AI Safety Assistant</span><button type="button" class="chatbot-close" aria-label="Close chatbot">&times;</button></div><div class="chatbot-messages"></div><div class="chatbot-input-wrap"><input type="text" class="chatbot-input" placeholder="Ask about your complaint or reporting" aria-label="Chat input"><button type="button" class="chatbot-send">Send</button></div></div><button type="button" class="chatbot-toggle" aria-label="Open chatbot"><i class="fa-solid fa-comments"></i></button></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script><script src="../assets/js/app.js"></script><script src="../chatbot/chatbot.js"></script><script>
document.addEventListener("DOMContentLoaded", function () { const bellCount = document.getElementById("notificationCount"), list = document.getElementById("notificationList"), panel = document.getElementById("notificationPanel"), markAllBtn = document.getElementById("markAllRead"); function href(item){ if (!item.complaint_id) return '#'; if (item.type === 'chat') return `chat.php?complaint_id=${item.complaint_id}`; if (item.type === 'update') return `report.php?complaint_id=${item.complaint_id}`; return `../track.php?complaint_id=${item.complaint_id}`; } function esc(text){ const div=document.createElement('div'); div.textContent=text||''; return div.innerHTML; } function renderNotifications(items){ const unread = items.filter(item => Number(item.is_read) === 0).length; bellCount.textContent = unread; bellCount.hidden = unread === 0; if (!items.length) { list.innerHTML = '<div class="p-3 text-muted small">No notifications yet.</div>'; panel.innerHTML = '<div class="empty-state">No notifications yet.</div>'; return; } list.innerHTML = items.map(item => `<a class="list-group-item list-group-item-action ${Number(item.is_read) === 0 ? 'bg-light' : ''}" href="${href(item)}" data-notification-id="${item.id}"><div class="fw-semibold small">${esc(item.message)}</div><div class="small text-muted">${esc(item.created_at)}</div></a>`).join(''); panel.innerHTML = items.map(item => `<a class="surface-card p-3 text-dark ${Number(item.is_read) === 0 ? 'border border-primary-subtle' : ''}" href="${href(item)}"><div class="fw-semibold mb-1">${esc(item.message)}</div><div class="small muted">${esc(item.created_at)}</div></a>`).join(''); }
function fetchNotifications(){ fetch('../api/get_notifications.php').then(response => response.json()).then(data => { if (data && data.success) renderNotifications(data.notifications || []); }).catch(() => {}); }
function markRead(notificationId, markAll){ const body = new URLSearchParams(); body.set('action', markAll ? 'mark_all_read' : 'mark_read'); if (notificationId) body.set('notification_id', notificationId); fetch('../api/get_notifications.php', { method:'POST', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: body.toString() }).then(response => response.json()).then(data => { if (data && data.success) fetchNotifications(); }).catch(() => {}); }
list.addEventListener('click', function (event) { const link = event.target.closest('[data-notification-id]'); if (!link) return; markRead(link.getAttribute('data-notification-id'), false); }); markAllBtn.addEventListener('click', function(){ markRead(null, true); }); fetchNotifications(); window.setInterval(fetchNotifications, 3000); });
</script>
</body>
</html>
