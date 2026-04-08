<?php
include("config/security.php");
include("config/db.php");

function fetch_all_rows($conn, $sql) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($result && $row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

$summary_stmt = mysqli_prepare(
    $conn,
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status='Resolved' THEN 1 ELSE 0 END) AS resolved,
        SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status IN ('Under Investigation', 'Investigating') THEN 1 ELSE 0 END) AS investigating
     FROM complaints"
);
mysqli_stmt_execute($summary_stmt);
$summary_result = mysqli_stmt_get_result($summary_stmt);
$summary = $summary_result ? mysqli_fetch_assoc($summary_result) : ["total" => 0, "resolved" => 0, "pending" => 0, "investigating" => 0];

$crime_dist = fetch_all_rows(
    $conn,
    "SELECT crime_type, COUNT(*) AS total
     FROM complaints
     GROUP BY crime_type
     ORDER BY total DESC"
);

$status_dist = fetch_all_rows(
    $conn,
    "SELECT status, COUNT(*) AS total
     FROM complaints
     GROUP BY status
     ORDER BY total DESC"
);

$monthly_rows = fetch_all_rows(
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Public Crime Statistics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg glass-nav">
    <div class="container py-2">
        <a class="navbar-brand d-flex align-items-center gap-3 fw-semibold" href="index.php">
            <span class="navbar-brand-mark"><i class="fa-solid fa-shield-halved"></i></span>
            <span class="navbar-brand-text">
                Crime Reporting System
                <small>Public statistics dashboard</small>
            </span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#statsNav" aria-controls="statsNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="statsNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 me-lg-3">
                <li class="nav-item"><a class="nav-link app-nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="user/report.php">Report Crime</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="user/dashboard.php">Track Complaint</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link active" href="public_stats.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="user/login.php">Login</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 py-lg-5">
    <section class="surface-card p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <p class="text-uppercase small fw-semibold text-primary mb-2">Open Data Snapshot</p>
                <h1 class="section-title mb-2">Public crime statistics and service trends</h1>
                <p class="section-copy mb-0">This dashboard presents complaint totals, status flow, crime type distribution, and monthly reporting trends in a modern public-facing analytics view.</p>
            </div>
            <div class="col-lg-4">
                <div class="d-grid gap-3">
                    <a href="user/report.php" class="btn btn-primary"><i class="fa-solid fa-file-circle-plus me-2"></i>Report Crime</a>
                    <a href="index.php" class="btn btn-outline-primary"><i class="fa-solid fa-arrow-left me-2"></i>Back to Home</a>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-grid mb-4">
        <div class="metric-card card-hover">
            <div class="metric-label">Total Complaints</div>
            <div class="metric-value"><?php echo (int)$summary["total"]; ?></div>
            <div class="metric-trend">All public complaints received</div>
        </div>
        <div class="metric-card card-hover">
            <div class="metric-label">Pending</div>
            <div class="metric-value"><?php echo (int)$summary["pending"]; ?></div>
            <div class="metric-trend">Awaiting initial action</div>
        </div>
        <div class="metric-card card-hover">
            <div class="metric-label">Under Investigation</div>
            <div class="metric-value"><?php echo (int)$summary["investigating"]; ?></div>
            <div class="metric-trend">Active operational review</div>
        </div>
        <div class="metric-card card-hover">
            <div class="metric-label">Resolved</div>
            <div class="metric-value"><?php echo (int)$summary["resolved"]; ?></div>
            <div class="metric-trend">Closed or completed complaints</div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-xl-6">
            <div class="card chart-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <p class="text-uppercase small fw-semibold text-primary mb-1">Chart 1</p>
                        <h4 class="mb-0">Crime types</h4>
                    </div>
                    <span class="feature-card-icon"><i class="fa-solid fa-chart-column"></i></span>
                </div>
                <canvas id="crimeTypeChart"></canvas>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card chart-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <p class="text-uppercase small fw-semibold text-primary mb-1">Chart 2</p>
                        <h4 class="mb-0">Monthly trends</h4>
                    </div>
                    <span class="feature-card-icon"><i class="fa-solid fa-chart-line"></i></span>
                </div>
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>
        <div class="col-xl-12">
            <div class="card chart-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <p class="text-uppercase small fw-semibold text-primary mb-1">Chart 3</p>
                        <h4 class="mb-0">Complaint status mix</h4>
                    </div>
                    <span class="feature-card-icon"><i class="fa-solid fa-chart-pie"></i></span>
                </div>
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
const publicCrimeData = <?php echo json_encode($crime_dist, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const monthlyLabels = <?php echo json_encode($month_labels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const monthlyValues = <?php echo json_encode($month_values, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const statusData = <?php echo json_encode($status_dist, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

new Chart(document.getElementById("crimeTypeChart"), {
    type: "bar",
    data: {
        labels: publicCrimeData.map((x) => x.crime_type),
        datasets: [{
            label: "Complaints",
            data: publicCrimeData.map((x) => Number(x.total)),
            backgroundColor: "#1a73e8",
            borderRadius: 12
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

new Chart(document.getElementById("monthlyTrendChart"), {
    type: "line",
    data: {
        labels: monthlyLabels,
        datasets: [{
            label: "Complaints",
            data: monthlyValues,
            borderColor: "#0f172a",
            backgroundColor: "rgba(26, 115, 232, 0.14)",
            fill: true,
            tension: 0.35,
            borderWidth: 3,
            pointBackgroundColor: "#1a73e8"
        }]
    },
    options: {
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

new Chart(document.getElementById("statusChart"), {
    type: "pie",
    data: {
        labels: statusData.map((x) => x.status),
        datasets: [{
            data: statusData.map((x) => Number(x.total)),
            backgroundColor: ["#f59e0b", "#1a73e8", "#16a34a", "#94a3b8"]
        }]
    },
    options: {
        plugins: {
            legend: { position: "bottom" }
        }
    }
});
</script>
</body>
</html>
