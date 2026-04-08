<?php
include("config/security.php");
security_start_session();
security_enforce_timeout();
$flash = get_flash_message();
$is_user_logged_in = !empty($_SESSION["user_id"]);
$is_admin_logged_in = isset($_SESSION["admin_id"]);
?>
<!DOCTYPE html>
<html lang="<?php echo h(translation_get_html_lang()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crime Reporting and Incident Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/translation.css">
    <link rel="stylesheet" href="chatbot/chatbot.css">
</head>
<body>
<nav class="navbar navbar-expand-lg glass-nav">
    <div class="container py-2">
        <a class="navbar-brand d-flex align-items-center gap-3 fw-semibold" href="index.php">
            <span class="navbar-brand-mark"><i class="fa-solid fa-shield-halved"></i></span>
            <span class="navbar-brand-text">
                <span data-i18n="Crime Reporting System">Crime Reporting System</span>
                <small data-i18n="Smart city incident response portal">Smart city incident response portal</small>
            </span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 me-lg-3">
                <li class="nav-item"><a class="nav-link app-nav-link active" href="index.php" data-i18n="Home">Home</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="user/report.php" data-i18n="Report Crime">Report Crime</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="user/dashboard.php" data-i18n="Dashboard">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="user/dashboard.php#complaint-history" data-i18n="Track Complaint">Track Complaint</a></li>
                <li class="nav-item"><a class="nav-link app-nav-link" href="public_stats.php" data-i18n="Statistics">Statistics</a></li>
                <?php if ($is_user_logged_in) { ?>
                    <li class="nav-item"><a class="nav-link app-nav-link" href="user/logout.php" data-i18n="Logout">Logout</a></li>
                <?php } elseif ($is_admin_logged_in) { ?>
                    <li class="nav-item"><a class="nav-link app-nav-link" href="admin/logout.php" data-i18n="Logout">Logout</a></li>
                <?php } else { ?>
                    <li class="nav-item"><a class="nav-link app-nav-link" href="user/login.php" data-i18n="Login">Login</a></li>
                <?php } ?>
            </ul>
            <div class="d-grid d-lg-flex gap-2">
                <?php translation_render_language_selector(); ?>
                <?php if ($is_user_logged_in) { ?>
                    <a class="btn btn-outline-primary" href="user/dashboard.php" data-i18n="My Dashboard">My Dashboard</a>
                <?php } elseif ($is_admin_logged_in) { ?>
                    <a class="btn btn-outline-primary" href="admin/dashboard.php" data-i18n="Admin Dashboard">Admin Dashboard</a>
                <?php } else { ?>
                    <a class="btn btn-outline-primary" href="user/login.php" data-i18n="Citizen Login">Citizen Login</a>
                    <a class="btn btn-dark" href="admin/login.php" data-i18n="Admin Access">Admin Access</a>
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

<section class="hero-panel">
    <div class="container hero-copy">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="hero-eyebrow" data-i18n="Secure digital reporting for public safety"><i class="fa-solid fa-tower-broadcast"></i> Secure digital reporting for public safety</span>
                <h1 class="hero-title" data-i18n="Report Crimes Quickly & Securely">Report Crimes Quickly &amp; Securely</h1>
                <p class="hero-description" data-i18n="Submit incidents with verified location data, upload evidence, monitor complaint progress, and access transparent public safety insights through one modern service portal.">Submit incidents with verified location data, upload evidence, monitor complaint progress, and access transparent public safety insights through one modern service portal.</p>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="user/report.php" class="btn btn-primary btn-lg" data-i18n="Report Crime"><i class="fa-solid fa-file-circle-plus me-2"></i>Report Crime</a>
                    <a href="user/dashboard.php" class="btn btn-soft btn-lg" data-i18n="Track Complaint"><i class="fa-solid fa-magnifying-glass-location me-2"></i>Track Complaint</a>
                </div>
                <div class="hero-metrics">
                    <div class="hero-metric">
                        <strong>24/7</strong>
                        <span>Incident intake availability</span>
                    </div>
                    <div class="hero-metric">
                        <strong>GPS</strong>
                        <span>Accurate location capture</span>
                    </div>
                    <div class="hero-metric">
                        <strong>Live</strong>
                        <span>Status and dashboard updates</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="surface-card p-4 p-lg-5 text-dark">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <p class="text-uppercase small fw-semibold text-primary mb-2">Operational Overview</p>
                            <h3 class="mb-0">Faster civic response</h3>
                        </div>
                        <span class="feature-card-icon"><i class="fa-solid fa-city"></i></span>
                    </div>
                    <div class="vstack gap-3">
                        <div class="d-flex align-items-start gap-3">
                            <span class="feature-card-icon flex-shrink-0" style="width:46px;height:46px;"><i class="fa-solid fa-location-dot"></i></span>
                            <div>
                                <h6 class="mb-1">Location-first reporting</h6>
                                <p class="muted mb-0">Leaflet mapping helps citizens mark the exact place of the incident.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="feature-card-icon flex-shrink-0" style="width:46px;height:46px;"><i class="fa-solid fa-chart-line"></i></span>
                            <div>
                                <h6 class="mb-1">Transparent tracking</h6>
                                <p class="muted mb-0">Complaint status moves from pending review to investigation and resolution.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="feature-card-icon flex-shrink-0" style="width:46px;height:46px;"><i class="fa-solid fa-lock"></i></span>
                            <div>
                                <h6 class="mb-1">Secure submissions</h6>
                                <p class="muted mb-0">Evidence and incident details stay inside the official reporting workflow.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="text-center mb-5">
            <p class="text-uppercase small fw-semibold text-primary mb-2">Core Capabilities</p>
            <h2 class="section-title">A professional portal for city-scale incident management</h2>
            <p class="section-copy mx-auto">The interface is structured for citizens, operators, and administrators to move from report creation to evidence review and analytics without friction.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-xl-4">
                <div class="card card-hover h-100 p-4">
                    <span class="feature-card-icon"><i class="fa-solid fa-location-crosshairs"></i></span>
                    <h5 class="mt-4">GPS Location Tracking</h5>
                    <p class="muted mb-0">Capture precise coordinates and contextual address details for each reported incident.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hover h-100 p-4">
                    <span class="feature-card-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                    <h5 class="mt-4">Secure Evidence Upload</h5>
                    <p class="muted mb-0">Attach supporting images in a guided upload flow with clear validation and file constraints.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hover h-100 p-4">
                    <span class="feature-card-icon"><i class="fa-solid fa-timeline"></i></span>
                    <h5 class="mt-4">Real-Time Complaint Tracking</h5>
                    <p class="muted mb-0">Users can follow complaint progress and view their latest updates from one dashboard.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hover h-100 p-4">
                    <span class="feature-card-icon"><i class="fa-solid fa-robot"></i></span>
                    <h5 class="mt-4">AI Assistance Chatbot</h5>
                    <p class="muted mb-0">A floating assistant helps citizens understand reporting steps, evidence guidelines, and status flows.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hover h-100 p-4">
                    <span class="feature-card-icon"><i class="fa-solid fa-chart-pie"></i></span>
                    <h5 class="mt-4">Crime Analytics Dashboard</h5>
                    <p class="muted mb-0">Public and admin dashboards surface complaint distribution, monthly trends, and status insights.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card card-hover h-100 p-4">
                    <span class="feature-card-icon"><i class="fa-solid fa-shield-heart"></i></span>
                    <h5 class="mt-4">Service-Oriented Interface</h5>
                    <p class="muted mb-0">Clean navigation, mobile responsiveness, and accessibility-focused spacing improve trust and usability.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="surface-card p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <p class="text-uppercase small fw-semibold text-primary mb-2">Start Now</p>
                    <h3 class="mb-2">Need to submit an incident or check complaint progress?</h3>
                    <p class="muted mb-0">Use the citizen tools to create a complaint, attach evidence, or monitor investigation updates.</p>
                </div>
                <div class="col-lg-4">
                    <div class="d-grid gap-3">
                        <a href="user/report.php" class="btn btn-primary"><i class="fa-solid fa-bullhorn me-2"></i>Report Crime</a>
                        <a href="public_stats.php" class="btn btn-outline-primary"><i class="fa-solid fa-chart-column me-2"></i>View Public Statistics</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</main>

<div id="crimeChatbot" class="chatbot-widget" data-api-url="chatbot/chatbot_api.php">
    <div class="chatbot-window">
        <div class="chatbot-header">
            <span class="chatbot-title" data-i18n="AI Safety Assistant">AI Safety Assistant</span>
            <button type="button" class="chatbot-close" aria-label="Close chatbot">&times;</button>
        </div>
        <div class="chatbot-messages"></div>
        <div class="chatbot-input-wrap">
            <input type="text" class="chatbot-input" placeholder="Ask about reporting, evidence, or tracking" aria-label="Chat input" data-i18n-placeholder data-i18n-aria-label>
            <button type="button" class="chatbot-send" data-i18n="Send">Send</button>
        </div>
    </div>
    <button type="button" class="chatbot-toggle" aria-label="Open chatbot"><i class="fa-solid fa-comments"></i></button>
</div>

<?php translation_render_page_config(); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/translation.js"></script>
<script src="chatbot/chatbot.js"></script>
</body>
</html>
