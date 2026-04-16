<?php
include("../config/security.php");
include("../config/db.php");
security_require_user("login.php");

$user_id = (int)$_SESSION["user_id"];
$message = "";
$message_type = "danger";

$has_address = db_has_column($conn, "complaints", "address");
$has_full_name = db_has_column($conn, "complaints", "full_name");
$has_age = db_has_column($conn, "complaints", "age");
$has_nationality = db_has_column($conn, "complaints", "nationality");
$has_phone_number = db_has_column($conn, "complaints", "phone_number");
$has_reporter_address = db_has_column($conn, "complaints", "reporter_address");

$user_stmt = mysqli_prepare($conn, "SELECT name, phone FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = $user_result ? mysqli_fetch_assoc($user_result) : ["name" => "", "phone" => ""];

$edit_complaint_id = (int)($_GET["complaint_id"] ?? 0);
$is_edit_mode = false;
$existing_complaint = null;

if ($edit_complaint_id > 0) {
    $select = "id, crime_type, description, latitude, longitude, status, evidence";
    if ($has_address) $select .= ", address";
    if ($has_full_name) $select .= ", full_name";
    if ($has_age) $select .= ", age";
    if ($has_nationality) $select .= ", nationality";
    if ($has_phone_number) $select .= ", phone_number";
    if ($has_reporter_address) $select .= ", reporter_address";
    $stmt = mysqli_prepare($conn, "SELECT {$select} FROM complaints WHERE id=? AND user_id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $edit_complaint_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existing_complaint = $result ? mysqli_fetch_assoc($result) : null;
    if (!$existing_complaint) {
        set_flash_message("warning", "The requested complaint could not be found for your account.");
        header("Location: dashboard.php");
        exit();
    }
    $is_edit_mode = true;
}

$form = [
    "full_name" => $existing_complaint["full_name"] ?? $user["name"] ?? "",
    "age" => $existing_complaint["age"] ?? "",
    "nationality" => $existing_complaint["nationality"] ?? "Indian",
    "phone_number" => $existing_complaint["phone_number"] ?? $user["phone"] ?? "",
    "reporter_address" => $existing_complaint["reporter_address"] ?? "",
    "crime_type" => $existing_complaint["crime_type"] ?? "",
    "description" => $existing_complaint["description"] ?? "",
    "latitude" => $existing_complaint["latitude"] ?? "",
    "longitude" => $existing_complaint["longitude"] ?? "",
    "address" => $existing_complaint["address"] ?? ""
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify_or_die();
    foreach ($form as $key => $value) {
        $form[$key] = trim($_POST[$key] ?? "");
    }

    if (in_array("", [$form["full_name"], $form["age"], $form["nationality"], $form["phone_number"], $form["reporter_address"], $form["crime_type"], $form["description"], $form["latitude"], $form["longitude"]], true) || ($has_address && $form["address"] === "")) {
        $message = "Please complete all required fields before submitting the complaint.";
    } elseif (!preg_match("/^[A-Za-z][A-Za-z\\s.'-]*$/", $form["full_name"])) {
        $message = "Full name must contain letters only and cannot include numbers.";
    } elseif (!ctype_digit($form["age"]) || (int)$form["age"] < 18) {
        $message = "Age must be a valid number and at least 18.";
    } elseif (strcasecmp($form["nationality"], "Indian") !== 0) {
        $message = "Only Indian citizens can submit complaints in this system.";
    } elseif (!preg_match("/^\\d{10}$/", $form["phone_number"])) {
        $message = "Phone number must contain exactly 10 digits.";
    } elseif (!is_numeric($form["latitude"]) || !is_numeric($form["longitude"])) {
        $message = "Invalid map coordinates.";
    } else {
        $uploaded_file = $existing_complaint["evidence"] ?? null;
        if (isset($_FILES["evidence"]) && $_FILES["evidence"]["error"] !== UPLOAD_ERR_NO_FILE) {
            $ext = strtolower(pathinfo(basename($_FILES["evidence"]["name"]), PATHINFO_EXTENSION));
            $mime = mime_content_type($_FILES["evidence"]["tmp_name"]);
            if ($_FILES["evidence"]["error"] !== UPLOAD_ERR_OK) {
                $message = "File upload failed. Please try again.";
            } elseif (!in_array($ext, ["jpg", "jpeg", "png"], true) || !in_array($mime, ["image/jpeg", "image/png"], true)) {
                $message = "Only image files (JPG, PNG) are allowed.";
            } elseif ($_FILES["evidence"]["size"] > (5 * 1024 * 1024)) {
                $message = "Image must be 5MB or smaller.";
            } else {
                $uploaded_file = hash("sha256", microtime(true) . $_FILES["evidence"]["name"] . random_bytes(16)) . "." . $ext;
                if (!move_uploaded_file($_FILES["evidence"]["tmp_name"], "../uploads/" . $uploaded_file)) {
                    $message = "Unable to save uploaded image.";
                }
            }
        }

        if ($message === "") {
            if ($is_edit_mode) {
                $sql = "UPDATE complaints SET crime_type=?, description=?, latitude=?, longitude=?, evidence=?, status='Pending'";
                $types = "ssdds";
                $params = [$form["crime_type"], $form["description"], $form["latitude"], $form["longitude"], $uploaded_file];
                if ($has_address) { $sql .= ", address=?"; $types .= "s"; $params[] = $form["address"]; }
                if ($has_full_name) { $sql .= ", full_name=?"; $types .= "s"; $params[] = $form["full_name"]; }
                if ($has_age) { $sql .= ", age=?"; $types .= "i"; $params[] = (int)$form["age"]; }
                if ($has_nationality) { $sql .= ", nationality=?"; $types .= "s"; $params[] = "Indian"; }
                if ($has_phone_number) { $sql .= ", phone_number=?"; $types .= "s"; $params[] = $form["phone_number"]; }
                if ($has_reporter_address) { $sql .= ", reporter_address=?"; $types .= "s"; $params[] = $form["reporter_address"]; }
                $sql .= " WHERE id=? AND user_id=?";
                $types .= "ii";
                $params[] = $edit_complaint_id;
                $params[] = $user_id;
            } else {
                $cols = ["user_id", "crime_type", "description", "latitude", "longitude", "evidence", "status"];
                $vals = ["?", "?", "?", "?", "?", "?", "'Pending'"];
                $types = "issdds";
                $params = [$user_id, $form["crime_type"], $form["description"], $form["latitude"], $form["longitude"], $uploaded_file];
                if ($has_address) { $cols[] = "address"; $vals[] = "?"; $types .= "s"; $params[] = $form["address"]; }
                if ($has_full_name) { $cols[] = "full_name"; $vals[] = "?"; $types .= "s"; $params[] = $form["full_name"]; }
                if ($has_age) { $cols[] = "age"; $vals[] = "?"; $types .= "i"; $params[] = (int)$form["age"]; }
                if ($has_nationality) { $cols[] = "nationality"; $vals[] = "?"; $types .= "s"; $params[] = "Indian"; }
                if ($has_phone_number) { $cols[] = "phone_number"; $vals[] = "?"; $types .= "s"; $params[] = $form["phone_number"]; }
                if ($has_reporter_address) { $cols[] = "reporter_address"; $vals[] = "?"; $types .= "s"; $params[] = $form["reporter_address"]; }
                $sql = "INSERT INTO complaints (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $vals) . ")";
            }

            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (mysqli_stmt_execute($stmt)) {
                $saved_id = $is_edit_mode ? $edit_complaint_id : (int)mysqli_insert_id($conn);
                if (db_table_exists($conn, "notifications")) {
                    $note = $is_edit_mode
                        ? "Complaint #{$saved_id} was updated and resubmitted by the user."
                        : "Complaint #{$saved_id} has been submitted successfully.";
                    $notif = mysqli_prepare($conn, "INSERT INTO notifications (user_id, complaint_id, message, is_read) VALUES (?, ?, ?, 0)");
                    mysqli_stmt_bind_param($notif, "iis", $user_id, $saved_id, $note);
                    mysqli_stmt_execute($notif);
                }
                set_flash_message("success", $is_edit_mode ? "Complaint updated and resubmitted successfully." : "Complaint submitted successfully. You can now track it from your dashboard.");
                header("Location: dashboard.php");
                exit();
            }
            $message = "Error saving complaint. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $is_edit_mode ? "Update Complaint" : "Report Crime"; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <link rel="stylesheet" href="../chatbot/chatbot.css">
    <style>
        #map { height: 420px; }
        .step { display: none; }
        .step.active { display: block; }
        .step-shell { border: 1px solid rgba(219, 228, 240, 0.95); border-radius: 22px; background: #fff; padding: 1.5rem; }
        .step-actions { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-top: 1.5rem; position: relative; z-index: 1092; }
        .step-actions .btn { position: relative; z-index: 1093; }
        body.report-page .chatbot-widget:not(.open) { bottom: 112px; z-index: 1040; }
        @media (max-width: 767.98px) {
            .step-actions { flex-direction: column; }
            .step-actions .btn { width: 100%; }
            body.report-page .chatbot-widget:not(.open) { bottom: 96px; z-index: 1040; }
        }
    </style>
</head>
<body class="report-page">
<nav class="navbar navbar-expand-lg glass-nav"><div class="container py-2"><a class="navbar-brand d-flex align-items-center gap-3 fw-semibold" href="../index.php"><span class="navbar-brand-mark"><i class="fa-solid fa-shield-halved"></i></span><span class="navbar-brand-text">Crime Reporting System<small><?php echo $is_edit_mode ? "Update complaint details" : "Secure incident submission"; ?></small></span></a><button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#reportNav"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="reportNav"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 me-lg-3"><li class="nav-item"><a class="nav-link app-nav-link" href="../index.php">Home</a></li><li class="nav-item"><a class="nav-link app-nav-link active" href="report.php">Report Crime</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="dashboard.php">Dashboard</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="dashboard.php#complaint-history">Track Complaint</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="../public_stats.php">Statistics</a></li><li class="nav-item"><a class="nav-link app-nav-link" href="logout.php">Logout</a></li></ul></div></div></nav>
<main class="container py-4 py-lg-5">
    <div class="page-breadcrumb mb-3"><a href="../index.php">Home</a><i class="fa-solid fa-chevron-right small"></i><a href="dashboard.php">Dashboard</a><i class="fa-solid fa-chevron-right small"></i><span class="current"><?php echo $is_edit_mode ? "Update Complaint" : "Report Crime"; ?></span></div>
    <?php if ($message !== "") { ?><div class="alert alert-<?php echo h($message_type); ?> mb-4"><?php echo h($message); ?></div><?php } ?>

    <form method="POST" enctype="multipart/form-data" data-loading-target="#reportLoading" class="needs-validation" id="complaintWizardForm" data-has-address="<?php echo $has_address ? "1" : "0"; ?>" data-existing-evidence="<?php echo h(!empty($existing_complaint["evidence"]) ? $existing_complaint["evidence"] : ""); ?>" novalidate>
        <?php echo csrf_input(); ?>
        <section class="card p-4 p-lg-5 mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <p class="text-uppercase small fw-semibold text-primary mb-1"><?php echo $is_edit_mode ? "Complaint Re-submission" : "New Complaint Form"; ?></p>
                    <h2 class="mb-2"><?php echo $is_edit_mode ? "Update and resubmit your complaint" : "Report an incident in four clear steps"; ?></h2>
                    <p class="muted mb-0">Only one step is visible at a time. Use Next and Previous to move through the form.</p>
                </div>
                <span class="wizard-badge"><?php echo $is_edit_mode ? "Edit Mode" : "Secure Form"; ?></span>
            </div>

            <div class="progress mb-4">
                <div id="progressBar" class="progress-bar" role="progressbar" style="width: 25%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="25"></div>
            </div>

            <div class="step active" id="step1">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="step-shell h-100">
                            <h4 class="mb-3">Step 1: Personal Details</h4>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" class="form-control" value="<?php echo h($form["full_name"]); ?>" required>
                                    <div class="invalid-feedback">Enter a valid full name without numbers.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Age</label>
                                    <input type="number" name="age" class="form-control" value="<?php echo h($form["age"]); ?>" min="18" required>
                                    <div class="invalid-feedback">Age must be 18 or above.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nationality</label>
                                    <input type="text" name="nationality" class="form-control" value="Indian" readonly required>
                                    <div class="invalid-feedback">Nationality must be Indian.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" name="phone_number" class="form-control" value="<?php echo h($form["phone_number"]); ?>" maxlength="10" required>
                                    <div class="invalid-feedback">Enter a 10-digit phone number.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address</label>
                                    <textarea name="reporter_address" class="form-control" rows="4" required><?php echo h($form["reporter_address"]); ?></textarea>
                                    <div class="invalid-feedback">Enter your residential address.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="wizard-side-panel h-100">
                            <div class="wizard-side-icon"><i class="fa-solid fa-id-card"></i></div>
                            <h5 class="mb-3">Rules</h5>
                            <ul class="wizard-hint-list">
                                <li>Name must contain letters only.</li>
                                <li>Age must be at least 18 years.</li>
                                <li>Nationality must remain Indian.</li>
                                <li>Phone number must contain exactly 10 digits.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="step-actions">
                    <a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
                    <button type="button" id="nextBtn" class="btn btn-primary nextBtn">Next</button>
                </div>
            </div>

            <div class="step" id="step2">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="step-shell h-100">
                            <h4 class="mb-3">Step 2: Crime Details</h4>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Crime Type</label>
                                    <select name="crime_type" class="form-select" required>
                                        <option value="">Select crime type</option>
                                        <?php foreach (["Theft","Assault","Accident","Cyber Crime","Vandalism","Other"] as $option) { ?>
                                            <option value="<?php echo h($option); ?>" <?php echo ($form["crime_type"] === $option) ? "selected" : ""; ?>><?php echo h($option); ?></option>
                                        <?php } ?>
                                    </select>
                                    <div class="invalid-feedback">Select the type of incident.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Evidence Upload</label>
                                    <input type="file" name="evidence" id="evidenceInput" class="form-control" accept=".jpg,.jpeg,.png">
                                    <div class="form-text">Upload JPG or PNG evidence up to 5MB.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="8" required><?php echo h($form["description"]); ?></textarea>
                                    <div class="invalid-feedback">Describe the incident clearly.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="wizard-side-panel h-100">
                            <div class="wizard-side-icon"><i class="fa-solid fa-file-shield"></i></div>
                            <h5 class="mb-3">Evidence guidance</h5>
                            <ul class="wizard-hint-list">
                                <li>Pick the closest matching crime type.</li>
                                <li>Explain what happened as clearly as possible.</li>
                                <li>Upload evidence only when it is relevant and safe.</li>
                            </ul>
                            <div class="surface-card p-3 mt-4">
                                <div class="small text-uppercase fw-semibold text-primary mb-2">Current Evidence</div>
                                <div id="evidenceFileName" class="small muted"><?php echo !empty($existing_complaint["evidence"]) ? h($existing_complaint["evidence"]) : "No new file selected"; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn btn-outline-primary prevBtn">Previous</button>
                    <button type="button" class="btn btn-primary nextBtn">Next</button>
                </div>
            </div>

            <div class="step" id="step3">
                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="step-shell h-100">
                            <h4 class="mb-3">Step 3: Location</h4>
                            <div class="map-shell mb-3">
                                <div class="map-search-bar">
                                    <input type="text" id="locationSearch" class="form-control map-search-input" placeholder="Search location...">
                                    <button type="button" id="searchLocationBtn" class="btn btn-outline-primary"><i class="fa-solid fa-magnifying-glass me-2"></i>Search</button>
                                </div>
                                <div class="search-status mb-3" id="mapSearchLoading" hidden>Searching map location</div>
                                <div id="mapSearchFeedback" class="small muted mb-3">Search a police station, area, or landmark to move the map instantly.</div>
                                <div class="map-frame"><div id="map"></div></div>
                            </div>
                            <input type="hidden" name="latitude" id="latitude" value="<?php echo h($form["latitude"]); ?>">
                            <input type="hidden" name="longitude" id="longitude" value="<?php echo h($form["longitude"]); ?>">
                            <input type="hidden" name="address" id="address" value="<?php echo h($form["address"]); ?>">
                            <div id="locationValidation" class="invalid-feedback d-block" hidden>Select the incident location on the map before continuing.</div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="wizard-side-panel h-100">
                            <div class="wizard-side-icon"><i class="fa-solid fa-location-dot"></i></div>
                            <h5 class="mb-3">Selected location</h5>
                            <div class="row g-3">
                                <div class="col-12"><div class="surface-card p-3 h-100"><div class="small text-uppercase fw-semibold text-primary mb-2">Latitude</div><div id="displayLat" class="fw-semibold"><?php echo $form["latitude"] !== "" ? h($form["latitude"]) : "Not selected"; ?></div></div></div>
                                <div class="col-12"><div class="surface-card p-3 h-100"><div class="small text-uppercase fw-semibold text-primary mb-2">Longitude</div><div id="displayLng" class="fw-semibold"><?php echo $form["longitude"] !== "" ? h($form["longitude"]) : "Not selected"; ?></div></div></div>
                                <div class="col-12"><div class="surface-card p-3"><div class="small text-uppercase fw-semibold text-primary mb-2">Incident Address</div><div id="displayAddress" class="muted"><?php echo $form["address"] !== "" ? h($form["address"]) : "Not selected"; ?></div></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn btn-outline-primary prevBtn">Previous</button>
                    <button type="button" class="btn btn-primary nextBtn">Next</button>
                </div>
            </div>

            <div class="step" id="step4">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="step-shell h-100">
                            <h4 class="mb-3">Step 4: Review &amp; Submit</h4>
                            <div class="review-grid">
                                <div class="review-card"><div class="review-label">Full Name</div><div id="reviewFullName" class="review-value"><?php echo h($form["full_name"]); ?></div></div>
                                <div class="review-card"><div class="review-label">Age</div><div id="reviewAge" class="review-value"><?php echo h($form["age"]); ?></div></div>
                                <div class="review-card"><div class="review-label">Nationality</div><div id="reviewNationality" class="review-value"><?php echo h($form["nationality"]); ?></div></div>
                                <div class="review-card"><div class="review-label">Phone Number</div><div id="reviewPhone" class="review-value"><?php echo h($form["phone_number"]); ?></div></div>
                                <div class="review-card review-card-wide"><div class="review-label">Residential Address</div><div id="reviewReporterAddress" class="review-value"><?php echo h($form["reporter_address"]); ?></div></div>
                                <div class="review-card"><div class="review-label">Crime Type</div><div id="reviewCrimeType" class="review-value"><?php echo h($form["crime_type"]); ?></div></div>
                                <div class="review-card"><div class="review-label">Evidence</div><div id="reviewEvidence" class="review-value"><?php echo !empty($existing_complaint["evidence"]) ? h($existing_complaint["evidence"]) : "No file selected"; ?></div></div>
                                <div class="review-card review-card-wide"><div class="review-label">Description</div><div id="reviewDescription" class="review-value"><?php echo h($form["description"]); ?></div></div>
                                <div class="review-card"><div class="review-label">Latitude</div><div id="reviewLatitude" class="review-value"><?php echo h($form["latitude"]); ?></div></div>
                                <div class="review-card"><div class="review-label">Longitude</div><div id="reviewLongitude" class="review-value"><?php echo h($form["longitude"]); ?></div></div>
                                <div class="review-card review-card-wide"><div class="review-label">Incident Address</div><div id="reviewIncidentAddress" class="review-value"><?php echo h($form["address"]); ?></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="wizard-side-panel h-100">
                            <div class="wizard-side-icon"><i class="fa-solid fa-circle-check"></i></div>
                            <h5 class="mb-3">Final confirmation</h5>
                            <ul class="wizard-hint-list">
                                <li>Review every detail before submitting.</li>
                                <li>Your complaint will be saved with status set to pending.</li>
                                <li>If this is a re-submission, the complaint will return to the admin queue.</li>
                            </ul>
                            <div class="loading-spinner mt-4" id="reportLoading" hidden><?php echo $is_edit_mode ? "Updating complaint" : "Submitting complaint"; ?></div>
                        </div>
                    </div>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn btn-outline-primary prevBtn">Previous</button>
                    <button type="submit" class="btn btn-primary" id="finalSubmitBtn"><?php echo $is_edit_mode ? "Update Complaint" : "Submit Complaint"; ?></button>
                </div>
            </div>
        </section>
    </form>
</main>
<div id="crimeChatbot" class="chatbot-widget" data-api-url="../chatbot/chatbot_api.php"><div class="chatbot-window"><div class="chatbot-header"><span class="chatbot-title">AI Safety Assistant</span><button type="button" class="chatbot-close" aria-label="Close chatbot">&times;</button></div><div class="chatbot-messages"></div><div class="chatbot-input-wrap"><input type="text" class="chatbot-input" placeholder="Ask about evidence or map selection" aria-label="Chat input"><button type="button" class="chatbot-send">Send</button></div></div><button type="button" class="chatbot-toggle" aria-label="Open chatbot"><i class="fa-solid fa-comments"></i></button></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../chatbot/chatbot.js"></script>
<script src="../assets/js/report-form.js"></script>
</body>
</html>
x