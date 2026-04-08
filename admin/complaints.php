<?php
include("auth.php");
include("../config/db.php");

$flash = get_flash_message();
$crime_type = trim($_GET["crime_type"] ?? "");
$status = trim($_GET["status"] ?? "");
$date_from = trim($_GET["date_from"] ?? "");
$date_to = trim($_GET["date_to"] ?? "");
$location = trim($_GET["location"] ?? "");

$has_address = db_has_column($conn, "complaints", "address");
$address_select = $has_address ? "c.address" : "NULL AS address";
$where = [];
$params = [];
$types = "";

if ($crime_type !== "") { $where[] = "c.crime_type = ?"; $params[] = $crime_type; $types .= "s"; }
if ($status !== "") { $where[] = "c.status = ?"; $params[] = $status; $types .= "s"; }
if ($date_from !== "") { $where[] = "DATE(c.created_at) >= ?"; $params[] = $date_from; $types .= "s"; }
if ($date_to !== "") { $where[] = "DATE(c.created_at) <= ?"; $params[] = $date_to; $types .= "s"; }
if ($location !== "" && $has_address) { $where[] = "c.address LIKE ?"; $params[] = "%" . $location . "%"; $types .= "s"; }

$sql = "SELECT c.id, c.user_id, c.crime_type, c.description, {$address_select}, c.status, c.created_at, c.evidence, u.name AS user_name, u.email AS user_email FROM complaints c JOIN users u ON c.user_id = u.id";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY c.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($types !== "") mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$complaints = [];
while ($result && $row = mysqli_fetch_assoc($result)) $complaints[] = $row;

$type_stmt = mysqli_prepare($conn, "SELECT DISTINCT crime_type FROM complaints ORDER BY crime_type");
mysqli_stmt_execute($type_stmt);
$type_result = mysqli_stmt_get_result($type_stmt);
$crime_types = [];
while ($type_result && $t = mysqli_fetch_assoc($type_result)) $crime_types[] = $t["crime_type"];

$message_threads = [];
if ($complaints && db_table_exists($conn, "messages")) {
    $complaint_ids = array_map(static function ($item) { return (int)$item["id"]; }, $complaints);
    $placeholders = implode(",", array_fill(0, count($complaint_ids), "?"));
    $message_sql = "SELECT id, complaint_id, sender_id, message_text, attachment, sent_at, is_seen FROM messages WHERE complaint_id IN ({$placeholders}) ORDER BY sent_at ASC, id ASC";
    $message_stmt = mysqli_prepare($conn, $message_sql);
    mysqli_stmt_bind_param($message_stmt, str_repeat("i", count($complaint_ids)), ...$complaint_ids);
    mysqli_stmt_execute($message_stmt);
    $message_result = mysqli_stmt_get_result($message_stmt);
    while ($message_result && $message = mysqli_fetch_assoc($message_result)) {
        $message["sender_role"] = ((int)$message["sender_id"] < 0) ? "admin" : "user";
        $thread_id = (int)$message["complaint_id"];
        if (!isset($message_threads[$thread_id])) $message_threads[$thread_id] = [];
        $message_threads[$thread_id][] = $message;
    }
}

function admin_status_class($status) {
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
    <title>Manage Complaints</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="d-flex align-items-center gap-3 mb-4"><span class="navbar-brand-mark"><i class="fa-solid fa-shield-halved"></i></span><div><div class="fw-semibold">Admin Control</div><div class="small text-white-50">Complaint management console</div></div></div>
        <nav class="nav flex-column mb-4"><a class="nav-link" href="dashboard.php"><i class="fa-solid fa-chart-line me-2"></i>Dashboard</a><a class="nav-link active" href="complaints.php"><i class="fa-solid fa-table-list me-2"></i>Manage Complaints</a><a class="nav-link" href="../public_stats.php"><i class="fa-solid fa-chart-pie me-2"></i>Public Statistics</a><a class="nav-link" href="../index.php"><i class="fa-solid fa-house me-2"></i>Public Portal</a><a class="nav-link" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></nav>
        <div class="surface-card p-3 text-dark"><div class="small text-uppercase fw-semibold text-primary mb-2">Admin Actions</div><div class="small muted">Review evidence, update complaint statuses, and keep the citizen conversation organized from one workspace.</div></div>
    </aside>
    <main class="admin-main">
        <div class="page-breadcrumb mb-3"><a href="../index.php">Home</a><i class="fa-solid fa-chevron-right small"></i><span class="current">Manage Complaints</span></div>
        <?php if ($flash) { ?><div class="alert alert-<?php echo h($flash["type"]); ?> mb-4"><?php echo h($flash["text"]); ?></div><?php } ?>
        <section class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3"><div><p class="text-uppercase small fw-semibold text-primary mb-1">Filters</p><h4 class="mb-0">Search complaint queue</h4></div><a href="complaints.php" class="btn btn-outline-primary">Reset</a></div>
            <form method="GET" class="row g-3">
                <div class="col-md-6 col-xl-2"><label class="form-label">Crime Type</label><select name="crime_type" class="form-select"><option value="">All</option><?php foreach ($crime_types as $type_option) { ?><option value="<?php echo h($type_option); ?>" <?php echo ($crime_type === $type_option) ? "selected" : ""; ?>><?php echo h($type_option); ?></option><?php } ?></select></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option><option value="Pending" <?php echo ($status === "Pending") ? "selected" : ""; ?>>Pending</option><option value="Under Investigation" <?php echo ($status === "Under Investigation") ? "selected" : ""; ?>>Under Investigation</option><option value="Resolved" <?php echo ($status === "Resolved") ? "selected" : ""; ?>>Resolved</option></select></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="<?php echo h($date_from); ?>"></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="<?php echo h($date_to); ?>"></div>
                <div class="col-xl-3"><label class="form-label">Location Keyword</label><input type="text" name="location" class="form-control" value="<?php echo h($location); ?>" <?php echo $has_address ? "" : "disabled"; ?>></div>
                <div class="col-xl-1 d-grid"><label class="form-label">&nbsp;</label><button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button></div>
            </form>
        </section>
        <section class="card p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4"><div><p class="text-uppercase small fw-semibold text-primary mb-1">Complaint Queue</p><h4 class="mb-0">Review, update, and communicate clearly</h4></div><div class="small muted"><?php echo count($complaints); ?> results</div></div>
            <?php if ($complaints) { ?>
                <div class="complaint-card-list">
                    <?php foreach ($complaints as $row) {
                        $row_id = (int)$row["id"];
                        $thread = $message_threads[$row_id] ?? [];
                    ?>
                    <article class="complaint-admin-card card">
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-md-8">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                        <div>
                                            <div class="complaint-label">Complaint</div>
                                            <h4 class="mb-1">#<?php echo $row_id; ?> &middot; <?php echo h($row["crime_type"]); ?></h4>
                                            <div class="small muted">Submitted on <?php echo h($row["created_at"]); ?></div>
                                        </div>
                                        <span class="status-pill <?php echo admin_status_class($row["status"]); ?>"><?php echo h($row["status"]); ?></span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <div class="complaint-info-block h-100">
                                                <div class="complaint-info-label">User</div>
                                                <div class="fw-semibold"><?php echo h($row["user_name"]); ?></div>
                                                <div class="small muted"><?php echo h($row["user_email"]); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="complaint-info-block h-100">
                                                <div class="complaint-info-label">Location</div>
                                                <div class="small"><?php echo h($row["address"] ?: "Location not available"); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="complaint-info-block">
                                                <div class="complaint-info-label">Description</div>
                                                <p class="mb-0 small"><?php echo nl2br(h($row["description"])); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="complaint-info-block">
                                                <div class="complaint-info-label">Evidence Preview</div>
                                                <?php if (!empty($row["evidence"])) { ?>
                                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                                        <a href="../uploads/<?php echo urlencode($row["evidence"]); ?>" target="_blank" rel="noopener">
                                                            <img src="../uploads/<?php echo urlencode($row["evidence"]); ?>" class="complaint-evidence-preview" alt="Evidence preview">
                                                        </a>
                                                        <div class="small muted">Click the preview to inspect the uploaded evidence.</div>
                                                    </div>
                                                <?php } else { ?>
                                                    <div class="small muted">No evidence file uploaded.</div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="complaint-action-panel h-100" data-complaint-id="<?php echo $row_id; ?>">
                                        <div class="mb-3">
                                            <a class="btn btn-outline-primary w-100" href="complaint_details.php?id=<?php echo $row_id; ?>">Details</a>
                                        </div>
                                        <form action="update_status.php" method="POST" class="mb-3" data-loading-target="#row-loading-<?php echo $row_id; ?>">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="complaint_id" value="<?php echo $row_id; ?>">
                                            <label class="form-label small">Status</label>
                                            <select name="status" class="form-select mb-3" required>
                                                <option value="Pending" <?php echo ($row["status"] === "Pending") ? "selected" : ""; ?>>Pending</option>
                                                <option value="Under Investigation" <?php echo ($row["status"] === "Under Investigation" || $row["status"] === "Investigating") ? "selected" : ""; ?>>Under Investigation</option>
                                                <option value="Resolved" <?php echo ($row["status"] === "Resolved") ? "selected" : ""; ?>>Resolved</option>
                                            </select>
                                            <button type="submit" class="btn btn-primary w-100">Update</button>
                                        </form>
                                        <div class="loading-spinner mb-3" id="row-loading-<?php echo $row_id; ?>" hidden>Updating</div>
                                        <div class="complaint-chat-preview mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div class="form-label small mb-0">Previous Messages</div>
                                                <span class="small muted">Auto refresh</span>
                                            </div>
                                            <div class="complaint-chat-box" id="chat-preview-<?php echo $row_id; ?>"></div>
                                        </div>
                                        <form class="admin-message-form mb-3" data-complaint-id="<?php echo $row_id; ?>">
                                            <label class="form-label small">Message Box</label>
                                            <textarea class="form-control complaint-message-input mb-3" rows="4" placeholder="Write a clear update for the user..." required></textarea>
                                            <div class="small muted mb-3 chat-status-text" id="chat-status-<?php echo $row_id; ?>">Messages load automatically for this complaint.</div>
                                            <button type="submit" class="btn btn-outline-primary w-100">Send Message</button>
                                        </form>
                                        <form action="../api/send_notification.php" method="POST" class="mb-0">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="complaint_id" value="<?php echo $row_id; ?>">
                                            <input type="hidden" name="message" value="Please update complaint details and resubmit the complaint.">
                                            <button type="submit" class="btn btn-outline-secondary w-100">Request Re-submit</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="empty-state">No complaints found.</div>
            <?php } ?>
        </section>
    </main>
</div>
<script src="../assets/js/app.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const initialThreads = <?php echo json_encode($message_threads, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const cards = Array.from(document.querySelectorAll(".complaint-action-panel"));

    function escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text || "";
        return div.innerHTML;
    }

    function scrollChatBox(box) {
        box.scrollTop = box.scrollHeight;
    }

    function renderMessages(complaintId, messages) {
        const box = document.getElementById("chat-preview-" + complaintId);
        if (!box) return;
        if (!messages || !messages.length) {
            box.innerHTML = '<div class="small muted text-center py-4">No chat messages yet for this complaint.</div>';
            return;
        }
        box.innerHTML = messages.map(function (msg) {
            const role = msg.sender_role === "admin" ? "admin" : "user";
            const sender = role === "admin" ? "Admin" : "User";
            const attachment = msg.attachment ? '<div class="mt-2"><a href="../uploads/' + encodeURIComponent(msg.attachment) + '" target="_blank" rel="noopener">View attachment</a></div>' : "";
            return '<div class="complaint-chat-message ' + role + '"><div class="complaint-chat-meta">' + sender + ' &middot; ' + escapeHtml(msg.sent_at) + '</div><div class="complaint-chat-bubble">' + escapeHtml(msg.message_text) + attachment + '</div></div>';
        }).join("");
        scrollChatBox(box);
    }

    function fetchMessages(complaintId) {
        const statusEl = document.getElementById("chat-status-" + complaintId);
        return fetch("../api/get_messages.php?complaint_id=" + complaintId)
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data && data.success) {
                    renderMessages(complaintId, data.messages || []);
                    if (statusEl) statusEl.textContent = "Conversation refreshed.";
                } else if (statusEl) {
                    statusEl.textContent = "Unable to load messages right now.";
                }
            })
            .catch(function () {
                if (statusEl) statusEl.textContent = "Unable to load messages right now.";
            });
    }

    cards.forEach(function (card) {
        const complaintId = Number(card.getAttribute("data-complaint-id"));
        renderMessages(complaintId, initialThreads[String(complaintId)] || initialThreads[complaintId] || []);
    });

    document.querySelectorAll(".admin-message-form").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            event.preventDefault();
            const complaintId = Number(form.getAttribute("data-complaint-id"));
            const textarea = form.querySelector(".complaint-message-input");
            const statusEl = document.getElementById("chat-status-" + complaintId);
            const text = textarea.value.trim();
            if (!text) return;
            if (statusEl) statusEl.textContent = "Sending message...";
            const payload = new FormData();
            payload.append("complaint_id", complaintId);
            payload.append("message_text", text);
            fetch("../api/send_message.php", { method: "POST", body: payload })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data && data.success) {
                        textarea.value = "";
                        if (statusEl) statusEl.textContent = "Message sent.";
                        return fetchMessages(complaintId);
                    }
                    if (statusEl) statusEl.textContent = (data && data.error) || "Unable to send message.";
                })
                .catch(function () {
                    if (statusEl) statusEl.textContent = "Unable to send message.";
                });
        });
    });

    window.setInterval(function () {
        cards.forEach(function (card) {
            fetchMessages(Number(card.getAttribute("data-complaint-id")));
        });
    }, 6000);
});
</script>
</body>
</html>
