<?php
include("auth.php");
include("../config/db.php");

$complaint_id = (int)($_GET["id"] ?? 0);
if ($complaint_id <= 0) { header("Location: complaints.php"); exit(); }
$has_address = db_has_column($conn, "complaints", "address");
$address_select = $has_address ? "c.address" : "NULL AS address";
$stmt = mysqli_prepare($conn, "SELECT c.id, c.user_id, c.crime_type, c.description, c.latitude, c.longitude, {$address_select}, c.evidence, c.status, c.created_at, u.name, u.email, u.phone FROM complaints c JOIN users u ON c.user_id = u.id WHERE c.id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $complaint_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$complaint = $result ? mysqli_fetch_assoc($result) : null;
if (!$complaint) { header("Location: complaints.php"); exit(); }
function detail_status_class($status) {
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
    <title>Complaint Details</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <style>
        #detailMap{height:320px}.chat-shell{height:520px;display:flex;flex-direction:column}.case-chat-box{flex:1;overflow:auto;padding:1rem;background:#f8fbff;border-radius:20px;border:1px solid #dbe4f0}.case-msg{display:flex;margin-bottom:1rem}.case-msg.user{justify-content:flex-start}.case-msg.admin{justify-content:flex-end}.case-bubble{max-width:76%;padding:.85rem 1rem;border-radius:18px;box-shadow:0 10px 24px rgba(15,23,42,.06)}.case-msg.admin .case-bubble{background:#1a73e8;color:#fff;border-bottom-right-radius:8px}.case-msg.user .case-bubble{background:#eef2f7;color:#1e293b;border-bottom-left-radius:8px}.case-meta{font-size:.75rem;color:#64748b;margin-bottom:.3rem}.case-form{display:flex;gap:.75rem;margin-top:1rem}.case-form textarea{min-height:56px;max-height:140px}@media (max-width:767.98px){.chat-shell{height:72vh}.case-form{flex-direction:column}.case-bubble{max-width:88%}}
    </style>
</head>
<body>
<div class="container py-4">
    <div class="page-breadcrumb mb-3"><a href="complaints.php">Complaints</a><i class="fa-solid fa-chevron-right small"></i><span class="current">Complaint #<?php echo (int)$complaint["id"]; ?></span></div>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3"><div><p class="text-uppercase small fw-semibold text-primary mb-1">Complaint Record</p><h3 class="mb-0">Complaint #<?php echo (int)$complaint["id"]; ?></h3></div><span class="status-pill <?php echo detail_status_class($complaint["status"]); ?>"><?php echo h($complaint["status"]); ?></span></div>
                <p><strong>Crime Type:</strong> <?php echo h($complaint["crime_type"]); ?></p>
                <p><strong>Description:</strong> <?php echo h($complaint["description"]); ?></p>
                <p><strong>Location:</strong> <?php echo h($complaint["address"] ?: "Not available"); ?></p>
                <p><strong>Created:</strong> <?php echo h($complaint["created_at"]); ?></p>
                <form action="update_status.php" method="POST" class="row g-2">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="complaint_id" value="<?php echo (int)$complaint["id"]; ?>">
                    <div class="col-md-8"><select class="form-select" name="status"><option value="Pending" <?php echo ($complaint["status"] === "Pending") ? "selected" : ""; ?>>Pending</option><option value="Under Investigation" <?php echo ($complaint["status"] === "Under Investigation" || $complaint["status"] === "Investigating") ? "selected" : ""; ?>>Under Investigation</option><option value="Resolved" <?php echo ($complaint["status"] === "Resolved") ? "selected" : ""; ?>>Resolved</option></select></div>
                    <div class="col-md-4 d-grid"><button class="btn btn-primary" type="submit">Update Status</button></div>
                </form>
                <?php if (!empty($complaint["evidence"])) { ?><div class="mt-3"><strong>Evidence:</strong><div class="mt-2"><img src="../uploads/<?php echo urlencode($complaint["evidence"]); ?>" alt="Evidence" class="img-fluid rounded border"></div></div><?php } ?>
            </div>
            <div class="card p-4">
                <h5 class="mb-3">Incident Location</h5>
                <div class="map-shell"><div class="map-frame"><div id="detailMap"></div></div></div>
                <p class="small muted mt-2 mb-0">Lat: <?php echo h($complaint["latitude"]); ?> · Lng: <?php echo h($complaint["longitude"]); ?></p>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card p-4 mb-4">
                <h5 class="mb-3">User Information</h5>
                <p><strong>Name:</strong> <?php echo h($complaint["name"]); ?></p>
                <p><strong>Email:</strong> <?php echo h($complaint["email"]); ?></p>
                <p class="mb-0"><strong>Phone:</strong> <?php echo h($complaint["phone"]); ?></p>
            </div>
            <div class="card p-4 chat-shell">
                <div class="d-flex justify-content-between align-items-center mb-3"><div><h5 class="mb-1">Chat with User</h5><div class="small muted" id="chatStatus">Conversation refreshes every 3 seconds.</div></div><a href="complaints.php" class="btn btn-outline-primary btn-sm">Back</a></div>
                <div id="chatMessages" class="case-chat-box"></div>
                <form id="chatForm" class="case-form">
                    <textarea id="chatInput" class="form-control" placeholder="Send a message to the user..." required></textarea>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-2"></i>Send</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../assets/js/app.js"></script>
<script>
const lat = parseFloat("<?php echo (float)$complaint["latitude"]; ?>") || 17.385; const lng = parseFloat("<?php echo (float)$complaint["longitude"]; ?>") || 78.4867; const map = L.map("detailMap").setView([lat, lng], 15); L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { attribution: "&copy; OpenStreetMap contributors" }).addTo(map); L.marker([lat, lng]).addTo(map).bindPopup("Complaint #<?php echo (int)$complaint["id"]; ?>").openPopup();
document.addEventListener("DOMContentLoaded", function(){
    const complaintId = <?php echo (int)$complaint_id; ?>, chatMessages = document.getElementById("chatMessages"), chatForm = document.getElementById("chatForm"), chatInput = document.getElementById("chatInput"), chatStatus = document.getElementById("chatStatus"); let lastMessageId = 0, initialized = false;
    function esc(text){ const div=document.createElement("div"); div.textContent=text||""; return div.innerHTML; }
    function scrollBottom(){ chatMessages.scrollTop = chatMessages.scrollHeight; }
    function render(messages){ const previousLast = lastMessageId; if (messages.length) lastMessageId = Number(messages[messages.length - 1].id); chatMessages.innerHTML = messages.length ? messages.map(msg => { const role = msg.sender_role === "admin" ? "admin" : "user"; const status = role === "admin" ? (Number(msg.is_seen) === 1 ? "Seen" : "Delivered") : ""; return `<div class="case-msg ${role}"><div><div class="case-meta">${role === "admin" ? "You" : "User"} · ${esc(msg.sent_at)} ${status ? '· ' + status : ''}</div><div class="case-bubble">${esc(msg.message_text)}</div></div></div>`; }).join("") : '<div class="empty-state">No messages yet.</div>'; if (!initialized || previousLast !== lastMessageId) scrollBottom(); initialized = true; }
    function fetchMessages(){ fetch(`../api/get_messages.php?complaint_id=${complaintId}`).then(r => r.json()).then(data => { if (data && data.success) { render(data.messages || []); return fetch("../api/mark_seen.php", { method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, body:`complaint_id=${complaintId}`}); } }).catch(() => { chatStatus.textContent = "Unable to refresh chat right now."; }); }
    chatForm.addEventListener("submit", function(e){ e.preventDefault(); const text = chatInput.value.trim(); if (!text) return; chatStatus.textContent = "Sending message..."; const fd = new FormData(); fd.append("complaint_id", complaintId); fd.append("message_text", text); fetch("../api/send_message.php", { method:"POST", body: fd }).then(r => r.json()).then(data => { if (data && data.success) { chatInput.value = ""; chatStatus.textContent = "Message sent."; fetchMessages(); } else { chatStatus.textContent = (data && data.error) || "Unable to send message."; } }).catch(() => { chatStatus.textContent = "Unable to send message."; }); });
    fetchMessages(); window.setInterval(fetchMessages, 3000);
});
</script>
</body>
</html>
