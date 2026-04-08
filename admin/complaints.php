<?php
include("auth.php");
include("../config/db.php");

function admin_status_class($status) {
    if ($status === "Pending") return "status-pending";
    if ($status === "Under Investigation" || $status === "Investigating") return "status-investigating";
    if ($status === "Resolved") return "status-resolved";
    return "status-secondary";
}

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

$sql = "
    SELECT
        c.id, c.crime_type, c.description, {$address_select}, c.status, c.created_at, c.evidence,
        u.name AS user_name, u.email AS user_email
    FROM complaints c
    JOIN users u ON c.user_id = u.id
";
if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY c.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types !== "") {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$complaints = [];
while ($result && $row = mysqli_fetch_assoc($result)) {
    $complaints[] = $row;
}

$type_stmt = mysqli_prepare($conn, "SELECT DISTINCT crime_type FROM complaints ORDER BY crime_type");
mysqli_stmt_execute($type_stmt);
$type_result = mysqli_stmt_get_result($type_stmt);
$crime_types = [];
while ($type_result && $t = mysqli_fetch_assoc($type_result)) {
    $crime_types[] = $t["crime_type"];
}
?>
<!DOCTYPE html>
<html lang="<?php echo h(translation_get_html_lang()); ?>">
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
    <link rel="stylesheet" href="../assets/translation.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="d-flex align-items-center gap-3 mb-4">
            <span class="navbar-brand-mark"><i class="fa-solid fa-shield-halved"></i></span>
            <div>
                <div class="fw-semibold" data-i18n="Admin Control">Admin Control</div>
                <div class="small text-white-50" data-i18n="Complaint management console">Complaint management console</div>
            </div>
        </div>
        <nav class="nav flex-column mb-4">
            <a class="nav-link" href="dashboard.php" data-i18n="Dashboard"><i class="fa-solid fa-chart-line me-2"></i>Dashboard</a>
            <a class="nav-link active" href="complaints.php" data-i18n="Manage Complaints"><i class="fa-solid fa-table-list me-2"></i>Manage Complaints</a>
            <a class="nav-link" href="../public_stats.php" data-i18n="Public Statistics"><i class="fa-solid fa-chart-pie me-2"></i>Public Statistics</a>
            <a class="nav-link" href="../index.php" data-i18n="Public Portal"><i class="fa-solid fa-house me-2"></i>Public Portal</a>
            <a class="nav-link" href="logout.php" data-i18n="Logout"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a>
        </nav>
        <div class="surface-card p-3 text-dark">
            <div class="small text-uppercase fw-semibold text-primary mb-2" data-i18n="Filters">Filters</div>
            <div class="small muted" data-i18n="Search by type, status, date range, and location keywords to narrow the complaint queue.">Search by type, status, date range, and location keywords to narrow the complaint queue.</div>
        </div>
    </aside>

    <main class="admin-main">
        <section class="surface-card p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <p class="text-uppercase small fw-semibold text-primary mb-2" data-i18n="Complaint Operations">Complaint Operations</p>
                    <h1 class="section-title mb-2" data-i18n="Review, filter, and update incoming complaints">Review, filter, and update incoming complaints</h1>
                    <p class="section-copy mb-0" data-i18n="Use the filters to search the queue, inspect evidence, and update case status from the same management table.">Use the filters to search the queue, inspect evidence, and update case status from the same management table.</p>
                </div>
                <div class="col-lg-4">
                    <div class="surface-card p-4">
                        <div class="small text-uppercase fw-semibold text-primary mb-2" data-i18n="Queue Size">Queue Size</div>
                        <h3 class="mb-1"><?php echo count($complaints); ?> <span data-i18n="Results">Results</span></h3>
                        <div class="muted" data-i18n="Records matching the current filter set">Records matching the current filter set</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <p class="text-uppercase small fw-semibold text-primary mb-1" data-i18n="Search & Filters">Search &amp; Filters</p>
                    <h4 class="mb-0" data-i18n="Refine the complaint table">Refine the complaint table</h4>
                </div>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <?php translation_render_language_selector("../"); ?>
                    <a href="complaints.php" class="btn btn-outline-primary" data-i18n="Reset Filters"><i class="fa-solid fa-rotate-left me-2"></i>Reset Filters</a>
                </div>
            </div>
            <form method="GET" class="row g-3">
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" data-i18n="Crime Type">Crime Type</label>
                    <select name="crime_type" class="form-select">
                        <option value="" data-i18n="All">All</option>
                        <?php foreach ($crime_types as $type_option) { ?>
                            <option value="<?php echo h($type_option); ?>" <?php echo ($crime_type === $type_option) ? "selected" : ""; ?>>
                                <?php echo h($type_option); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" data-i18n="Status">Status</label>
                    <select name="status" class="form-select">
                        <option value="" data-i18n="All">All</option>
                        <option value="Pending" <?php echo ($status === "Pending") ? "selected" : ""; ?> data-i18n="Pending">Pending</option>
                        <option value="Under Investigation" <?php echo ($status === "Under Investigation") ? "selected" : ""; ?> data-i18n="Under Investigation">Under Investigation</option>
                        <option value="Resolved" <?php echo ($status === "Resolved") ? "selected" : ""; ?> data-i18n="Resolved">Resolved</option>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" data-i18n="From">From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo h($date_from); ?>">
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" data-i18n="To">To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo h($date_to); ?>">
                </div>
                <div class="col-xl-3">
                    <label class="form-label" data-i18n="Location Keyword">Location Keyword</label>
                    <input type="text" name="location" class="form-control" value="<?php echo h($location); ?>" placeholder="Area, address, or landmark" data-i18n-placeholder <?php echo $has_address ? "" : "disabled"; ?>>
                </div>
                <div class="col-xl-1 d-grid">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </form>
        </section>

        <section class="card p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <p class="text-uppercase small fw-semibold text-primary mb-1" data-i18n="Management Table">Management Table</p>
                    <h4 class="mb-0" data-i18n="Complaint queue">Complaint queue</h4>
                </div>
                <div class="small muted" data-i18n="Update status inline or open the detail screen for the full record.">Update status inline or open the detail screen for the full record.</div>
            </div>

            <div class="table-responsive">
                <table class="table table-modern align-middle">
                    <thead>
                        <tr>
                            <th data-i18n="ID">ID</th>
                            <th data-i18n="User">User</th>
                            <th data-i18n="Crime">Crime</th>
                            <th data-i18n="Description">Description</th>
                            <?php if ($has_address) { ?><th data-i18n="Location">Location</th><?php } ?>
                            <th data-i18n="Status">Status</th>
                            <th data-i18n="Date">Date</th>
                            <th data-i18n="Evidence">Evidence</th>
                            <th data-i18n="Actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($complaints) > 0) { ?>
                        <?php foreach ($complaints as $row) { ?>
                            <tr>
                                <td class="fw-semibold">#<?php echo (int)$row["id"]; ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo h($row["user_name"]); ?></div>
                                    <div class="small muted"><?php echo h($row["user_email"]); ?></div>
                                </td>
                                <td><?php echo h($row["crime_type"]); ?></td>
                                <td class="small"><span data-translate-content data-original-text="<?php echo h($row["description"]); ?>"><?php echo h($row["description"]); ?></span></td>
                                <?php if ($has_address) { ?><td class="small muted"><?php echo h($row["address"]); ?></td><?php } ?>
                                <td><span class="status-pill <?php echo admin_status_class($row["status"]); ?>"><?php echo h($row["status"]); ?></span></td>
                                <td class="small muted"><?php echo h($row["created_at"]); ?></td>
                                <td>
                                    <?php if (!empty($row["evidence"])) { ?>
                                        <a href="../uploads/<?php echo urlencode($row["evidence"]); ?>" target="_blank" rel="noopener">
                                            <img src="../uploads/<?php echo urlencode($row["evidence"]); ?>" alt="Evidence preview" class="thumbnail-preview">
                                        </a>
                                    <?php } else { ?>
                                        <span class="small muted" data-i18n="No file">No file</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-2">
                                        <a class="btn btn-sm btn-outline-primary" href="complaint_details.php?id=<?php echo (int)$row["id"]; ?>" data-i18n="Details">Details</a>
                                        <form action="update_status.php" method="POST" class="d-flex gap-2 flex-wrap" data-loading-target="#row-loading-<?php echo (int)$row["id"]; ?>">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="complaint_id" value="<?php echo (int)$row["id"]; ?>">
                                            <select name="status" class="form-select form-select-sm" required style="min-width: 180px;">
                                                <option value="Pending" <?php echo ($row["status"] === "Pending") ? "selected" : ""; ?> data-i18n="Pending">Pending</option>
                                                <option value="Under Investigation" <?php echo ($row["status"] === "Under Investigation" || $row["status"] === "Investigating") ? "selected" : ""; ?> data-i18n="Under Investigation">Under Investigation</option>
                                                <option value="Resolved" <?php echo ($row["status"] === "Resolved") ? "selected" : ""; ?> data-i18n="Resolved">Resolved</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary" data-i18n="Update">Update</button>
                                        </form>
                                        <div class="loading-spinner" id="row-loading-<?php echo (int)$row["id"]; ?>" hidden data-i18n="Updating">Updating</div>
                                        <form action="delete_complaint.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this complaint?');">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="complaint_id" value="<?php echo (int)$row["id"]; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" data-i18n="Delete">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="<?php echo $has_address ? 9 : 8; ?>" class="empty-state">
                                <div class="mb-2"><i class="fa-regular fa-folder-open fa-2x"></i></div>
                                <div data-i18n="No complaints found.">No complaints found.</div>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<?php translation_render_page_config("../"); ?>
<script src="../assets/js/app.js"></script>
<script src="../assets/translation.js"></script>
</body>
</html>
