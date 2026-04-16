<?php
$footer_base_path = isset($footer_base_path) ? rtrim((string)$footer_base_path, "/") : ".";
$footer_variant = isset($footer_variant) ? (string)$footer_variant : "public";
$footer_title = $footer_variant === "admin" ? "Admin Operations Footer" : "Crime Reporting System";
$footer_tagline = $footer_variant === "admin"
    ? "Secure oversight for complaint review, analytics, and status control."
    : "Secure reporting for citizens, responders, and city administrators.";
?>
<footer class="site-footer <?php echo $footer_variant === "admin" ? "site-footer-admin" : ""; ?>">
    <div class="container">
        <div class="site-footer-grid">
            <div>
                <div class="site-footer-brand">
                    <span class="navbar-brand-mark brand-logo-shell">
                        <img class="brand-logo-img-contain" src="<?php echo h($footer_base_path); ?>/assets/img/logo-new.png" alt="Crime Reporting System logo">
                    </span>
                    <div>
                        <h5 data-i18n="footer.title"><?php echo h($footer_title); ?></h5>
                        <p data-i18n="footer.tagline"><?php echo h($footer_tagline); ?></p>
                    </div>
                </div>
            </div>
            <div>
                <h6 data-i18n="footer.tools">Citizen Tools</h6>
                <ul class="site-footer-links">
                    <li><a href="<?php echo h($footer_base_path); ?>/index.php" data-i18n="footer.home">Home</a></li>
                    <li><a href="<?php echo h($footer_base_path); ?>/user/report.php" data-i18n="footer.report">Report Crime</a></li>
                    <li><a href="<?php echo h($footer_base_path); ?>/user/dashboard.php" data-i18n="footer.track">Track Complaint</a></li>
                    <li><a href="<?php echo h($footer_base_path); ?>/public_stats.php" data-i18n="footer.stats">Statistics</a></li>
                </ul>
            </div>
            <div>
                <h6 data-i18n="footer.access">Access</h6>
                <ul class="site-footer-links">
                    <li><a href="<?php echo h($footer_base_path); ?>/user/login.php" data-i18n="footer.citizen_login">Citizen Login</a></li>
                    <li><a href="<?php echo h($footer_base_path); ?>/user/register.php" data-i18n="footer.citizen_register">Citizen Register</a></li>
                    <li><a href="<?php echo h($footer_base_path); ?>/admin/login.php" data-i18n="footer.admin_login">Admin Login</a></li>
                </ul>
            </div>
            <div>
                <h6 data-i18n="footer.support">Support</h6>
                <ul class="site-footer-links">
                    <li><span data-i18n="footer.support_emergency">Emergency: 100 / 112</span></li>
                    <li><span data-i18n="footer.support_non_emergency">Non-emergency portal only</span></li>
                    <li><span data-i18n="footer.support_availability">Digital incident intake available 24/7</span></li>
                </ul>
            </div>
        </div>
        <div class="site-footer-bottom">
            <span>&copy; <?php echo date("Y"); ?> Crime Reporting System</span>
            <span data-i18n="footer.bottom_right">Designed for secure digital complaint management</span>
        </div>
    </div>
</footer>
