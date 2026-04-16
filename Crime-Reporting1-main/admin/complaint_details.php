<?php
include("auth.php");
include("../config/db.php");

$complaint_id = (int)($_GET["id"] ?? 0);
if ($complaint_id <= 0) {
    header("Location: complaints.php");
    exit();
}

$has_address = db_has_column($conn, "complaints", "address");
$address_select = $has_address ? "c.address" : "NULL AS address";

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        c.id, c.crime_type, c.description, c.latitude, c.longitude, {$address_select}, c.evidence, c.status, c.created_at,
        u.name, u.email, u.phone
     FROM complaints c
     JOIN users u ON c.user_id = u.id
     WHERE c.id=?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $complaint_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$complaint = $result ? mysqli_fetch_assoc($result) : null;

if (!$complaint) {
    header("Location: complaints.php");
    exit();
}

// Optional static assignment example based on city keywords.
$station = "Central Crime Desk";
$addr = strtolower($complaint["address"] ?? "");
if (strpos($addr, "north") !== false) {
    $station = "North Zone Police Station";
} elseif (strpos($addr, "south") !== false) {
    $station = "South Zone Police Station";
} elseif (strpos($addr, "east") !== false) {
    $station = "East Zone Police Station";
} elseif (strpos($addr, "west") !== false) {
    $station = "West Zone Police Station";
}
$translate_js_version = (string)filemtime(__DIR__ . "/../assets/js/google-translate-switcher.js");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complaint Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <style>
        #detailMap { height: 350px; border-radius: 12px; }
    </style>
</head>
<body>
<main class="container py-4 py-lg-5">
    <div class="page-topbar">
        <div class="page-breadcrumb">
            <a href="dashboard.php">Admin Dashboard</a>
            <i class="fa-solid fa-chevron-right small"></i>
            <a href="complaints.php">Manage Complaints</a>
            <i class="fa-solid fa-chevron-right small"></i>
            <span class="current">Complaint Details</span>
        </div>
    </div>
    <a href="complaints.php" class="btn btn-outline-primary mb-3"><i class="fa-solid fa-arrow-left me-2"></i>Back</a>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card p-4">
                    <h4 class="mb-3">Complaint #<?php echo (int)$complaint["id"]; ?></h4>
                    <p><strong>Crime Type:</strong> <?php echo htmlspecialchars($complaint["crime_type"], ENT_QUOTES, "UTF-8"); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($complaint["description"], ENT_QUOTES, "UTF-8"); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($complaint["status"], ENT_QUOTES, "UTF-8"); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($complaint["address"] ?: "Not available in current schema", ENT_QUOTES, "UTF-8"); ?></p>
                    <p><strong>Assigned Station:</strong> <?php echo htmlspecialchars($station, ENT_QUOTES, "UTF-8"); ?></p>
                    <p><strong>Created:</strong> <?php echo htmlspecialchars($complaint["created_at"], ENT_QUOTES, "UTF-8"); ?></p>
                    <form action="update_status.php" method="POST" class="row g-2 mb-3">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="complaint_id" value="<?php echo (int)$complaint["id"]; ?>">
                        <div class="col-md-8">
                            <select class="form-select" name="status">
                                <option value="Pending" <?php echo ($complaint["status"] === "Pending") ? "selected" : ""; ?>>Pending</option>
                                <option value="Under Investigation" <?php echo ($complaint["status"] === "Under Investigation" || $complaint["status"] === "Investigating") ? "selected" : ""; ?>>Under Investigation</option>
                                <option value="Resolved" <?php echo ($complaint["status"] === "Resolved") ? "selected" : ""; ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-grid">
                            <button class="btn btn-success" type="submit">Update Status</button>
                        </div>
                    </form>

                    <?php if (!empty($complaint["evidence"])) { ?>
                        <p><strong>Evidence:</strong></p>
                        <img src="../uploads/<?php echo urlencode($complaint["evidence"]); ?>" alt="Evidence"
                             class="img-fluid rounded border">
                    <?php } else { ?>
                        <p class="text-muted mb-0">No evidence image uploaded.</p>
                    <?php } ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card p-4 mb-3">
                    <h5>User Information</h5>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($complaint["name"], ENT_QUOTES, "UTF-8"); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($complaint["email"], ENT_QUOTES, "UTF-8"); ?></p>
                    <p class="mb-0"><strong>Phone:</strong> <?php echo htmlspecialchars($complaint["phone"], ENT_QUOTES, "UTF-8"); ?></p>
            </div>
            <div class="card p-4">
                    <h5>Incident Location</h5>
                    <div id="detailMap"></div>
                    <p class="small text-muted mt-2 mb-0">
                        Lat: <?php echo htmlspecialchars($complaint["latitude"], ENT_QUOTES, "UTF-8"); ?> |
                        Lng: <?php echo htmlspecialchars($complaint["longitude"], ENT_QUOTES, "UTF-8"); ?>
                    </p>
            </div>
            <div class="card p-4 mt-3">
                    <h5>Case Timeline</h5>
                    <?php
                    if (db_table_exists($conn, "case_updates")) {
                        $t_stmt = mysqli_prepare(
                            $conn,
                            "SELECT status, note, created_at
                             FROM case_updates
                             WHERE complaint_id=?
                             ORDER BY created_at DESC
                             LIMIT 10"
                        );
                        mysqli_stmt_bind_param($t_stmt, "i", $complaint_id);
                        mysqli_stmt_execute($t_stmt);
                        $t_result = mysqli_stmt_get_result($t_stmt);
                        if ($t_result && mysqli_num_rows($t_result) > 0) {
                            echo '<ul class="list-group list-group-flush">';
                            while ($t = mysqli_fetch_assoc($t_result)) {
                                echo '<li class="list-group-item px-0">';
                                echo '<div class="fw-semibold">' . h($t["status"]) . '</div>';
                                echo '<div class="small">' . h($t["note"]) . '</div>';
                                echo '<div class="small text-muted">' . h($t["created_at"]) . '</div>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p class="text-muted mb-0">No timeline updates available.</p>';
                        }
                    } else {
                        echo '<p class="text-muted mb-0">Timeline table not available yet.</p>';
                    }
                    ?>
            </div>
        </div>
    </div>
</main>
<?php
$footer_base_path = "..";
$footer_variant = "admin";
include("../includes/footer.php");
?>
<div id="google_translate_element" class="visually-hidden" aria-hidden="true"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/google-translate-switcher.js?v=<?php echo h($translate_js_version); ?>"></script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<script>
const lat = parseFloat("<?php echo (float)$complaint["latitude"]; ?>");
const lng = parseFloat("<?php echo (float)$complaint["longitude"]; ?>");
const map = L.map("detailMap").setView([lat, lng], 15);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
}).addTo(map);
L.marker([lat, lng]).addTo(map).bindPopup("Complaint #<?php echo (int)$complaint["id"]; ?>").openPopup();
</script>
</body>
</html>
