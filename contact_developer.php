<?php
require_once __DIR__ . '/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please Login First");
    exit();
}
$user_name = $_SESSION['user_name'] ?? 'Admin';

$dev_name = 'Makarious';
$dev_phone = '+255 622 045 972';
$dev_email = 'stevenabalwambo@gmail.com';
$dev_phone_raw = '+255622045972';

$banner_img = 'assets/contact_developer_banner.png';
$has_banner = file_exists(__DIR__ . '/' . $banner_img);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <title>Contact Developer | TRA DDA</title>
    <link rel="icon" href="dda.jpg" type="image/jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/responsive-layout.css" rel="stylesheet">
<link href="assets/dashboard-common.css" rel="stylesheet">
    <style>
        :root { --tra-navy: #0b1e3b; --sidebar-width: 260px; --accent-gold: #c5a059; --light-bg: #f4f7fa; --tra-blue-gradient: linear-gradient(135deg, #0e2245 0%, #1c3d7a 100%); }
        body { font-family: 'Open Sans', sans-serif; background: var(--light-bg); margin: 0; overflow-x: hidden; }
        .sidebar-wrapper { width: var(--sidebar-width); background: var(--tra-navy); height: 100vh; position: fixed; left: 0; top: 0; z-index: 1000; color: #fff; overflow-y: auto; scrollbar-width: none; display: flex; flex-direction: column; }
        .sidebar-wrapper::-webkit-scrollbar { display: none; }
        .sidebar-logo-section { padding: 15px; text-align: center; background: rgba(0,0,0,.2); }
        .sidebar-logo-section img { width: 100%; height: 100px; border-radius: 0; background: #fff; padding: 0; object-fit: cover; object-position: center; display: block; }
        .card-sidebar-info { background: rgba(255,255,255,.05); margin: 15px; border-radius: 8px; padding: 15px; border-left: 3px solid var(--accent-gold); }
        .sidebar-nav { flex: 1; }
        .sidebar-link { padding: 12px 25px; color: #bdc3c7; display: flex; align-items: center; text-decoration: none !important; transition: .3s; font-size: 14px; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(255,255,255,.1); color: #fff; }
        .sidebar-link i { margin-right: 15px; width: 20px; text-align: center; }
        .sidebar-api-status { background: rgba(255,255,255,.06); margin: 0 15px; padding: 10px 12px; border-radius: 8px; }
        .api-status-icon { font-size: 8px; color: #94a3b8; }
        .api-status-icon.live { color: #22c55e; }
        .api-status-icon.offline { color: #ef4444; }
        .api-status-text { color: #94a3b8; font-size: 13px; }
        .api-status-text.live { color: #22c55e; }
        .api-status-text.offline { color: #ef4444; }
        .status-chip { border-radius: 999px; padding: 6px 12px; border: 1px solid #e2e8f0; background: #fff; font-size: 12px; color: #334155; }
        .api-live-chip .api-dot-icon { font-size: 8px; color: #94a3b8; }
        .api-live-chip .api-dot-icon.live { color: #22c55e; }
        .api-live-chip .api-dot-icon.offline { color: #ef4444; }
        .api-live-chip #header-api-label.live { color: #22c55e; }
        .api-live-chip #header-api-label.offline { color: #ef4444; }
        .sidebar-coat-section { display: none !important; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .tt-header { background: #fff; min-height: 65px; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; box-shadow: 0 1px 3px rgba(0,0,0,.1); position: sticky; top: 0; z-index: 999; }
        .tt-page-header {
            background: var(--tra-blue-gradient);
            padding: 36px 30px 30px;
            color: #fff;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .tt-page-header::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -95px;
            transform: translateX(-50%);
            width: min(760px, 94%);
            height: 180px;
            background: radial-gradient(circle, rgba(255,255,255,0.22) 0%, rgba(255,255,255,0.04) 55%, rgba(255,255,255,0) 75%);
            pointer-events: none;
        }
        .tt-page-header h1,
        .tt-page-header .small {
            position: relative;
            z-index: 1;
            margin-left: auto;
            margin-right: auto;
            text-shadow: 0 3px 14px rgba(6, 20, 45, 0.45);
        }
        .tt-page-header h1 {
            letter-spacing: .02em;
            animation: headerGlowFloat 4s ease-in-out infinite;
        }
        @keyframes headerGlowFloat {
            0%, 100% { transform: translateY(0); text-shadow: 0 3px 14px rgba(6, 20, 45, 0.45); }
            50% { transform: translateY(-2px); text-shadow: 0 6px 16px rgba(6, 20, 45, 0.55); }
        }
        .contact-section { display: flex; align-items: stretch; gap: 0; margin: 0 30px 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; min-height: 320px; }
        .contact-banner { flex: 0 0 48%; min-height: 280px; background: #f8fafc; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .contact-banner img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .contact-details { flex: 1; padding: 36px 40px; display: flex; flex-direction: column; justify-content: center; }
        .contact-details h1 { color: var(--tra-navy); font-size: 1.75rem; margin-bottom: 6px; font-weight: 700; }
        .contact-details .sub { color: #64748b; margin-bottom: 28px; font-size: 15px; }
        .contact-row { display: flex; align-items: flex-start; padding: 16px 0; border-bottom: 1px solid #f1f5f9; }
        .contact-row:last-of-type { border-bottom: 0; }
        .contact-row .icon-wrap { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 16px; font-size: 18px; flex-shrink: 0; }
        .contact-row .icon-phone { background: #dcfce7; color: #16a34a; }
        .contact-row .icon-email { background: #dbeafe; color: #2563eb; }
        .contact-row .label { font-weight: 700; color: #1e293b; margin-bottom: 4px; }
        .contact-row .value { color: #64748b; margin-bottom: 10px; }
        .btn-call { background: #16a34a; color: #fff !important; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 14px; border: none; }
        .btn-call:hover { background: #15803d; color: #fff !important; }
        .btn-email { background: #2563eb; color: #fff !important; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 14px; margin-left: 10px; border: none; }
        .btn-email:hover { color: #fff !important; background: #1d4ed8; }
        .mobile-menu-btn { display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; width: 44px; height: 44px; border-radius: 8px; background: var(--tra-navy); color: #fff; border: none; align-items: center; justify-content: center; font-size: 20px; cursor: pointer; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 999; }
        @media (max-width: 991px) {
            .mobile-menu-btn { display: flex; }
            .sidebar-wrapper { transform: translateX(-100%); transition: transform .3s ease; width: 260px; }
            .sidebar-wrapper.sidebar-open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .contact-section { flex-direction: column; margin: 15px; }
            .contact-banner { min-height: 200px; }
            .contact-details { padding: 28px 24px; }
        }
    </style>
</head>
<body>
<button type="button" class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Open menu"><i class="fas fa-bars"></i></button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<aside class="sidebar-wrapper">
    <div class="sidebar-logo-section">
        <img src="<?php echo htmlspecialchars(tra_sidebar_logo_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="TRA LOGO" onerror="this.src='https://via.placeholder.com/90?text=TRA'">
        <div class="mt-2 font-weight-bold small text-uppercase">Tanzania Revenue Authority</div>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard_home.php" class="sidebar-link"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="analytics.php" class="sidebar-link"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="dashboard.php" class="sidebar-link"><i class="fab fa-tiktok"></i> TikTok</a>
        <a href="airbnb_engine.php" class="sidebar-link"><i class="fab fa-airbnb"></i> Airbnb Engine</a>
        <a href="airbnb_realtime.php" class="sidebar-link"><i class="fas fa-bolt"></i> Airbnb Real Time</a>
        <a href="airbnb2_engine.php" class="sidebar-link"><i class="fab fa-airbnb"></i> Airbnb 2</a>
        <a href="upgraded_airbnb.php" class="sidebar-link"><i class="fas fa-star"></i> Upgraded Airbnb</a>
        <a href="booking_engine.php" class="sidebar-link"><i class="fas fa-hotel"></i> Booking Engine</a>
        <a href="download.php" class="sidebar-link"><i class="fas fa-file-export"></i> Export Records</a>
        <a href="settings_dashboard.php" class="sidebar-link"><i class="fas fa-user-cog"></i> Settings</a>
        <a href="contact_developer.php" class="sidebar-link active"><i class="fas fa-headset"></i> Contact Developer</a>
        <a href="logout.php" class="sidebar-link text-danger mt-3"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
    <div class="sidebar-coat-section">
        <img src="coat_of_arms.png" alt="Coat of Arms" class="sidebar-coat-img" onerror="this.style.display='none'">
    </div>
</aside>

<main class="main-content">
    <header class="tt-header">
        <div class="font-weight-bold text-muted"><i class="fas fa-shield-alt mr-2"></i>INSTITUTIONAL CONTROL DASHBOARD</div>
        <div class="status-chip api-live-chip d-flex align-items-center" id="header-api-status">
            <i class="fas fa-circle api-dot-icon mr-2" id="header-api-icon"></i>
            <span id="header-api-label">API …</span>
        </div>
    </header>
    <section class="tt-page-header">
        <h1 class="h3 mb-1">Contact Developer</h1>
        <div class="small">Wasiliana na developer wa mfumo wa TRA DDA.</div>
    </section>

    <div class="contact-section">
        <div class="contact-banner">
            <?php if ($has_banner): ?>
                <img src="<?php echo htmlspecialchars($banner_img); ?>" alt="Hire Dedicated Developers">
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-code fa-4x mb-3 opacity-50"></i>
                    <p class="mb-0 small">Developer contact</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="contact-details">
            <h1><i class="fas fa-user-cog text-secondary mr-2"></i><?php echo htmlspecialchars($dev_name); ?></h1>
            <p class="sub">Developer wa mfumo wa TRA DDA. Wasiliana kwa simu au barua pepe.</p>
            <div class="contact-row">
                <div class="icon-wrap icon-phone"><i class="fas fa-phone"></i></div>
                <div>
                    <div class="label">Simu</div>
                    <div class="value"><?php echo htmlspecialchars($dev_phone); ?></div>
                    <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $dev_phone_raw)); ?>" class="btn-call"><i class="fas fa-phone"></i> Piga sasa</a>
                </div>
            </div>
            <div class="contact-row">
                <div class="icon-wrap icon-email"><i class="fas fa-envelope"></i></div>
                <div>
                    <div class="label">Barua pepe</div>
                    <div class="value"><?php echo htmlspecialchars($dev_email); ?></div>
                    <a href="mailto:<?php echo htmlspecialchars($dev_email); ?>?subject=TRA%20DDA%20-%20Contact" class="btn-email"><i class="fas fa-envelope"></i> Tuma email</a>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="js/code_protection.js"></script>
<script>
(function() {
    var btn = document.getElementById('mobile-menu-btn');
    var sidebar = document.querySelector('.sidebar-wrapper');
    var overlay = document.getElementById('sidebar-overlay');
    if (!btn || !sidebar || !overlay) return;
    function openSidebar() { sidebar.classList.add('sidebar-open'); overlay.style.display = 'block'; btn.querySelector('i').className = 'fas fa-times'; }
    function closeSidebar() { sidebar.classList.remove('sidebar-open'); overlay.style.display = 'none'; btn.querySelector('i').className = 'fas fa-bars'; }
    btn.addEventListener('click', function() { sidebar.classList.contains('sidebar-open') ? closeSidebar() : openSidebar(); });
    overlay.addEventListener('click', closeSidebar);
})();
</script>
</body>
</html>
