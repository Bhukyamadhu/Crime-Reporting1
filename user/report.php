<?php
include("../config/security.php");
include("../config/db.php");
security_require_user("login.php");

$message = "";
$message_type = "danger";
$has_address = db_has_column($conn, "complaints", "address");

$crime_type = trim($_POST["crime_type"] ?? "");
$description = trim($_POST["description"] ?? "");
$latitude = trim($_POST["latitude"] ?? "");
$longitude = trim($_POST["longitude"] ?? "");
$address = trim($_POST["address"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify_or_die();
    $user_id = (int)$_SESSION["user_id"];

    if ($crime_type === "" || $description === "" || $latitude === "" || $longitude === "" || ($has_address && $address === "")) {
        $message = "Please complete all required fields and select a location.";
    } elseif (!is_numeric($latitude) || !is_numeric($longitude)) {
        $message = "Invalid map coordinates.";
    } else {
        $uploaded_file = null;

        if (isset($_FILES["evidence"]) && $_FILES["evidence"]["error"] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES["evidence"]["error"] !== UPLOAD_ERR_OK) {
                $message = "File upload failed. Please try again.";
            } else {
                $allowed_ext = ["jpg", "jpeg", "png"];
                $allowed_mime = ["image/jpeg", "image/png"];

                $original_name = basename($_FILES["evidence"]["name"]);
                $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                $mime_type = mime_content_type($_FILES["evidence"]["tmp_name"]);
                $max_size = 5 * 1024 * 1024;

                if (!in_array($extension, $allowed_ext, true) || !in_array($mime_type, $allowed_mime, true)) {
                    $message = "Only image files (JPG, PNG) are allowed.";
                } elseif ($_FILES["evidence"]["size"] > $max_size) {
                    $message = "Image must be 5MB or smaller.";
                } else {
                    $uploaded_file = hash("sha256", microtime(true) . $original_name . random_bytes(16)) . "." . $extension;
                    $target_path = "../uploads/" . $uploaded_file;

                    if (!move_uploaded_file($_FILES["evidence"]["tmp_name"], $target_path)) {
                        $message = "Unable to save uploaded image.";
                    }
                }
            }
        }

        if ($message === "") {
            if ($has_address) {
                $insert_stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO complaints (user_id, crime_type, description, latitude, longitude, address, evidence, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')"
                );
                mysqli_stmt_bind_param(
                    $insert_stmt,
                    "issddss",
                    $user_id,
                    $crime_type,
                    $description,
                    $latitude,
                    $longitude,
                    $address,
                    $uploaded_file
                );
            } else {
                $insert_stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO complaints (user_id, crime_type, description, latitude, longitude, evidence, status)
                     VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
                );
                mysqli_stmt_bind_param(
                    $insert_stmt,
                    "issdds",
                    $user_id,
                    $crime_type,
                    $description,
                    $latitude,
                    $longitude,
                    $uploaded_file
                );
            }

            if (mysqli_stmt_execute($insert_stmt)) {
                set_flash_message("success", "Complaint submitted successfully. You can now track it from your dashboard.");
                header("Location: dashboard.php");
                exit();
            } else {
                $message = "Error submitting complaint. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report Crime</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../chatbot/chatbot.css">
    <style>
        #map { height: 420px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg glass-nav">
    <div class="container py-2">
        <a class="navbar-brand d-flex align-items-center gap-3 fw-semibold" href="../index.php">
            <span class="navbar-brand-mark"><i class="fa-solid fa-shield-halved"></i></span>
            <span class="navbar-brand-text">
                Crime Reporting System
                <small>Secure incident submission</small>
            </span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#reportNav" aria-controls="reportNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="reportNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 me-lg-3">
                <li class="nav-item"><a class="nav-link app-nav-link" href="../index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link active" href="report.php">Report Crime</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="dashboard.php#complaint-history">Track Complaint</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="../public_stats.php">Statistics</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="logout.php">Logout</a></li>
            </ul>
            <div class="d-grid d-lg-flex gap-2">
                <a href="dashboard.php" class="btn btn-outline-primary">My Dashboard</a>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4 py-lg-5">
    <div class="page-topbar">
        <div class="page-breadcrumb">
            <a href="../index.php">Home</a>
            <i class="fa-solid fa-chevron-right small"></i>
            <a href="dashboard.php">Dashboard</a>
            <i class="fa-solid fa-chevron-right small"></i>
            <span class="current">Report Crime</span>
        </div>
        <div class="page-toolbar">
            <div class="nav-shortcuts">
                <a class="shortcut-pill" href="dashboard.php"><i class="fa-solid fa-gauge"></i>Dashboard</a>
                <a class="shortcut-pill" href="dashboard.php#complaint-history"><i class="fa-solid fa-list-check"></i>Track Complaint</a>
                <a class="shortcut-pill" href="../public_stats.php"><i class="fa-solid fa-chart-column"></i>Statistics</a>
            </div>
        </div>
    </div>

    <section class="surface-card p-4 p-lg-5 mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-7">
                <p class="text-uppercase small fw-semibold text-primary mb-2">Submit Complaint</p>
                <h1 class="section-title mb-2">Report a crime with verified location details</h1>
                <p class="section-copy mb-0">Search the location first, refine the pin on the map if needed, upload evidence, and submit the complaint into the existing reporting workflow.</p>
            </div>
            <div class="col-lg-5">
                <div class="surface-card p-4">
                    <div class="d-flex align-items-start gap-3">
                        <span class="feature-card-icon"><i class="fa-solid fa-map-location-dot"></i></span>
                        <div>
                            <h5 class="mb-1">Smarter location selection</h5>
                            <p class="muted mb-2">Search a landmark or address, click anywhere on the map, or drag the marker to adjust the exact point.</p>
                            <div class="small muted">The address panel and coordinates update automatically.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if ($message !== "") { ?>
        <div class="alert alert-<?php echo h($message_type); ?> mb-4"><?php echo h($message); ?></div>
    <?php } ?>

    <form method="POST" enctype="multipart/form-data" data-loading-target="#reportLoading" class="needs-validation" novalidate>
        <?php echo csrf_input(); ?>
        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card form-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <p class="text-uppercase small fw-semibold text-primary mb-1">Incident Details</p>
                            <h4 class="mb-0">Complaint information</h4>
                        </div>
                        <span class="feature-card-icon"><i class="fa-solid fa-file-shield"></i></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fa-solid fa-list me-2 text-primary"></i>Crime Type</label>
                        <select name="crime_type" class="form-select" required>
                            <option value="">Select crime type</option>
                            <?php
                            $crime_options = ["Theft", "Assault", "Accident", "Cyber Crime", "Vandalism", "Other"];
                            foreach ($crime_options as $option) {
                                $selected = ($crime_type === $option) ? "selected" : "";
                                echo '<option value="' . h($option) . '" ' . $selected . '>' . h($option) . '</option>';
                            }
                            ?>
                        </select>
                        <div class="invalid-feedback">Please select the type of incident.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fa-solid fa-align-left me-2 text-primary"></i>Description</label>
                        <textarea name="description" class="form-control" rows="6" required><?php echo h($description); ?></textarea>
                        <div class="invalid-feedback">Please describe what happened.</div>
                    </div>

                    <div class="upload-dropzone mb-3">
                        <label class="form-label"><i class="fa-solid fa-camera me-2 text-primary"></i>Upload Evidence</label>
                        <input type="file" name="evidence" class="form-control" accept=".jpg,.jpeg,.png">
                        <div class="form-text mt-2">Allowed formats: JPG, JPEG, PNG. Maximum size: 5MB.</div>
                    </div>

                    <div class="loading-spinner" id="reportLoading" hidden>Submitting complaint</div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card form-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <p class="text-uppercase small fw-semibold text-primary mb-1">Location Intelligence</p>
                            <h4 class="mb-0">Search and mark the incident location</h4>
                        </div>
                        <span class="feature-card-icon"><i class="fa-solid fa-location-crosshairs"></i></span>
                    </div>

                    <div class="map-shell mb-3">
                        <div class="map-search-bar">
                            <input type="text" id="locationSearch" class="form-control map-search-input" placeholder="Search location..." aria-label="Search location">
                            <button type="button" id="searchLocationBtn" class="btn btn-outline-primary"><i class="fa-solid fa-magnifying-glass me-2"></i>Search</button>
                        </div>
                        <div class="search-status mb-3" id="mapSearchLoading" hidden>Searching map location</div>
                        <div id="mapSearchFeedback" class="small muted mb-3">Search a landmark, police station, area, or full address to position the map automatically.</div>
                        <div class="map-frame">
                            <div id="map"></div>
                        </div>
                    </div>

                    <input type="hidden" name="latitude" id="latitude" value="<?php echo h($latitude); ?>" required>
                    <input type="hidden" name="longitude" id="longitude" value="<?php echo h($longitude); ?>" required>
                    <input type="hidden" name="address" id="address" value="<?php echo h($address); ?>" <?php echo $has_address ? "required" : ""; ?>>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="surface-card p-3 h-100">
                                <div class="small text-uppercase fw-semibold text-primary mb-2">Latitude</div>
                                <div id="displayLat" class="fw-semibold"><?php echo $latitude !== "" ? h($latitude) : "Not selected"; ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="surface-card p-3 h-100">
                                <div class="small text-uppercase fw-semibold text-primary mb-2">Longitude</div>
                                <div id="displayLng" class="fw-semibold"><?php echo $longitude !== "" ? h($longitude) : "Not selected"; ?></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="surface-card p-3">
                                <div class="small text-uppercase fw-semibold text-primary mb-2">Resolved Address</div>
                                <div id="displayAddress" class="muted"><?php echo $address !== "" ? h($address) : "Not selected"; ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid d-md-flex gap-3 mt-4">
                        <button type="submit" class="btn btn-primary flex-fill"><i class="fa-solid fa-paper-plane me-2"></i>Submit Complaint</button>
                        <a href="dashboard.php" class="btn btn-outline-primary flex-fill"><i class="fa-solid fa-arrow-left me-2"></i>Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</main>

<div id="crimeChatbot" class="chatbot-widget" data-api-url="../chatbot/chatbot_api.php">
    <div class="chatbot-window">
        <div class="chatbot-header">
            <span class="chatbot-title">AI Safety Assistant</span>
            <button type="button" class="chatbot-close" aria-label="Close chatbot">&times;</button>
        </div>
        <div class="chatbot-messages"></div>
        <div class="chatbot-input-wrap">
            <input type="text" class="chatbot-input" placeholder="Ask about evidence or map selection" aria-label="Chat input">
            <button type="button" class="chatbot-send">Send</button>
        </div>
    </div>
    <button type="button" class="chatbot-toggle" aria-label="Open chatbot"><i class="fa-solid fa-comments"></i></button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../chatbot/chatbot.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const defaultLat = 17.385;
    const defaultLng = 78.4867;
    const latInput = document.getElementById("latitude");
    const lngInput = document.getElementById("longitude");
    const addressInput = document.getElementById("address");
    const latDisplay = document.getElementById("displayLat");
    const lngDisplay = document.getElementById("displayLng");
    const addressDisplay = document.getElementById("displayAddress");
    const searchInput = document.getElementById("locationSearch");
    const searchBtn = document.getElementById("searchLocationBtn");
    const searchLoading = document.getElementById("mapSearchLoading");
    const searchFeedback = document.getElementById("mapSearchFeedback");
    const existingLat = parseFloat(latInput.value);
    const existingLng = parseFloat(lngInput.value);
    const mapCenter = (!Number.isNaN(existingLat) && !Number.isNaN(existingLng)) ? [existingLat, existingLng] : [defaultLat, defaultLng];
    const map = L.map("map").setView(mapCenter, (!Number.isNaN(existingLat) && !Number.isNaN(existingLng)) ? 15 : 13);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors"
    }).addTo(map);

    let marker;

    function setSearchState(isLoading, message) {
        searchLoading.hidden = !isLoading;
        searchBtn.disabled = isLoading;
        if (message) {
            searchFeedback.textContent = message;
        }
    }

    function updateCoordinates(lat, lng) {
        latInput.value = lat;
        lngInput.value = lng;
        latDisplay.textContent = Number(lat).toFixed(6);
        lngDisplay.textContent = Number(lng).toFixed(6);
    }

    function updateAddress(lat, lng) {
        setSearchState(true, "Resolving selected address...");
        return fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then((response) => response.json())
            .then((data) => {
                const displayAddress = data && data.display_name ? data.display_name : "Address not found";
                addressDisplay.textContent = displayAddress;
                addressInput.value = displayAddress;
                setSearchState(false, "Address updated from the selected map position.");
            })
            .catch(() => {
                addressDisplay.textContent = "Unable to fetch address";
                addressInput.value = "Unable to fetch address";
                setSearchState(false, "Address lookup failed. You can still submit the selected coordinates.");
            });
    }

    function setMarker(lat, lng, shouldFetchAddress = true) {
        if (!marker) {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on("dragend", function () {
                const pos = marker.getLatLng();
                updateCoordinates(pos.lat, pos.lng);
                updateAddress(pos.lat, pos.lng);
            });
        } else {
            marker.setLatLng([lat, lng]);
        }

        updateCoordinates(lat, lng);
        if (shouldFetchAddress) {
            updateAddress(lat, lng);
        }
    }

    function searchLocation() {
        const query = searchInput.value.trim();
        if (!query) {
            setSearchState(false, "Enter a location name or address to search.");
            searchInput.focus();
            return;
        }

        setSearchState(true, "Searching OpenStreetMap for the entered location...");
        fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`)
            .then((response) => response.json())
            .then((results) => {
                if (!results || results.length === 0) {
                    setSearchState(false, "No location found. Try a more specific landmark or area.");
                    return;
                }

                const result = results[0];
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                map.setView([lat, lng], 16);
                setMarker(lat, lng, false);
                addressDisplay.textContent = result.display_name || "Address found";
                addressInput.value = result.display_name || "";
                setSearchState(false, "Location found. You can drag the marker to refine the exact point.");
            })
            .catch(() => {
                setSearchState(false, "Search failed. Check your connection and try again.");
            });
    }

    searchBtn.addEventListener("click", searchLocation);
    searchInput.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            searchLocation();
        }
    });

    if (!Number.isNaN(existingLat) && !Number.isNaN(existingLng)) {
        setMarker(existingLat, existingLng, addressInput.value === "");
    } else if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;
            map.setView([userLat, userLng], 15);
            setMarker(userLat, userLng);
            setSearchState(false, "Using your current location as a starting point. You can still search or move the pin.");
        });
    }

    map.on("click", function (e) {
        setMarker(e.latlng.lat, e.latlng.lng);
        setSearchState(false, "Map point selected. Drag the marker if you need to fine-tune the location.");
    });

    Array.from(document.querySelectorAll(".needs-validation")).forEach(function (form) {
        form.addEventListener("submit", function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add("was-validated");
        });
    });
});
</script>
</body>
</html>
