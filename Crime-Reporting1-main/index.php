<?php
include("config/security.php");
security_start_session();
security_enforce_timeout();
$flash = get_flash_message();
$is_user_logged_in = !empty($_SESSION["user_id"]);
$is_admin_logged_in = isset($_SESSION["admin_id"]);
$chatbot_css_version = (string)filemtime(__DIR__ . "/chatbot/chatbot.css");
$chatbot_js_version = (string)filemtime(__DIR__ . "/chatbot/chatbot.js");
$translate_js_version = (string)filemtime(__DIR__ . "/assets/js/google-translate-switcher.js");
$tour_js_version = (string)filemtime(__DIR__ . "/assets/js/site-tour.js");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crime Reporting and Incident Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="chatbot/chatbot.css?v=<?php echo h($chatbot_css_version); ?>">
</head>
<body class="home-page" data-tour-page="home">
<div class="top-alert-strip">
    <strong data-i18n="common.alert_title">Emergency?</strong> <span data-i18n="common.alert_text">Call 100 or 112 immediately. This portal is for non-emergency reporting only.</span>
</div>
<nav class="navbar navbar-expand-lg glass-nav" data-tour-step="1" data-tour-title="Main Navigation" data-tour-text="Use this top bar to move between reporting, tracking complaints, viewing statistics, and switching language at any time.">
    <div class="container py-2">
        <a class="navbar-brand d-flex align-items-center gap-3 fw-semibold" href="index.php">
            <span class="navbar-brand-mark brand-logo-shell">
                <img class="brand-logo-img-contain" src="assets/img/logo-new.png" alt="Crime Reporting System logo">
            </span>
            <span class="navbar-brand-text">
                Crime Reporting System
                <small data-i18n="index.brand_tagline">Smart city incident response portal</small>
            </span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 me-lg-3">
                <li class="nav-item"><a class="nav-link app-nav-link active" href="index.php"><span data-i18n="common.nav_home">Home</span></a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="user/report.php"><span data-i18n="common.nav_report">Report Crime</span></a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="user/dashboard.php"><span data-i18n="common.nav_dashboard">Dashboard</span></a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="user/dashboard.php#complaint-history"><span data-i18n="common.nav_track">Track Complaint</span></a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="public_stats.php"><span data-i18n="common.nav_statistics">Statistics</span></a></li>
                <?php if ($is_user_logged_in) { ?>
                    <li class="nav-item"><a class="nav-link app-nav-link" href="user/logout.php"><span data-i18n="common.nav_logout">Logout</span></a></li>
                <?php } elseif ($is_admin_logged_in) { ?>
                    <li class="nav-item"><a class="nav-link app-nav-link" href="admin/logout.php"><span data-i18n="common.nav_logout">Logout</span></a></li>
                <?php } else { ?>
                    <li class="nav-item"><a class="nav-link app-nav-link" href="user/login.php"><span data-i18n="index.login">Login</span></a></li>
                <?php } ?>
            </ul>
            <div class="d-grid d-lg-flex gap-2">
                <div class="language-switcher-wrap">
                    <label class="visually-hidden" for="indexLanguageSwitcher" data-i18n="common.lang_label">Language</label>
                    <div class="language-switcher-shell">
                        <i class="fa-solid fa-language" aria-hidden="true"></i>
                        <select id="indexLanguageSwitcher" class="language-switcher-select" aria-label="Language switcher" data-google-translate-switcher>
                            <option value="en">English</option>
                            <option value="hi">हिंदी</option>
                        </select>
                    </div>
                </div>
                <?php if ($is_user_logged_in) { ?>
                    <a class="btn btn-outline-primary" href="user/dashboard.php"><span data-i18n="index.my_dashboard">My Dashboard</span></a>
                <?php } elseif ($is_admin_logged_in) { ?>
                    <a class="btn btn-outline-primary" href="admin/dashboard.php"><span data-i18n="index.admin_dashboard">Admin Dashboard</span></a>
                <?php } else { ?>
                    <a class="btn btn-outline-primary" href="user/login.php"><span data-i18n="index.citizen_login">Citizen Login</span></a>
                    <a class="btn btn-dark" href="admin/login.php"><span data-i18n="index.admin_access">Admin Access</span></a>
                <?php } ?>
            </div>
        </div>
    </div>
</nav>

<main class="app-shell">
<?php if ($flash) { ?>
    <div class="container pt-4">
        <div class="alert alert-<?php echo h($flash["type"]); ?> mb-0"><?php echo h($flash["text"]); ?></div>
    </div>
<?php } ?>

<section class="hero-panel" data-tour-step="2" data-tour-title="Start Here" data-tour-text="This hero section is the fastest entry point for a new visitor. Use Report Crime to file a complaint or Track Complaint to follow an existing one.">
    <div class="container hero-copy">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <p class="hero-intro mb-3" data-i18n="index.hero_intro">1. Incident Type 2. Location 3. Details &amp; Media 4. Review &amp; Submit</p>
                <span class="hero-eyebrow"><i class="fa-solid fa-circle-check"></i> <span data-i18n="index.hero_eyebrow">Non-emergency digital reporting portal</span></span>
                <h1 class="hero-title" data-i18n="index.hero_title">Report an Incident. Securely and Confidently.</h1>
                <p class="hero-description" data-i18n="index.hero_description">Share incident details, location, and supporting evidence through one trusted interface designed for citizens, responders, and administrators.</p>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="user/report.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-shield-halved me-2"></i><span data-i18n="index.hero_primary">Report Crime</span></a>
                    <a href="user/dashboard.php" class="btn btn-soft btn-lg"><i class="fa-solid fa-magnifying-glass me-2"></i><span data-i18n="index.hero_secondary">Track Complaint</span></a>
                </div>
                <div class="hero-metrics">
                    <div class="hero-metric">
                        <strong>24/7</strong>
                        <span data-i18n="index.metric_1">Incident intake availability</span>
                    </div>
                    <div class="hero-metric">
                        <strong>GPS</strong>
                        <span data-i18n="index.metric_2">Accurate location capture</span>
                    </div>
                    <div class="hero-metric">
                        <strong>Live</strong>
                        <span data-i18n="index.metric_3">Status and dashboard updates</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="portal-overview-card p-4 p-lg-5 text-dark">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <p class="overview-label mb-2" data-i18n="index.overview_label">Portal Overview</p>
                            <h3 class="mb-0" data-i18n="index.overview_title">How the reporting flow works</h3>
                        </div>
                        <span class="feature-card-icon icon-lilac"><i class="fa-solid fa-notes-medical"></i></span>
                    </div>
                    <div>
                        <div class="overview-step">
                            <span class="overview-step-icon"><i class="fa-solid fa-list-check"></i></span>
                            <div>
                                <h6 data-i18n="index.step_1_title">Choose an incident type</h6>
                                <p data-i18n="index.step_1_copy">Start with the category that best matches the complaint you want to submit.</p>
                            </div>
                        </div>
                        <div class="overview-step">
                            <span class="overview-step-icon"><i class="fa-solid fa-location-dot"></i></span>
                            <div>
                                <h6 data-i18n="index.step_2_title">Add location and details</h6>
                                <p data-i18n="index.step_2_copy">Pin the incident on the map and include the facts officers need to review it properly.</p>
                            </div>
                        </div>
                        <div class="overview-step">
                            <span class="overview-step-icon"><i class="fa-solid fa-shield-heart"></i></span>
                            <div>
                                <h6 data-i18n="index.step_3_title">Submit and track securely</h6>
                                <p data-i18n="index.step_3_copy">Your complaint enters the official workflow and can be followed through the dashboard.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding home-capabilities-section" data-tour-step="3" data-tour-title="Core Capabilities" data-tour-text="This section explains what the portal can do, including location capture, evidence upload, complaint tracking, analytics, and chatbot support.">
    <div class="container">
        <div class="home-capabilities-intro text-center mb-5">
            <p class="text-uppercase small fw-semibold text-primary mb-2" data-i18n="index.cap_label">Core Capabilities</p>
            <h2 class="section-title" data-i18n="index.cap_title">A professional portal for city-scale incident management</h2>
            <p class="section-copy mx-auto" data-i18n="index.cap_copy">The interface is structured for citizens, operators, and administrators to move from report creation to evidence review and analytics without friction.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-xl-4">
                <div class="card card-hover h-100 p-4">
                    <span class="feature-card-icon icon-sky"><i class="fa-solid fa-location-crosshairs"></i></span>
                    <h5 class="mt-4">GPS Location Tracking</h5>
                    <p class="muted mb-0">Capture precise coordinates and contextual address details for each reported incident.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hover h-100 p-4">
                    <span class="feature-card-icon icon-rose"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                    <h5 class="mt-4">Secure Evidence Upload</h5>
                    <p class="muted mb-0">Attach supporting images in a guided upload flow with clear validation and file constraints.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hover h-100 p-4">
                    <span class="feature-card-icon icon-blue"><i class="fa-solid fa-timeline"></i></span>
                    <h5 class="mt-4">Real-Time Complaint Tracking</h5>
                    <p class="muted mb-0">Users can follow complaint progress and view their latest updates from one dashboard.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hover h-100 p-4">
                    <span class="feature-card-icon icon-lilac"><i class="fa-solid fa-robot"></i></span>
                    <h5 class="mt-4">AI Assistance Chatbot</h5>
                    <p class="muted mb-0">A floating assistant helps citizens understand reporting steps, evidence guidelines, and status flows.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hover h-100 p-4">
                    <span class="feature-card-icon icon-amber"><i class="fa-solid fa-chart-pie"></i></span>
                    <h5 class="mt-4">Crime Analytics Dashboard</h5>
                    <p class="muted mb-0">Public and admin dashboards surface complaint distribution, monthly trends, and status insights.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hover h-100 p-4">
                    <span class="feature-card-icon icon-mint"><i class="fa-solid fa-shield-heart"></i></span>
                    <h5 class="mt-4">Service-Oriented Interface</h5>
                    <p class="muted mb-0">Clean navigation, mobile responsiveness, and accessibility-focused spacing improve trust and usability.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="home-service-hub-section py-5" data-tour-step="4" data-tour-title="Quick Service Hub" data-tour-text="These shortcuts take users directly to the most-used services such as reporting a crime, checking status, viewing public stats, and opening the dashboard.">
    <div class="container">
        <div class="home-service-hub-shell">
            <div class="text-center mb-4">
                <p class="text-uppercase small fw-semibold text-primary mb-2" data-i18n="index.service_label">Online Services</p>
                <h2 class="section-title mb-2" data-i18n="index.service_title">Citizen Service Hub</h2>
                <p class="section-copy mx-auto mb-0" data-i18n="index.service_copy">Quick access to the most-used reporting, tracking, and safety services from one clean police-style service grid.</p>
            </div>
            <div class="row g-3 g-lg-4">
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="service-hub-tile" href="user/report.php">
                        <span class="service-hub-icon icon-rose"><i class="fa-solid fa-file-circle-plus"></i></span>
                        <span>Report Crime</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="service-hub-tile" href="user/dashboard.php#complaint-history">
                        <span class="service-hub-icon icon-blue"><i class="fa-solid fa-clipboard-check"></i></span>
                        <span>Track Complaint</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="service-hub-tile" href="public_stats.php">
                        <span class="service-hub-icon icon-amber"><i class="fa-solid fa-chart-line"></i></span>
                        <span>Public Stats</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="service-hub-tile" href="user/report.php">
                        <span class="service-hub-icon icon-sky"><i class="fa-solid fa-map-location-dot"></i></span>
                        <span>Share Location</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="service-hub-tile" href="user/dashboard.php">
                        <span class="service-hub-icon icon-lilac"><i class="fa-solid fa-gauge-high"></i></span>
                        <span>My Dashboard</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="service-hub-tile" href="user/login.php">
                        <span class="service-hub-icon icon-mint"><i class="fa-solid fa-user-shield"></i></span>
                        <span>Citizen Access</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="home-helpline-strip">
            <div class="home-helpline-marquee">
                <div class="home-helpline-track">
                    <span class="helpline-badge" data-i18n="index.helpline_badge">Helplines</span>
                    <span><strong>Emergency:</strong> 100 / 112</span>
                    <span><strong>Cyber Crime:</strong> 1930</span>
                    <span><strong data-i18n="index.helpline_support">Citizen Support:</strong> <span data-i18n="index.helpline_support_text">24/7 intake</span></span>
                    <span><strong data-i18n="index.helpline_evidence">Evidence Upload:</strong> <span data-i18n="index.helpline_evidence_text">image reporting enabled</span></span>
                    <span class="helpline-badge" data-i18n="index.helpline_badge">Helplines</span>
                    <span><strong>Emergency:</strong> 100 / 112</span>
                    <span><strong>Cyber Crime:</strong> 1930</span>
                    <span><strong data-i18n="index.helpline_support">Citizen Support:</strong> <span data-i18n="index.helpline_support_text">24/7 intake</span></span>
                    <span><strong data-i18n="index.helpline_evidence">Evidence Upload:</strong> <span data-i18n="index.helpline_evidence_text">image reporting enabled</span></span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pb-5 home-cta-section">
    <div class="container">
        <div class="surface-card p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <p class="text-uppercase small fw-semibold text-primary mb-2" data-i18n="index.cta_label">Start Now</p>
                    <h3 class="mb-2" data-i18n="index.cta_title">Need to submit an incident or check complaint progress?</h3>
                    <p class="muted mb-0" data-i18n="index.cta_copy">Use the citizen tools to create a complaint, attach evidence, or monitor investigation updates.</p>
                </div>
                <div class="col-lg-4">
                    <div class="d-grid gap-3">
                        <a href="user/report.php" class="btn btn-primary"><i class="fa-solid fa-bullhorn me-2"></i><span data-i18n="index.hero_primary">Report Crime</span></a>
                        <a href="public_stats.php" class="btn btn-outline-primary"><i class="fa-solid fa-chart-column me-2"></i><span data-i18n="index.cta_stats">View Public Statistics</span></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</main>
<?php
$footer_base_path = ".";
$footer_variant = "public";
include("includes/footer.php");
?>
<div id="google_translate_element" class="visually-hidden" aria-hidden="true"></div>

<div id="crimeChatbot" class="chatbot-widget" data-api-url="chatbot/chatbot_api.php" data-logo-src="assets/img/bot_logo.png" data-tour-step="5" data-tour-title="Help Assistant" data-tour-text="Open the chatbot whenever you need quick guidance about reporting, uploading evidence, tracking complaints, or emergency directions.">
    <div class="chatbot-window">
        <div class="chatbot-header">
            <span class="chatbot-title">AI Safety Assistant</span>
            <button type="button" class="chatbot-close" aria-label="Close chatbot">&times;</button>
        </div>
        <div class="chatbot-messages"></div>
        <div class="chatbot-input-wrap">
            <input type="text" class="chatbot-input" placeholder="Ask about reporting, evidence, or tracking" aria-label="Chat input">
            <button type="button" class="chatbot-send">Send</button>
        </div>
    </div>
    <button type="button" class="chatbot-toggle" aria-label="Open chatbot">
        <img class="chatbot-toggle-logo" src="assets/img/bot_logo.png" alt="Chatbot logo">
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/site-tour.js?v=<?php echo h($tour_js_version); ?>"></script>
<script src="assets/js/google-translate-switcher.js?v=<?php echo h($translate_js_version); ?>"></script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<script src="chatbot/chatbot.js?v=<?php echo h($chatbot_js_version); ?>"></script>
</body>
</html>
