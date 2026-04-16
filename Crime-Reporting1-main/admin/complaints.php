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
$translate_js_version = (string)filemtime(__DIR__ . "/../assets/js/google-translate-switcher.js");
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
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="d-flex align-items-center gap-3 mb-4">
            <span class="navbar-brand-mark brand-logo-shell">
                <img class="brand-logo-img-contain" src="../assets/img/logo-new.png" alt="Crime Reporting System logo">
            </span>
            <div>
                <div class="fw-semibold">Admin Control</div>
                <div class="small text-white-50">Complaint management console</div>
            </div>
        </div>
        <nav class="nav flex-column mb-4">
            <a class="nav-link" href="dashboard.php"><i class="fa-solid fa-chart-line me-2"></i>Dashboard</a>
            <a class="nav-link active" href="complaints.php"><i class="fa-solid fa-table-list me-2"></i>Manage Complaints</a>
            <a class="nav-link" href="../public_stats.php"><i class="fa-solid fa-chart-pie me-2"></i>Public Statistics</a>
            <a class="nav-link" href="../index.php"><i class="fa-solid fa-house me-2"></i>Public Portal</a>
            <a class="nav-link" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a>
        </nav>
        <div class="surface-card p-3 text-dark">
            <div class="small text-uppercase fw-semibold text-primary mb-2">Filters</div>
            <div class="small muted">Search by type, status, date range, and location keywords to narrow the complaint queue.</div>
        </div>
    </aside>

    <main class="admin-main">
        <section class="surface-card p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <p class="text-uppercase small fw-semibold text-primary mb-2">Complaint Operations</p>
                    <h1 class="section-title mb-2">Review, filter, and update incoming complaints</h1>
                    <p class="section-copy mb-0">Use the filters to search the queue, inspect evidence, and update case status from the same management table.</p>
                </div>
                <div class="col-lg-4">
                    <div class="surface-card p-4">
                        <div class="small text-uppercase fw-semibold text-primary mb-2">Queue Size</div>
                        <h3 class="mb-1"><?php echo count($complaints); ?> Results</h3>
                        <div class="muted">Records matching the current filter set</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <p class="text-uppercase small fw-semibold text-primary mb-1">Search &amp; Filters</p>
                    <h4 class="mb-0">Refine the complaint table</h4>
                </div>
                <a href="complaints.php" class="btn btn-outline-primary"><i class="fa-solid fa-rotate-left me-2"></i>Reset Filters</a>
            </div>
            <form method="GET" class="row g-3">
                <div class="col-md-6 col-xl-2">
                    <label class="form-label">Crime Type</label>
                    <select name="crime_type" class="form-select">
                        <option value="">All</option>
                        <?php foreach ($crime_types as $type_option) { ?>
                            <option value="<?php echo h($type_option); ?>" <?php echo ($crime_type === $type_option) ? "selected" : ""; ?>>
                                <?php echo h($type_option); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="Pending" <?php echo ($status === "Pending") ? "selected" : ""; ?>>Pending</option>
                        <option value="Under Investigation" <?php echo ($status === "Under Investigation") ? "selected" : ""; ?>>Under Investigation</option>
                        <option value="Resolved" <?php echo ($status === "Resolved") ? "selected" : ""; ?>>Resolved</option>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo h($date_from); ?>">
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo h($date_to); ?>">
                </div>
                <div class="col-xl-3">
                    <label class="form-label">Location Keyword</label>
                    <input type="text" name="location" class="form-control" value="<?php echo h($location); ?>" placeholder="Area, address, or landmark" <?php echo $has_address ? "" : "disabled"; ?>>
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
                    <p class="text-uppercase small fw-semibold text-primary mb-1">Management Table</p>
                    <h4 class="mb-0">Complaint queue</h4>
                </div>
                <div class="small muted">Update status inline or open the detail screen for the full record.</div>
            </div>

            <div class="table-responsive">
                <table class="table table-modern align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Crime</th>
                            <th>Description</th>
                            <?php if ($has_address) { ?><th>Location</th><?php } ?>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Evidence</th>
                            <th>Actions</th>
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
                                <td class="small"><?php echo h($row["description"]); ?></td>
                                <?php if ($has_address) { ?><td class="small muted"><?php echo h($row["address"]); ?></td><?php } ?>
                                <td><span class="status-pill <?php echo admin_status_class($row["status"]); ?>"><?php echo h($row["status"]); ?></span></td>
                                <td class="small muted"><?php echo h($row["created_at"]); ?></td>
                                <td>
                                    <?php if (!empty($row["evidence"])) { ?>
                                        <a href="../uploads/<?php echo urlencode($row["evidence"]); ?>" target="_blank" rel="noopener">
                                            <img src="../uploads/<?php echo urlencode($row["evidence"]); ?>" alt="Evidence preview" class="thumbnail-preview">
                                        </a>
                                    <?php } else { ?>
                                        <span class="small muted">No file</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-2">
                                        <a class="btn btn-sm btn-outline-primary" href="complaint_details.php?id=<?php echo (int)$row["id"]; ?>">Details</a>
                                        <form action="update_status.php" method="POST" class="d-flex gap-2 flex-wrap" data-loading-target="#row-loading-<?php echo (int)$row["id"]; ?>">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="complaint_id" value="<?php echo (int)$row["id"]; ?>">
                                            <select name="status" class="form-select form-select-sm" required style="min-width: 180px;">
                                                <option value="Pending" <?php echo ($row["status"] === "Pending") ? "selected" : ""; ?>>Pending</option>
                                                <option value="Under Investigation" <?php echo ($row["status"] === "Under Investigation" || $row["status"] === "Investigating") ? "selected" : ""; ?>>Under Investigation</option>
                                                <option value="Resolved" <?php echo ($row["status"] === "Resolved") ? "selected" : ""; ?>>Resolved</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                        </form>
                                        <div class="loading-spinner" id="row-loading-<?php echo (int)$row["id"]; ?>" hidden>Updating</div>
                                        <form action="delete_complaint.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this complaint?');">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="complaint_id" value="<?php echo (int)$row["id"]; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="<?php echo $has_address ? 9 : 8; ?>" class="empty-state">
                                <div class="mb-2"><i class="fa-regular fa-folder-open fa-2x"></i></div>
                                <div>No complaints found.</div>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php
        $footer_base_path = "..";
        $footer_variant = "admin";
        include("../includes/footer.php");
        ?>
    </main>
</div>
<div id="google_translate_element" class="visually-hidden" aria-hidden="true"></div>

<script src="../assets/js/app.js"></script>
<script src="../assets/js/google-translate-switcher.js?v=<?php echo h($translate_js_version); ?>"></script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>
