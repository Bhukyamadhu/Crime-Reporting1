<?php
include("auth.php");
include("../config/db.php");

function fetch_one($conn, $sql) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
}

function fetch_all($conn, $sql) {
    $rows = [];
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($result && $r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
    }
    return $rows;
}

$counts = fetch_one(
    $conn,
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status IN ('Under Investigation', 'Investigating') THEN 1 ELSE 0 END) AS investigating,
        SUM(CASE WHEN status='Resolved' THEN 1 ELSE 0 END) AS resolved
     FROM complaints"
);
$counts = array_merge(["total" => 0, "pending" => 0, "investigating" => 0, "resolved" => 0], $counts ?: []);

$recent_rows = fetch_all(
    $conn,
    "SELECT c.id, c.crime_type, c.status, c.created_at, u.name
     FROM complaints c
     JOIN users u ON c.user_id = u.id
     ORDER BY c.created_at DESC
     LIMIT 8"
);

$has_address = db_has_column($conn, "complaints", "address");
$address_select = $has_address ? "address" : "NULL AS address";
$markers = fetch_all(
    $conn,
    "SELECT id, crime_type, latitude, longitude, {$address_select}, status
     FROM complaints
     WHERE latitude IS NOT NULL AND longitude IS NOT NULL"
);

$crime_dist = fetch_all(
    $conn,
    "SELECT crime_type, COUNT(*) AS total
     FROM complaints
     GROUP BY crime_type
     ORDER BY total DESC"
);

$monthly_rows = fetch_all(
    $conn,
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS total
     FROM complaints
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY month_key ASC"
);

$month_map = [];
foreach ($monthly_rows as $m) {
    $month_map[$m["month_key"]] = (int)$m["total"];
}
$month_labels = [];
$month_values = [];
for ($i = 11; $i >= 0; $i--) {
    $key = date("Y-m", strtotime("-{$i} month"));
    $month_labels[] = date("M Y", strtotime($key . "-01"));
    $month_values[] = $month_map[$key] ?? 0;
}

$hotspots = [];
foreach ($markers as $m) {
    $lat_key = number_format((float)$m["latitude"], 2, ".", "");
    $lng_key = number_format((float)$m["longitude"], 2, ".", "");
    $key = $lat_key . "," . $lng_key;
    if (!isset($hotspots[$key])) {
        $hotspots[$key] = ["lat" => (float)$lat_key, "lng" => (float)$lng_key, "count" => 0];
    }
    $hotspots[$key]["count"]++;
}
?>
<!DOCTYPE html>
<html lang="<?php echo h(translation_get_html_lang()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/translation.css">
    <style>
        #allComplaintsMap { height: 500px; }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="d-flex align-items-center gap-3 mb-4">
            <span class="navbar-brand-mark"><i class="fa-solid fa-shield-halved"></i></span>
            <div>
                <div class="fw-semibold" data-i18n="Admin Control">Admin Control</div>
                <div class="small text-white-50" data-i18n="Incident management system">Incident management system</div>
            </div>
        </div>
        <div class="surface-card p-3 mb-4 text-dark">
            <div class="small text-uppercase fw-semibold text-primary mb-2" data-i18n="Overview">Overview</div>
            <h4 class="mb-1"><?php echo (int)$counts["total"]; ?> <span data-i18n="Complaints">Complaints</span></h4>
            <div class="muted" data-i18n="Active monitoring across the platform">Active monitoring across the platform</div>
        </div>
        <nav class="nav flex-column mb-4">
            <a class="nav-link active" href="dashboard.php" data-i18n="Dashboard"><i class="fa-solid fa-chart-line me-2"></i>Dashboard</a>
            <a class="nav-link" href="complaints.php" data-i18n="Manage Complaints"><i class="fa-solid fa-table-list me-2"></i>Manage Complaints</a>
            <a class="nav-link" href="../public_stats.php" data-i18n="Public Statistics"><i class="fa-solid fa-chart-pie me-2"></i>Public Statistics</a>
            <a class="nav-link" href="../index.php" data-i18n="Public Portal"><i class="fa-solid fa-house me-2"></i>Public Portal</a>
            <a class="nav-link" href="logout.php" data-i18n="Logout"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a>
        </nav>
        <div class="surface-card p-3 text-dark">
            <div class="small text-uppercase fw-semibold text-primary mb-2" data-i18n="Response Mix">Response Mix</div>
            <div class="d-flex justify-content-between mb-2"><span data-i18n="Pending">Pending</span><strong><?php echo (int)$counts["pending"]; ?></strong></div>
            <div class="d-flex justify-content-between mb-2"><span data-i18n="Investigating">Investigating</span><strong><?php echo (int)$counts["investigating"]; ?></strong></div>
            <div class="d-flex justify-content-between"><span data-i18n="Resolved">Resolved</span><strong><?php echo (int)$counts["resolved"]; ?></strong></div>
        </div>
    </aside>

    <main class="admin-main">
        <div class="page-topbar">
            <div class="page-breadcrumb">
                <a href="../index.php" data-i18n="Home">Home</a>
                <i class="fa-solid fa-chevron-right small"></i>
                <span class="current" data-i18n="Admin Dashboard">Admin Dashboard</span>
            </div>
            <div class="page-toolbar">
                <div class="nav-shortcuts">
                    <a class="shortcut-pill" href="complaints.php" data-i18n="Manage Complaints"><i class="fa-solid fa-list-check"></i>Manage Complaints</a>
                    <a class="shortcut-pill" href="../public_stats.php" data-i18n="Statistics"><i class="fa-solid fa-chart-column"></i>Statistics</a>
                    <a class="shortcut-pill" href="logout.php" data-i18n="Logout"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
                </div>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <?php translation_render_language_selector("../"); ?>
                    <div class="small muted">Logged in as <?php echo h($_SESSION["admin_name"] ?? "Admin"); ?></div>
                </div>
            </div>
        </div>

        <section class="surface-card p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <p class="text-uppercase small fw-semibold text-primary mb-2" data-i18n="Administrator Workspace">Administrator Workspace</p>
                    <h1 class="section-title mb-2" data-i18n="Operational dashboard for complaint monitoring">Operational dashboard for complaint monitoring</h1>
                    <p class="section-copy mb-0" data-i18n="Review case volume, monitor investigation flow, inspect reporting hotspots, and use charts and maps to understand activity across the system.">Review case volume, monitor investigation flow, inspect reporting hotspots, and use charts and maps to understand activity across the system.</p>
                </div>
                <div class="col-lg-4">
                    <div class="d-grid gap-3">
                        <a class="btn btn-primary" href="complaints.php" data-i18n="Manage Complaints"><i class="fa-solid fa-list-check me-2"></i>Manage Complaints</a>
                        <a class="btn btn-outline-primary" href="../public_stats.php" data-i18n="View Public Dashboard"><i class="fa-solid fa-chart-column me-2"></i>View Public Dashboard</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="stats-grid mb-4">
            <div class="metric-card card-hover">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-label">Total Complaints</div>
                        <div class="metric-value"><?php echo (int)$counts["total"]; ?></div>
                    </div>
                    <span class="dashboard-icon feature-card-icon"><i class="fa-solid fa-folder-tree"></i></span>
                </div>
                <div class="metric-trend">All records submitted into the system</div>
            </div>
            <div class="metric-card card-hover">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-label">Pending Complaints</div>
                        <div class="metric-value"><?php echo (int)$counts["pending"]; ?></div>
                    </div>
                    <span class="dashboard-icon" style="background:rgba(245,158,11,.14);color:#9a6700;"><i class="fa-solid fa-clock"></i></span>
                </div>
                <div class="metric-trend">Waiting for triage or officer assignment</div>
            </div>
            <div class="metric-card card-hover">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-label">Under Investigation</div>
                        <div class="metric-value"><?php echo (int)$counts["investigating"]; ?></div>
                    </div>
                    <span class="dashboard-icon" style="background:rgba(26,115,232,.14);color:#0f5fcc;"><i class="fa-solid fa-user-secret"></i></span>
                </div>
                <div class="metric-trend">Cases with active operational work</div>
            </div>
            <div class="metric-card card-hover">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-label">Resolved Cases</div>
                        <div class="metric-value"><?php echo (int)$counts["resolved"]; ?></div>
                    </div>
                    <span class="dashboard-icon" style="background:rgba(22,163,74,.14);color:#15703a;"><i class="fa-solid fa-badge-check"></i></span>
                </div>
                <div class="metric-trend">Completed case workflows</div>
            </div>
        </section>

        <section class="row g-4 mb-4">
            <div class="col-xl-6">
                <div class="card chart-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="text-uppercase small fw-semibold text-primary mb-1">Distribution</p>
                            <h4 class="mb-0">Crime type mix</h4>
                        </div>
                        <span class="feature-card-icon"><i class="fa-solid fa-chart-pie"></i></span>
                    </div>
                    <canvas id="crimeTypeChart"></canvas>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card chart-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="text-uppercase small fw-semibold text-primary mb-1">Trend</p>
                            <h4 class="mb-0">Monthly complaint volume</h4>
                        </div>
                        <span class="feature-card-icon"><i class="fa-solid fa-chart-line"></i></span>
                    </div>
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
        </section>

        <section class="row g-4">
            <div class="col-xl-8">
                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="text-uppercase small fw-semibold text-primary mb-1">Geo Intelligence</p>
                            <h4 class="mb-0">Complaint map, clusters, and heat layer</h4>
                        </div>
                        <span class="feature-card-icon"><i class="fa-solid fa-map"></i></span>
                    </div>
                    <div class="map-shell">
                        <div class="map-frame">
                            <div id="allComplaintsMap"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="text-uppercase small fw-semibold text-primary mb-1">Hotspot Buckets</p>
                            <h4 class="mb-0">Repeated locations</h4>
                        </div>
                        <span class="feature-card-icon"><i class="fa-solid fa-fire"></i></span>
                    </div>
                    <?php if (count($hotspots) > 0) { ?>
                        <div class="vstack gap-3">
                            <?php foreach ($hotspots as $spot) { ?>
                                <div class="surface-card p-3">
                                    <div class="fw-semibold"><?php echo (int)$spot["count"]; ?> complaints</div>
                                    <div class="small muted">Near <?php echo h($spot["lat"] . ", " . $spot["lng"]); ?></div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="empty-state">
                            <div class="mb-2"><i class="fa-solid fa-location-crosshairs fa-2x"></i></div>
                            <div>No geo-tagged complaints yet.</div>
                        </div>
                    <?php } ?>
                </div>

                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="text-uppercase small fw-semibold text-primary mb-1">Recent Activity</p>
                            <h4 class="mb-0">Latest complaints</h4>
                        </div>
                        <span class="feature-card-icon"><i class="fa-solid fa-bell"></i></span>
                    </div>
                    <?php if (count($recent_rows) > 0) { ?>
                        <div class="vstack gap-3">
                            <?php foreach ($recent_rows as $r) { ?>
                                <div class="surface-card p-3">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <div class="fw-semibold">#<?php echo (int)$r["id"]; ?> <?php echo h($r["crime_type"]); ?></div>
                                            <div class="small muted"><?php echo h($r["name"]); ?> &middot; <?php echo h($r["created_at"]); ?></div>
                                        </div>
                                        <span class="status-pill <?php echo ($r["status"] === "Pending") ? "status-pending" : (($r["status"] === "Resolved") ? "status-resolved" : "status-investigating"); ?>"><?php echo h($r["status"]); ?></span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="empty-state">
                            <div class="mb-2"><i class="fa-regular fa-folder-open fa-2x"></i></div>
                            <div>No complaints found.</div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
<script src="../assets/js/app.js"></script>
<?php translation_render_page_config("../"); ?>
<script src="../assets/translation.js"></script>
<script>
const markers = <?php echo json_encode($markers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const crimeDist = <?php echo json_encode($crime_dist, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const monthLabels = <?php echo json_encode($month_labels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const monthValues = <?php echo json_encode($month_values, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

new Chart(document.getElementById("crimeTypeChart"), {
    type: "doughnut",
    data: {
        labels: crimeDist.map((x) => x.crime_type),
        datasets: [{
            data: crimeDist.map((x) => Number(x.total)),
            backgroundColor: ["#1a73e8", "#0f172a", "#4aa3ff", "#f59e0b", "#16a34a", "#94a3b8"]
        }]
    },
    options: {
        plugins: {
            legend: {
                position: "bottom"
            }
        }
    }
});

new Chart(document.getElementById("monthlyTrendChart"), {
    type: "line",
    data: {
        labels: monthLabels,
        datasets: [{
            label: "Complaints",
            data: monthValues,
            borderColor: "#1a73e8",
            backgroundColor: "rgba(26, 115, 232, 0.14)",
            fill: true,
            tension: 0.35,
            borderWidth: 3,
            pointBackgroundColor: "#1a73e8"
        }]
    },
    options: {
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 }
            }
        }
    }
});

const map = L.map("allComplaintsMap").setView([20.5937, 78.9629], 5);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
}).addTo(map);

const clusterGroup = L.markerClusterGroup();
const heatPoints = [];
const bounds = [];
markers.forEach((item) => {
    const lat = parseFloat(item.latitude);
    const lng = parseFloat(item.longitude);
    if (Number.isNaN(lat) || Number.isNaN(lng)) {
        return;
    }

    bounds.push([lat, lng]);
    heatPoints.push([lat, lng, 0.45]);

    const marker = L.marker([lat, lng]).bindPopup(
        `<strong>#${item.id} ${item.crime_type}</strong><br>Status: ${item.status}<br>${item.address || ""}`
    );
    clusterGroup.addLayer(marker);
});

map.addLayer(clusterGroup);
if (heatPoints.length > 0) {
    L.heatLayer(heatPoints, { radius: 25, blur: 18, maxZoom: 16 }).addTo(map);
}
if (bounds.length > 0) {
    map.fitBounds(bounds, { padding: [28, 28] });
}
</script>
</body>
</html>
