<?php require_once __DIR__ . '/config.php'; ?>

<?php
// Kuanzisha kikao na kuangalia kama mtumiaji ameingia
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please Login First");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// SIMULATE ADMIN APPROVAL
$_SESSION['device_approved'] = true;
$_SESSION['device_recognized'] = true;
$device_approved = $_SESSION['device_approved'] ?? false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="format-detection" content="telephone=no">
    <title>TRA DDA | Digital Engine</title>
    
    <!-- Favicon (Logo kwenye browser tab) -->
    <link rel="icon" href="dda.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="dda.jpg" type="image/jpeg">
    <link rel="apple-touch-icon" href="dda.jpg">
    
    <!-- Fonts & CSS (WazoHost/Institutional Style) -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="assets/responsive-layout.css" rel="stylesheet">
<link href="assets/dashboard-common.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 300px;
            --tra-navy: #0b1e3b;
            --tra-blue-gradient: linear-gradient(135deg, #0e2245 0%, #1c3d7a 100%);
            --accent-gold: #c5a059;
            --light-bg: #f4f7fa;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            overflow-x: hidden;
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }

        /* --- SIDEBAR STYLE (WAZOHOST STYLE) --- */
        .sidebar-wrapper {
            width: var(--sidebar-width);
            background-color: var(--tra-navy);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            color: #fff;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            opacity: 0;
            transform: translateX(-20px);
        }
        .sidebar-wrapper.sidebar-visible {
            opacity: 1;
            transform: translateX(0);
        }
        .sidebar-wrapper::-webkit-scrollbar { display: none; }

        .sidebar-logo-section {
            padding: 15px;
            text-align: center;
            background: rgba(0,0,0,0.2);
        }

        .sidebar-logo-section img {
            width: 100%;
            height: 100px;
            border-radius: 0;
            background: #fff;
            padding: 0;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .card-sidebar-info {
            background: rgba(255,255,255,0.05);
            margin: 15px;
            border-radius: 8px;
            padding: 15px;
            border-left: 3px solid var(--accent-gold);
        }

        .sidebar-nav {
            margin-top: 10px;
        }

        .sidebar-link {
            padding: 12px 25px;
            color: #bdc3c7;
            display: flex;
            align-items: center;
            text-decoration: none !important;
            transition: 0.3s;
            font-size: 14px;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }

        .sidebar-link i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        .sidebar-wrapper { display: flex; flex-direction: column; }
        .sidebar-nav { flex: 1; }
        .sidebar-api-status { background: rgba(255,255,255,.06); }
        .api-status-icon { font-size: 8px; vertical-align: middle; color: #94a3b8; transition: color .3s; }
        .api-status-icon.live { color: #22c55e; }
        .api-status-icon.offline { color: #ef4444; }
        .api-status-text { color: #94a3b8; transition: color .3s; }
        .api-status-text.live { color: #22c55e; }
        .api-status-text.offline { color: #ef4444; }
        .sidebar-coat-section { display: none !important; }

        .sidebar-wrapper, .main-content, .tile-box, .card-custom {
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        /* --- MAIN CONTENT AREA --- */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            overflow-x: hidden;
            scrollbar-width: none;
            -ms-overflow-style: none;
            opacity: 0;
            transform: translateX(15px);
        }
        .main-content.main-visible {
            opacity: 1;
            transform: translateX(0);
        }
        .main-content::-webkit-scrollbar { display: none; }

        /* Top Header Navigation */
        .tt-header {
            background: #fff;
            min-height: 65px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
            gap: 20px;
        }
        .header-title {
            min-width: 0;
            line-height: 1.35;
        }

        /* Nav Search Bar */
        .nav-search-wrap {
            flex: 1;
            max-width: 400px;
            position: relative;
        }
        .nav-search-input {
            width: 100%;
            padding: 10px 18px 10px 42px;
            border: 1px solid #e2e8f0;
            border-radius: 25px;
            font-size: 14px;
            background: #f8fafc;
            transition: all 0.35s ease;
        }
        .nav-search-input:focus {
            outline: none;
            border-color: var(--accent-gold);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(197, 160, 89, 0.2);
        }
        .nav-search-input::placeholder { color: #94a3b8; }
        .nav-search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
            pointer-events: none;
            transition: color 0.3s;
        }
        .nav-search-wrap:focus-within .nav-search-icon { color: var(--accent-gold); }

        /* Status Indicator - Redesigned with animations */
        .status-badge {
            font-size: 12px;
            font-weight: 700;
            padding: 8px 18px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .status-online {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            color: #fff;
            border: 1px solid #34d399;
            box-shadow: 0 2px 12px rgba(52, 211, 153, 0.4);
        }
        .status-offline {
            background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
            color: #fff;
            border: 1px solid #f87171;
            box-shadow: 0 2px 12px rgba(248, 113, 113, 0.4);
        }
        .status-badge.status-pulse { animation: statusPulse 2s ease-in-out infinite; }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            position: relative;
        }
        .status-online .status-dot {
            background: #a7f3d0;
            box-shadow: 0 0 8px rgba(167, 243, 208, 0.8);
            animation: dotPulse 1.5s ease-in-out infinite;
        }
        .status-offline .status-dot {
            background: #fed7aa;
            box-shadow: 0 0 8px rgba(254, 215, 170, 0.6);
            animation: dotBlink 0.8s ease-in-out infinite;
        }
        @keyframes statusPulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.85; transform: scale(1.02); } }
        @keyframes dotPulse { 0%, 100% { box-shadow: 0 0 8px rgba(167, 243, 208, 0.8), 0 0 0 0 rgba(167, 243, 208, 0.6); } 50% { box-shadow: 0 0 12px rgba(167, 243, 208, 1), 0 0 0 8px rgba(167, 243, 208, 0); } }
        @keyframes dotBlink { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

        /* WazoHost Page Header (Blue Banner) */
        .tt-page-header {
            background: var(--tra-blue-gradient);
            padding: 40px 30px 30px;
            color: #fff;
            text-align: center;
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.5s ease, transform 0.5s ease;
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
        .tt-page-header .breadcrumb,
        .tt-page-header .small {
            position: relative;
            z-index: 1;
            margin-left: auto;
            margin-right: auto;
            text-shadow: 0 3px 14px rgba(6, 20, 45, 0.45);
        }
        .tt-page-header .breadcrumb {
            justify-content: center;
        }
        .tt-page-header h1 {
            letter-spacing: 0.02em;
            animation: headerGlowFloat 4s ease-in-out infinite;
        }
        @keyframes headerGlowFloat {
            0%, 100% { transform: translateY(0); text-shadow: 0 3px 14px rgba(6, 20, 45, 0.45); }
            50% { transform: translateY(-2px); text-shadow: 0 6px 16px rgba(6, 20, 45, 0.55); }
        }
        .tt-page-header.header-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* KPI Tiles (WazoHost Style) */
        .tiles-wrapper {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            padding: 20px 30px;
            margin-top: 0;
        }

        .tile-box {
            background: #fff;
            padding: 26px;
            min-height: 126px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none !important;
            color: inherit;
            border-bottom: 3px solid transparent;
            transition: opacity 0.5s ease, transform 0.5s ease;
            opacity: 0;
            transform: translateY(20px);
        }
        .tile-box.tile-visible {
            opacity: 1;
            transform: translateY(0);
        }
        .tile-box:hover { transform: translateY(-5px); border-bottom-color: var(--accent-gold); }
        .tile-box i { font-size: 28px; color: var(--accent-gold); }
        .tile-stat { font-size: 30px; font-weight: 800; color: #333; line-height: 1; letter-spacing: .01em; }
        .tile-stat.tile-profiles { color: #0f766e; }
        .tile-stat.tile-contacts { color: #1d4ed8; }
        .tile-stat.tile-hashtag { color: #7c3aed; }
        .tile-stat.tile-api { color: #b91c1c; }
        .tile-label { font-size: 12px; color: #888; text-transform: uppercase; }

        /* Search Card (Domain Style) */
        .card-custom {
            background: #fff;
            margin: 20px 30px;
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            opacity: 0;
            transform: translateY(15px);
        }
        .card-custom.card-visible {
            opacity: 1;
            transform: translateY(0);
        }
        .card-header-accent { border-top: 3px solid var(--accent-gold); font-weight: 700; background: #fff; padding: 15px 20px; }

        /* GLASSMORPHISM SEARCH OVERLAY */
        .search-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(12px);
            z-index: 10000;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .loader-card {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 90%;
        }
        .loader-title {
            margin: 6px 0 8px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-size: 1.15rem;
            background: linear-gradient(90deg, #1a73e8, #34a853, #fbbc05, #ea4335);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: loaderTitleGlow 2.4s ease-in-out infinite;
        }
        .loader-text {
            color: #475569;
            font-weight: 700;
            margin-bottom: 10px;
            animation: loaderTextPulse 1.4s ease-in-out infinite;
        }
        .google-spinner {
            width: 54px;
            height: 54px;
            margin: 0 auto 10px;
            border-radius: 50%;
            border: 5px solid transparent;
            border-top-color: #1a73e8;
            border-right-color: #34a853;
            border-bottom-color: #fbbc05;
            border-left-color: #ea4335;
            animation: googleSpin 1s linear infinite;
        }
        @keyframes googleSpin { 100% { transform: rotate(360deg); } }
        @keyframes loaderTextPulse { 0%, 100% { opacity: 0.75; } 50% { opacity: 1; } }
        @keyframes loaderTitleGlow {
            0%, 100% { filter: drop-shadow(0 0 0 rgba(26, 115, 232, 0)); }
            50% { filter: drop-shadow(0 2px 8px rgba(26, 115, 232, 0.25)); }
        }

        /* Results Table */
        .results-wrapper {
            display: none;
            margin: 0 30px 30px;
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            opacity: 0;
            transform: translateY(10px);
        }
        .table-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 4px;
            border: 1px solid transparent;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
            word-break: break-word;
        }
        .badge-creator { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
        .badge-contact { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .badge-description { background: #f8fafc; color: #334155; border-color: #cbd5e1; font-weight: 600; }
        .results-wrapper.results-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Mobile hamburger button */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: var(--tra-navy);
            color: #fff;
            border: none;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            -webkit-tap-highlight-color: transparent;
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            -webkit-tap-highlight-color: transparent;
        }

        /* ========== MOBILE RESPONSIVE ========== */
        @media (max-width: 991px) {
            .mobile-menu-btn { display: flex; }
            .sidebar-wrapper {
                transform: translateX(-100%);
                transition: transform 0.3s ease, opacity 0.3s ease;
                width: 280px;
            }
            .sidebar-wrapper.sidebar-open {
                transform: translateX(0);
                opacity: 1;
            }
            .sidebar-wrapper.sidebar-visible { transform: translateX(-100%); }
            .sidebar-wrapper.sidebar-open.sidebar-visible { transform: translateX(0); }
            .main-content { margin-left: 0; padding-top: 0; }
            .tt-header { flex-wrap: wrap; padding: 10px 15px 10px 60px; gap: 10px; }
            .header-title { font-size: 12px; }
            .nav-search-wrap { max-width: none; order: 3; width: 100%; flex: 1 1 100%; }
            .tiles-wrapper {
                grid-template-columns: repeat(2, 1fr);
                padding: 15px;
                gap: 10px;
                margin-top: 0;
            }
            .tile-box { min-height: 110px; padding: 18px; }
            .tile-stat { font-size: 26px; }
            .tile-label { font-size: 10px; }
            .tt-page-header { padding: 25px 15px 20px; }
            .card-custom { margin: 15px; }
            .results-wrapper { margin: 15px; padding: 15px; }
            .input-group { display: flex; flex-direction: row; width: 100%; }
            .input-group .form-control { flex: 1; min-width: 0; border-radius: 8px 0 0 8px; }
            .input-group .input-group-append { flex-shrink: 0; }
            .input-group .btn { border-radius: 0 8px 8px 0; min-height: 48px; white-space: nowrap; padding: 12px 20px; }
            #hashtag-input { min-height: 48px; font-size: 16px; }
            .card-body { padding: 15px; }
        }

        @media (max-width: 767px) {
            .tt-header .d-flex.align-items-center .border-left,
            .tt-header .font-weight-bold:last-child { display: none !important; }
            .status-badge { padding: 6px 12px; font-size: 11px; }
            .status-badge span { max-width: 70px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        }

        @media (max-width: 575px) {
            .tiles-wrapper { grid-template-columns: 1fr; }
            .mobile-menu-btn { top: 12px; left: 12px; width: 42px; height: 42px; }
            .tt-header { padding-left: 15px; padding-right: 15px; }
            .header-title { width: 100%; }
        }

        /* Tables: card layout on mobile */
        @media (max-width: 767px) {
            .results-wrapper .table thead { display: none; }
            .results-wrapper .table, .results-wrapper .table tbody, .results-wrapper .table tr, .results-wrapper .table td { display: block; }
            .results-wrapper .table tr {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                margin-bottom: 12px;
                padding: 12px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            }
            .results-wrapper .table td {
                padding: 8px 0;
                border: none;
                display: flex;
                align-items: flex-start;
                gap: 10px;
            }
            .results-wrapper .table td::before {
                content: attr(data-label);
                font-weight: 700;
                color: #64748b;
                min-width: 100px;
                font-size: 12px;
            }
            .results-wrapper .table td:last-child { justify-content: flex-end; padding-top: 12px; border-top: 1px solid #f1f5f9; }
            .results-wrapper .table td .badge { word-break: break-all; }
            .results-wrapper .table td .btn { min-height: 44px; padding: 10px 20px; }
        }

        /* iOS safe area (notch devices) */
        @supports (padding: max(0px)) {
            body { padding-left: env(safe-area-inset-left, 0); }
            .mobile-menu-btn { left: max(15px, env(safe-area-inset-left)); }
            .tt-header { padding-left: max(15px, env(safe-area-inset-left)); }
        }

    </style>
</head>
<body>

<!-- Mobile menu button -->
<button type="button" class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Open menu">
    <i class="fas fa-bars"></i>
</button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- SIDEBAR (Left) -->
<aside class="sidebar-wrapper">
    <div class="sidebar-logo-section">
        <!-- Logo ya TRA - uploads/image.png -->
        <img src="<?php echo htmlspecialchars(tra_sidebar_logo_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="TRA LOGO" onerror="this.src='https://via.placeholder.com/90?text=TRA'">
        <div class="mt-2 font-weight-bold small text-uppercase">Tanzania Revenue Authority</div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <a href="dashboard_home.php" class="sidebar-link"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="analytics.php" class="sidebar-link"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="dashboard.php" class="sidebar-link active"><i class="fab fa-tiktok"></i> TikTok</a>
        <a href="airbnb_engine.php" class="sidebar-link"><i class="fab fa-airbnb"></i> Airbnb Engine</a>
        <a href="airbnb_realtime.php" class="sidebar-link"><i class="fas fa-bolt"></i> Airbnb Real Time</a>
        <a href="airbnb2_engine.php" class="sidebar-link"><i class="fab fa-airbnb"></i> Airbnb 2</a>
        <a href="upgraded_airbnb.php" class="sidebar-link"><i class="fas fa-star"></i> Upgraded Airbnb</a>
        <a href="booking_engine.php" class="sidebar-link"><i class="fas fa-hotel"></i> Booking Engine</a>
        <a href="download.php" class="sidebar-link"><i class="fas fa-file-export"></i> Export Records</a>
        <a href="settings_dashboard.php" class="sidebar-link"><i class="fas fa-user-cog"></i> Settings</a>
        <a href="contact_developer.php" class="sidebar-link" title="Contact Developer"><i class="fas fa-headset"></i> Contact Developer</a>
        <a href="logout.php" class="sidebar-link text-danger mt-3"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
    <div class="sidebar-coat-section">
        <img src="coat_of_arms.png" alt="Coat of Arms" class="sidebar-coat-img" onerror="this.style.display='none'">
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="main-content">
    
    <!-- Top Nav Header -->
    <header class="tt-header">
        <div class="font-weight-bold text-muted header-title">TIKTOK SEARCH ENGINE</div>
        <!-- Search Bar -->
        <div class="nav-search-wrap">
            <i class="fas fa-search nav-search-icon"></i>
            <input type="text" class="nav-search-input" id="nav-search" placeholder="Search by hashtag..." autocomplete="off">
        </div>
        <div class="d-flex align-items-center">
            <!-- Server Status with animations -->
            <div id="status-box" class="status-badge status-offline status-pulse">
                <div class="status-dot"></div>
                <span id="status-text">Checking System...</span>
            </div>
            <div class="mx-3 border-left" style="height: 25px;"></div>
            <div class="font-weight-bold"><i class="fas fa-user-circle"></i> Admin Panel</div>
        </div>
    </header>

    <!-- Blue Page Header -->
    <section class="tt-page-header">
        <h1 class="h3 mb-0">TikTok Engine</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent p-0 mt-2 small">
                <li class="breadcrumb-item text-white opacity-7">Home</li>
                <li class="breadcrumb-item active text-white" aria-current="page">Client Area</li>
            </ol>
        </nav>
    </section>

    <!-- KPI Tiles -->
    <div class="tiles-wrapper">
        <a href="#" class="tile-box">
            <i class="fas fa-users-cog"></i>
            <div>
                <div class="tile-stat tile-profiles" id="stat-total">0</div>
                <div class="tile-label">Profiles</div>
            </div>
        </a>
        <a href="#" class="tile-box">
            <i class="fas fa-address-card"></i>
            <div>
                <div class="tile-stat tile-contacts" id="stat-contacts">0</div>
                <div class="tile-label">Contacts</div>
            </div>
        </a>
        <a href="#" class="tile-box">
            <i class="fas fa-hashtag"></i>
            <div>
                <div class="tile-stat tile-hashtag" id="stat-active" style="font-size: 16px;">N/A</div>
                <div class="tile-label">Hashtag</div>
            </div>
        </a>
        <a href="#" class="tile-box">
            <i class="fas fa-server"></i>
            <div>
                <div class="tile-stat tile-api">API</div>
                <div class="tile-label">V2.4.0</div>
            </div>
        </a>
    </div>

    <!-- Search Extraction Card -->
    <div class="card card-custom">
        <div class="card-header-accent"><i class="fas fa-search-plus"></i> TikTok Data Extraction Engine</div>
        <div class="card-body">
            <p class="small text-muted mb-3">Enter a hashtag (without #) to fetch related posts, creators, descriptions, and detected contact numbers.</p>
            <div class="input-group">
                <input type="text" id="hashtag-input" class="form-control form-control-lg" placeholder="e.g. fashion, sneakers, dar_es_salaam" />
                <div class="input-group-append">
                    <button class="btn btn-primary px-4 font-weight-bold" id="search-btn">
                        <i class="fas fa-bolt"></i> FETCH DATA
                    </button>
                </div>
            </div>
            <div class="mt-2 d-flex align-items-center" style="gap:8px;">
                <label for="result-limit" class="small text-muted mb-0">Result Limit:</label>
                <select id="result-limit" class="form-control form-control-sm" style="max-width: 140px;">
                    <option value="100" selected>100</option>
                    <option value="150">150</option>
                    <option value="200">200</option>
                    <option value="300">300</option>
                    <option value="500">500</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="results-wrapper" id="results-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:10px;">
            <h5 class="font-weight-bold mb-0">Extracted Records</h5>
            <button class="btn btn-success btn-sm" id="save-results-btn" disabled>
                <i class="fas fa-save"></i> SAVE TO DATABASE
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Creator</th>
                        <th>Contact Number</th>
                        <th>Post Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    <!-- Loaded via JS -->
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- GLASSMORPHISM SEARCH OVERLAY -->
<div class="search-overlay" id="search-overlay">
    <div class="loader-card">
        <div class="google-spinner" role="status" aria-label="Searching"></div>
        <h4 class="loader-title">SCANNING ENGINE</h4>
        <p class="loader-text" id="loader-msg">Initializing connection to API...</p>
        <div class="progress" style="height: 4px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
        </div>
        <button class="btn btn-outline-danger btn-sm mt-3" id="cancel-search-btn" type="button" style="display:none;">
            <i class="fas fa-times-circle"></i> Cancel Search
        </button>
    </div>
</div>

<!-- Save Category Card -->
<div class="search-overlay" id="save-category-overlay" style="display:none; z-index:1200;">
    <div class="loader-card" style="max-width:460px; width:92%;">
        <h4 class="font-weight-bold mb-2"><i class="fas fa-folder-plus text-primary"></i> Save Results</h4>
        <p class="text-muted mb-3">Choose existing category or type a new category name.</p>

        <div class="form-group text-left">
            <label class="small font-weight-bold">Existing Category</label>
            <select id="save-category-select" class="form-control">
                <option value="">-- Select category --</option>
            </select>
        </div>
        <div class="text-center text-muted small mb-2">OR</div>
        <div class="form-group text-left mb-3">
            <label class="small font-weight-bold">New Category Name</label>
            <div class="input-group">
                <input type="text" id="save-category-input" class="form-control" placeholder="e.g. fashion_feb_2026">
                <div class="input-group-append">
                    <button class="btn btn-outline-primary" type="button" id="add-category-btn">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end" style="gap:8px;">
            <button class="btn btn-light border" id="cancel-save-btn">Cancel</button>
            <button class="btn btn-primary" id="confirm-save-btn">
                <i class="fas fa-database"></i> Save Now
            </button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // --- Local API Endpoint ---
    const LOCAL_API_ENDPOINT = "tiktok_search.php";
    const SAVE_ENDPOINT = "save_to_db.php";
    let latestResults = [];
    let currentSearchController = null;
    let currentSearchTimeout = null;

    function showSuccess(message) {
        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: 'Successful',
                text: message,
                confirmButtonColor: '#0b1e3b'
            });
            return;
        }
        alert(message);
    }

    function showError(message) {
        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#0b1e3b'
            });
            return;
        }
        alert(message);
    }

    function escHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function animateCount(el, target) {
        if (!el) return;
        const safeTarget = Number.isFinite(target) ? target : 0;
        const duration = 950;
        const startTs = performance.now();
        function step(now) {
            const p = Math.min((now - startTs) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            const current = Math.floor(safeTarget * eased);
            el.textContent = current.toLocaleString();
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    function setTiktokKpis(total, contacts) {
        animateCount(document.getElementById('stat-total'), Number(total) || 0);
        animateCount(document.getElementById('stat-contacts'), Number(contacts) || 0);
    }

    // 1. Check Server Connection with animated status
    async function checkStatus() {
        const box = document.getElementById('status-box');
        const text = document.getElementById('status-text');
        
        try {
            const res = await fetch(`${LOCAL_API_ENDPOINT}?action=status`, { method: 'GET' });
            if (res.ok) {
                box.classList.remove('status-offline');
                box.classList.add('status-online');
                box.classList.add('status-pulse');
                text.innerText = "Server Online";
            } else { throw new Error(); }
        } catch (e) {
            box.classList.remove('status-online');
            box.classList.add('status-offline');
            box.classList.add('status-pulse');
            text.innerText = "Server Offline";
        }
    }

    // 2. Page load animations (fade in sidebar, main, tiles, card)
    function initPageAnimations() {
        const sidebar = document.querySelector('.sidebar-wrapper');
        const main = document.querySelector('.main-content');
        const tiles = document.querySelectorAll('.tile-box');
        const card = document.querySelector('.card-custom');

        // Sidebar fade in
        requestAnimationFrame(function() {
            sidebar.classList.add('sidebar-visible');
        });

        // Main content fade in (slight delay)
        setTimeout(function() {
            main.classList.add('main-visible');
        }, 100);

        // Page header fade in
        var pageHeader = document.querySelector('.tt-page-header');
        if (pageHeader) {
            setTimeout(function() { pageHeader.classList.add('header-visible'); }, 150);
        }

        // Tiles stagger animation
        tiles.forEach(function(tile, i) {
            setTimeout(function() {
                tile.classList.add('tile-visible');
            }, 200 + (i * 80));
        });

        // Card fade in
        setTimeout(function() {
            if (card) card.classList.add('card-visible');
        }, 400);
    }

    // 2. Data Extraction
    async function performSearch() {
        const hashtagRaw = document.getElementById('hashtag-input').value.trim();
        const hashtag = hashtagRaw.replace(/^#/, '');
        if (!hashtag) return showError("Please enter a hashtag!");
        const limitInput = parseInt(document.getElementById('result-limit').value, 10);
        const fetchLimit = Number.isFinite(limitInput) ? Math.min(Math.max(limitInput, 20), 500) : 100;

        const overlay = document.getElementById('search-overlay');
        const tableBody = document.getElementById('table-body');
        const resultsBox = document.getElementById('results-wrapper');
        const loaderMsg = document.getElementById('loader-msg');
        const searchBtn = document.getElementById('search-btn');
        const cancelBtn = document.getElementById('cancel-search-btn');

        if (currentSearchController) {
            currentSearchController.abort();
            currentSearchController = null;
        }
        if (currentSearchTimeout) {
            clearTimeout(currentSearchTimeout);
            currentSearchTimeout = null;
        }
        currentSearchController = new AbortController();

        // Show Glassmorphism Overlay
        overlay.style.display = 'flex';
        cancelBtn.style.display = 'inline-block';
        searchBtn.disabled = true;
        tableBody.innerHTML = "";
        latestResults = [];
        let phonesCount = 0;

        try {
            currentSearchTimeout = setTimeout(() => {
                if (currentSearchController) currentSearchController.abort();
            }, 55000);

            const cacheBuster = '&_=' + Date.now();
            const response = await fetch(`${LOCAL_API_ENDPOINT}?hashtag=${encodeURIComponent(hashtag)}&count=50&limit=${encodeURIComponent(fetchLimit)}&cursor=0${cacheBuster}`, {
                method: "GET",
                cache: "no-store",
                signal: currentSearchController.signal
            });

            let payload = null;
            try {
                payload = await response.json();
            } catch (parseErr) {
                payload = null;
            }

            if (!response.ok) {
                const serverMsg = payload && payload.message ? payload.message : `HTTP ${response.status}`;
                throw new Error(serverMsg);
            }

            if (!payload) {
                throw new Error("Invalid response from local API.");
            }
            if (payload.debug) {
                console.warn("TikTok API debug:", payload.debug);
            }
            if (!payload.success) throw new Error(payload.message || "Request failed");

            latestResults = Array.isArray(payload.records) ? payload.records : [];
            const saveBtn = document.getElementById('save-results-btn');
            saveBtn.disabled = latestResults.length === 0;
            if (!latestResults.length) {
                loaderMsg.innerHTML = "No records found for this hashtag.";
                if (payload.debug && Array.isArray(payload.debug.attempts)) {
                    const okAttempt = payload.debug.attempts.find(a => (a.http_code >= 200 && a.http_code < 300) || (a.message && a.message !== ''));
                    if (okAttempt && okAttempt.message) {
                        loaderMsg.innerHTML = okAttempt.message;
                    }
                }
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            No records found for <strong>#${hashtag}</strong>. Try another hashtag.
                        </td>
                    </tr>
                `;
            }

            latestResults.forEach((item, idx) => {
                if (item.phone) phonesCount++;

                loaderMsg.innerHTML = `Extracting profile: <strong class='text-primary'>@${item.username || 'unknown'}</strong>`;

                const username = escHtml(item.username || 'unknown');
                const phone = escHtml(item.phone || 'N/A');
                const bioText = escHtml(item.bio ? item.bio.substring(0, 60) + '...' : 'No Description');
                const row = `
                    <tr>
                        <td data-label="Creator"><span class="table-badge badge-creator">@${username}</span></td>
                        <td data-label="Contact"><span class="table-badge badge-contact">${phone}</span></td>
                        <td data-label="Description"><span class="table-badge badge-description">${bioText}</span></td>
                        <td data-label="Action"><button class="btn btn-sm btn-outline-info" onclick="viewProfileByIndex(${idx})">View</button></td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });

            // Update KPI Stats
            setTiktokKpis(latestResults.length, phonesCount);
            document.getElementById('stat-active').innerText = `#${hashtag}`;
            resultsBox.style.display = 'block';
            requestAnimationFrame(function() {
                resultsBox.classList.add('results-visible');
            });

        } catch (err) {
            console.error(err);
            if (err.name === 'AbortError') {
                showError('Search ilikwisha muda au ume cancel. Jaribu limit ndogo (50 au 100) au jaribu tena baadaye.');
            } else {
                showError(err.message || "Server did not respond.");
            }
        } finally {
            if (currentSearchTimeout) {
                clearTimeout(currentSearchTimeout);
                currentSearchTimeout = null;
            }
            currentSearchController = null;
            searchBtn.disabled = false;
            cancelBtn.style.display = 'none';
            overlay.style.display = 'none';
        }
    }

    async function addCategoryFromModal() {
        const input = document.getElementById('save-category-input');
        const categoryName = input.value.trim();
        if (!categoryName) {
            showError('Please type a category name first.');
            return;
        }

        const addBtn = document.getElementById('add-category-btn');
        try {
            addBtn.disabled = true;
            addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

            const res = await fetch(SAVE_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create_category',
                    category_name: categoryName
                })
            });

            const result = await res.json();
            if (!res.ok || !result.success) {
                throw new Error(result.error || 'Failed to create category');
            }

            await loadCategoryOptions();
            document.getElementById('save-category-select').value = result.category_name || categoryName;
            showSuccess(result.created ? 'Category added successfully.' : 'Category already exists and was selected.');
        } catch (err) {
            console.error(err);
            showError('Could not add category: ' + (err.message || 'Unknown error'));
        } finally {
            addBtn.disabled = false;
            addBtn.innerHTML = '<i class="fas fa-plus"></i> Add Category';
        }
    }

    async function loadCategoryOptions() {
        const select = document.getElementById('save-category-select');
        select.innerHTML = '<option value="">-- Select category --</option>';

        try {
            const res = await fetch(`${SAVE_ENDPOINT}?action=list_categories`);
            const data = await res.json();
            if (data.success && Array.isArray(data.categories)) {
                data.categories.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat.category_name;
                    opt.textContent = cat.category_name;
                    select.appendChild(opt);
                });
            }
        } catch (err) {
            console.warn('Could not load categories:', err);
        }
    }

    function openSaveCategoryCard() {
        if (!latestResults.length) {
            showError('No results to save yet.');
            return;
        }
        document.getElementById('save-category-input').value = '';
        document.getElementById('save-category-select').value = '';
        loadCategoryOptions();
        document.getElementById('save-category-overlay').style.display = 'flex';
    }

    function closeSaveCategoryCard() {
        document.getElementById('save-category-overlay').style.display = 'none';
    }

    async function confirmSaveResults() {
        const inputName = document.getElementById('save-category-input').value.trim();
        const selectedName = document.getElementById('save-category-select').value.trim();
        const categoryName = inputName || selectedName;

        if (!categoryName) {
            showError('Please type category name or select existing category.');
            return;
        }

        try {
            const saveBtn = document.getElementById('confirm-save-btn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const res = await fetch(SAVE_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save_batch_category',
                    category_name: categoryName,
                    records: latestResults
                })
            });

            const result = await res.json();
            if (!res.ok || !result.success) {
                throw new Error(result.error || 'Save failed');
            }

            showSuccess(`Saved successfully in "${result.category_name}". Records: ${result.saved_category}, Duplicates: ${result.duplicates}`);
            closeSaveCategoryCard();
        } catch (err) {
            console.error(err);
            showError('Failed to save: ' + (err.message || 'Unknown error'));
        } finally {
            const saveBtn = document.getElementById('confirm-save-btn');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-database"></i> Save Now';
        }
    }

    function viewProfileByIndex(index) {
        const user = latestResults[index];
        if (user) {
            const query = `username=${encodeURIComponent(user.username || '')}&phone=${encodeURIComponent(user.phone || '')}&bio=${encodeURIComponent(user.bio || '')}`;
            window.location.href = `view_result.php?${query}`;
        }
    }

    // Nav search - sync with hashtag input or navigate
    document.getElementById('nav-search').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const q = this.value.trim();
            if (q) {
                document.getElementById('hashtag-input').value = q;
                document.getElementById('hashtag-input').focus();
            }
        }
    });

    // Mobile sidebar toggle
    (function() {
        var btn = document.getElementById('mobile-menu-btn');
        var sidebar = document.querySelector('.sidebar-wrapper');
        var overlay = document.getElementById('sidebar-overlay');
        var navLinks = document.querySelectorAll('.sidebar-link');
        if (!btn || !sidebar) return;
        function openSidebar() {
            sidebar.classList.add('sidebar-open');
            if (overlay) overlay.style.display = 'block';
            document.body.style.overflow = 'hidden';
            btn.setAttribute('aria-label', 'Close menu');
            btn.querySelector('i').className = 'fas fa-times';
        }
        function closeSidebar() {
            sidebar.classList.remove('sidebar-open');
            if (overlay) overlay.style.display = 'none';
            document.body.style.overflow = '';
            btn.setAttribute('aria-label', 'Open menu');
            btn.querySelector('i').className = 'fas fa-bars';
        }
        btn.addEventListener('click', function() {
            sidebar.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
        });
        if (overlay) overlay.addEventListener('click', closeSidebar);
        navLinks.forEach(function(link) {
            link.addEventListener('click', closeSidebar);
        });
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991) {
                closeSidebar();
            }
        });
    })();

    // Initialize
    document.getElementById('search-btn').addEventListener('click', performSearch);
    document.getElementById('save-results-btn').addEventListener('click', openSaveCategoryCard);
    document.getElementById('cancel-save-btn').addEventListener('click', closeSaveCategoryCard);
    document.getElementById('confirm-save-btn').addEventListener('click', confirmSaveResults);
    document.getElementById('add-category-btn').addEventListener('click', addCategoryFromModal);
    document.getElementById('cancel-search-btn').addEventListener('click', function() {
        if (currentSearchController) currentSearchController.abort();
    });
    document.getElementById('save-category-overlay').addEventListener('click', function(e) {
        if (e.target.id === 'save-category-overlay') closeSaveCategoryCard();
    });
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initPageAnimations();
            setTiktokKpis(0, 0);
            checkStatus();
        });
    } else {
        initPageAnimations();
        setTiktokKpis(0, 0);
        checkStatus();
    }
    setInterval(checkStatus, 30000);
</script>
<script src="js/code_protection.js"></script>
</body>
</html>