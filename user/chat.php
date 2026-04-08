<?php
include("../config/security.php");
include("../config/db.php");
security_require_user("login.php");

$user_id = (int)$_SESSION["user_id"];
$complaint_id = (int)($_GET["complaint_id"] ?? 0);
if ($complaint_id <= 0) {
    set_flash_message("warning", "Complaint ID is required to open chat.");
    header("Location: dashboard.php");
    exit();
}

$has_address = db_has_column($conn, "complaints", "address");
$address_select = $has_address ? "address" : "NULL AS address";
$stmt = mysqli_prepare($conn, "SELECT id, crime_type, description, status, {$address_select}, created_at FROM complaints WHERE id=? AND user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $complaint_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$complaint = $result ? mysqli_fetch_assoc($result) : null;
if (!$complaint) {
    set_flash_message("warning", "Complaint not found for your account.");
    header("Location: dashboard.php");
    exit();
}

function chat_status_class($status) {
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
    <title>Complaint Chat</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <style>
        .chat-shell{height:620px;display:flex;flex-direction:column}.case-chat-box{flex:1;overflow:auto;padding:1rem;background:#f8fbff;border-radius:20px;border:1px solid #dbe4f0}.case-msg{display:flex;margin-bottom:1rem}.case-msg.user{justify-content:flex-end}.case-msg.admin{justify-content:flex-start}.case-bubble{max-width:76%;padding:.85rem 1rem;border-radius:18px;box-shadow:0 10px 24px rgba(15,23,42,.06)}.case-msg.user .case-bubble{background:#1a73e8;color:#fff;border-bottom-right-radius:8px}.case-msg.admin .case-bubble{background:#eef2f7;color:#1e293b;border-bottom-left-radius:8px}.case-meta{font-size:.75rem;color:#64748b;margin-bottom:.3rem}.case-form{display:flex;gap:.75rem;margin-top:1rem}.case-form textarea{min-height:56px;max-height:140px}.typing-note{font-size:.85rem;color:#64748b}
        @media (max-width: 767.98px){.chat-shell{height:72vh}.case-bubble{max-width:88%}.case-form{flex-direction:column}}
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg glass-nav"><div class="container py-2"><a class="navbar-brand d-flex align-items-center gap-3 fw-semibold" href="../index.php"><span class="navbar-brand-mark"><i class="fa-solid fa-shield-halved"></i></span><span class="navbar-brand-text">Crime Reporting System<small>Complaint communication center</small></span></a><button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#chatNav"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="chatNav"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 me-lg-3"><li class="nav-item"><a class="nav-link app-nav-link" href="../index.php">Home</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="report.php">Report Crime</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="dashboard.php">Dashboard</a></li><li class="nav-item"><a class="nav-link app-nav-link active" href="chat.php?complaint_id=<?php echo (int)$complaint_id; ?>">Open Chat</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="logout.php">Logout</a></li></ul></div></div></nav>
<main class="container py-4 py-lg-5">
    <div class="page-breadcrumb mb-3"><a href="../index.php">Home</a><i class="fa-solid fa-chevron-right small"></i><a href="dashboard.php">Dashboard</a><i class="fa-solid fa-chevron-right small"></i><span class="current">Complaint Chat</span></div>
    <section class="surface-card p-4 mb-4"><div class="row g-3 align-items-center"><div class="col-lg-8"><p class="text-uppercase small fw-semibold text-primary mb-2">Complaint Conversation</p><h2 class="mb-2">Complaint #<?php echo (int)$complaint["id"]; ?> · <?php echo h($complaint["crime_type"]); ?></h2><p class="muted mb-0"><?php echo h($complaint["description"]); ?></p></div><div class="col-lg-4"><div class="surface-card p-3"><div class="small text-uppercase fw-semibold text-primary mb-2">Status</div><span class="status-pill <?php echo chat_status_class($complaint["status"]); ?>"><?php echo h($complaint["status"]); ?></span><div class="small muted mt-2"><?php echo h($complaint["created_at"]); ?></div></div></div></div></section>
    <section class="card p-4 chat-shell">
        <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="mb-1">Admin Chat</h4><div class="typing-note" id="chatStatus">Messages refresh every 3 seconds.</div></div><a href="dashboard.php" class="btn btn-outline-primary">Back</a></div>
        <div id="chatMessages" class="case-chat-box"></div>
        <form id="chatForm" class="case-form">
            <textarea id="chatInput" class="form-control" placeholder="Type your message to admin..." required></textarea>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-2"></i>Send</button>
        </form>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function(){
    const complaintId = <?php echo (int)$complaint_id; ?>;
    const chatMessages = document.getElementById("chatMessages");
    const chatForm = document.getElementById("chatForm");
    const chatInput = document.getElementById("chatInput");
    const chatStatus = document.getElementById("chatStatus");
    let lastMessageId = 0;
    let initialized = false;

    function beep(){ try { const ctx = new (window.AudioContext || window.webkitAudioContext)(); const o = ctx.createOscillator(); const g = ctx.createGain(); o.connect(g); g.connect(ctx.destination); o.type = "sine"; o.frequency.value = 880; g.gain.value = 0.02; o.start(); o.stop(ctx.currentTime + 0.12);} catch(e){} }
    function esc(text){ const div=document.createElement("div"); div.textContent=text||""; return div.innerHTML; }
    function scrollBottom(){ chatMessages.scrollTop = chatMessages.scrollHeight; }

    function render(messages){
        const previousLast = lastMessageId;
        if (messages.length) lastMessageId = Number(messages[messages.length - 1].id);
        chatMessages.innerHTML = messages.length ? messages.map(msg => {
            const role = msg.sender_role === "user" ? "user" : "admin";
            const status = role === "user" ? (Number(msg.is_seen) === 1 ? "Seen" : "Delivered") : "";
            const attachment = msg.attachment ? `<div class="mt-2"><a href="../uploads/${encodeURIComponent(msg.attachment)}" target="_blank" rel="noopener">View attachment</a></div>` : "";
            return `<div class="case-msg ${role}"><div><div class="case-meta">${role === "user" ? "You" : "Admin"} · ${esc(msg.sent_at)} ${status ? '· ' + status : ''}</div><div class="case-bubble">${esc(msg.message_text)}${attachment}</div></div></div>`;
        }).join("") : '<div class="empty-state">No messages yet. Start the conversation.</div>';
        if (!initialized || lastMessageId !== previousLast) scrollBottom();
        if (initialized && lastMessageId > previousLast && messages.some(msg => Number(msg.id) === lastMessageId && msg.sender_role === "admin")) beep();
        initialized = true;
    }

    function fetchMessages(){
        fetch(`../api/get_messages.php?complaint_id=${complaintId}`)
            .then(r => r.json())
            .then(data => { if (data && data.success) { render(data.messages || []); return fetch("../api/mark_seen.php", { method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, body:`complaint_id=${complaintId}`}); } })
            .catch(() => { chatStatus.textContent = "Unable to refresh chat right now."; });
    }

    chatForm.addEventListener("submit", function(e){
        e.preventDefault();
        const text = chatInput.value.trim();
        if (!text) return;
        chatStatus.textContent = "Sending message...";
        fetch("../api/send_message.php", { method:"POST", body:(() => { const fd = new FormData(); fd.append("complaint_id", complaintId); fd.append("message_text", text); return fd; })() })
            .then(r => r.json())
            .then(data => { if (data && data.success) { chatInput.value = ""; chatStatus.textContent = "Message sent."; fetchMessages(); } else { chatStatus.textContent = (data && data.error) || "Unable to send message."; } })
            .catch(() => { chatStatus.textContent = "Unable to send message."; });
    });

    fetchMessages();
    window.setInterval(fetchMessages, 3000);
});
</script>
</body>
</html>
