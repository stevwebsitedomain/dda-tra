<?php
require_once __DIR__ . '/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=Please Login First');
    exit();
}

$user_name = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    <title>TRA DDA | Airbnb Engine</title>

    <link rel="icon" href="dda.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="dda.jpg" type="image/jpeg">
    <link rel="apple-touch-icon" href="dda.jpg">

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
        }

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
        }
        .sidebar-wrapper::-webkit-scrollbar { display: none; }
        .sidebar-logo-section {
            padding: 15px;
            text-align: center;
            background: rgba(0, 0, 0, 0.2);
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
            background: rgba(255, 255, 255, 0.05);
            margin: 15px;
            border-radius: 8px;
            padding: 15px;
            border-left: 3px solid var(--accent-gold);
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
            background: rgba(255, 255, 255, 0.1);
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
        .api-status-icon { font-size: 8px; color: #94a3b8; }
        .api-status-icon.live { color: #22c55e; }
        .api-status-icon.offline { color: #ef4444; }
        .api-status-text { color: #94a3b8; }
        .api-status-text.live { color: #22c55e; }
        .api-status-text.offline { color: #ef4444; }
        .sidebar-coat-section { display: none !important; }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        .tt-header {
            background: #fff;
            min-height: 65px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .status-badge {
            font-size: 12px;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .status-badge.online {
            background: #e6fffa;
            border-color: #34d399;
            color: #047857;
        }
        .status-badge.offline {
            background: #fef2f2;
            border-color: #fca5a5;
            color: #b91c1c;
        }
        .location-meta {
            font-size: 11px;
            color: #64748b;
            margin-top: 6px;
        }

        .tt-page-header {
            background: var(--tra-blue-gradient);
            padding: 18px 20px 16px;
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
            letter-spacing: 0.02em;
            animation: headerGlowFloat 4s ease-in-out infinite;
        }
        .tt-page-logo {
            margin-bottom: 0;
        }
        @keyframes headerGlowFloat {
            0%, 100% { transform: translateY(0); text-shadow: 0 3px 14px rgba(6, 20, 45, 0.45); }
            50% { transform: translateY(-2px); text-shadow: 0 6px 16px rgba(6, 20, 45, 0.55); }
        }

        .tiles-wrapper {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 20px 30px;
            margin-top: 0;
        }
        .tile-box {
            background: #fff;
            padding: 16px;
            min-height: 96px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0;
            transform: translateY(12px);
            transition: opacity .45s ease, transform .45s ease;
        }
        .tile-box.tile-visible { opacity: 1; transform: translateY(0); }
        .tile-box.clickable {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .tile-box.clickable:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.11);
        }
        .tile-box i {
            font-size: 22px;
            color: var(--accent-gold);
        }
        .tile-stat {
            font-size: 22px;
            font-weight: 800;
            color: #1f2937;
            line-height: 1;
            letter-spacing: .01em;
        }
        .tile-stat.tile-category { color: #7c3aed; }
        .tile-stat.tile-records { color: #047857; }
        .tile-stat.tile-fetched { color: #0ea5e9; }
        .tile-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .card-custom {
            background: #fff;
            margin: 0 30px 20px;
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .card-header-accent {
            border-top: 3px solid var(--accent-gold);
            font-weight: 700;
            background: #fff;
            padding: 14px 20px;
        }
        .results-wrapper {
            display: none;
            margin: 0 30px 30px;
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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
        .badge-listing { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
        .badge-host { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .badge-phone { background: #f5f3ff; color: #6d28d9; border-color: #ddd6fe; }
        .badge-location { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
        .badge-neighborhood { background: #f8fafc; color: #334155; border-color: #cbd5e1; }
        .badge-coords { background: #f0f9ff; color: #0369a1; border-color: #bae6fd; text-decoration: none; }
        .badge-map-preview { background: #fff7ed; color: #c2410c; border-color: #fed7aa; text-decoration: none; margin-left: 6px; }
        .badge-price { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
        .badge-description { background: #f8fafc; color: #334155; border-color: #cbd5e1; font-weight: 600; }
        .direction-note { font-size: 11px; color: #64748b; margin-top: 4px; }
        .results-row-animate { opacity: 0; transform: translateY(8px); animation: rowFadeIn .32s ease forwards; }
        @keyframes rowFadeIn { to { opacity: 1; transform: translateY(0); } }
        .results-wrapper thead th {
            border-top: 0;
            color: #0f172a;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            background: #f8fafc;
            white-space: nowrap;
        }
        .results-wrapper thead th:nth-child(1) { color: #6d28d9; }
        .results-wrapper thead th:nth-child(2) { color: #0369a1; }
        .results-wrapper thead th:nth-child(3) { color: #7c2d12; }
        .results-wrapper thead th:nth-child(4) { color: #15803d; }
        .results-wrapper thead th:nth-child(5) { color: #0f766e; }
        .results-wrapper thead th:nth-child(6) { color: #1d4ed8; }
        .results-wrapper thead th:nth-child(7) { color: #b45309; }
        .results-wrapper thead th:nth-child(8) { color: #475569; }
        .results-wrapper thead th:nth-child(9) { color: #b91c1c; }

        .search-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.42);
            backdrop-filter: blur(10px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .loader-card {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
            width: 92%;
            max-width: 480px;
            text-align: center;
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
            margin-bottom: 0;
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
        @keyframes googleSpin {
            100% { transform: rotate(360deg); }
        }
        @keyframes loaderTextPulse {
            0%, 100% { opacity: 0.75; }
            50% { opacity: 1; }
        }
        @keyframes loaderTitleGlow {
            0%, 100% { filter: drop-shadow(0 0 0 rgba(26, 115, 232, 0)); }
            50% { filter: drop-shadow(0 2px 8px rgba(26, 115, 232, 0.25)); }
        }

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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        @media (max-width: 991px) {
            .mobile-menu-btn { display: flex; }
            .sidebar-wrapper {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 280px;
            }
            .sidebar-wrapper.sidebar-open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .tt-header { padding: 10px 15px 10px 60px; gap: 10px; }
            .tt-page-header { padding: 16px 12px 12px; }
            .tiles-wrapper {
                grid-template-columns: repeat(2, 1fr);
                padding: 15px;
                margin-top: 0;
            }
            .tile-box { min-height: 110px; padding: 18px; }
            .tile-stat { font-size: 26px; }
            .card-custom { margin: 0 15px 15px; }
            .results-wrapper { margin: 0 15px 20px; padding: 15px; }
        }

        @media (max-width: 767px) {
            .tiles-wrapper { grid-template-columns: 1fr; }
        }

        @media (max-width: 767px) {
            .results-wrapper .table thead { display: none; }
            .results-wrapper .table,
            .results-wrapper .table tbody,
            .results-wrapper .table tr,
            .results-wrapper .table td { display: block; }
            .results-wrapper .table tr {
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                margin-bottom: 12px;
                padding: 12px;
            }
            .results-wrapper .table td {
                border: none;
                display: flex;
                gap: 8px;
                padding: 7px 0;
            }
            .results-wrapper .table td::before {
                content: attr(data-label);
                min-width: 95px;
                font-weight: 700;
                color: #64748b;
                font-size: 12px;
            }
            .results-wrapper .table td:last-child {
                padding-top: 10px;
                border-top: 1px solid #f1f5f9;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
<button type="button" class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Open menu">
    <i class="fas fa-bars"></i>
</button>
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
        <a href="airbnb_engine.php" class="sidebar-link active"><i class="fab fa-airbnb"></i> Airbnb Engine</a>
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

<main class="main-content">
    <header class="tt-header">
        <div class="font-weight-bold text-muted">AIRBNB DATA ACQUISITION PORTAL</div>
        <div class="d-flex flex-column align-items-end">
            <div id="status-box" class="status-badge offline">
                <i class="fas fa-circle-notch"></i>
                <span id="status-text">Checking service...</span>
            </div>
            <div id="user-location-label" class="location-meta">Your location: detecting...</div>
        </div>
    </header>

    <section class="tt-page-header">
        <img
            src="uploads/airbnb2.png"
            alt="Airbnb Logo"
            class="tt-page-logo airbnb-header-logo"
            onerror="this.style.display='none'">
    </section>

    <div class="tiles-wrapper">
        <button type="button" class="tile-box border-0 text-left">
            <i class="fas fa-download"></i>
            <div>
                <div class="tile-stat tile-fetched" id="airbnb-fetched-count">0</div>
                <div class="tile-label">Fetched This Search</div>
            </div>
        </button>
        <button type="button" class="tile-box clickable border-0 text-left" id="airbnb-categories-card">
            <i class="fas fa-tags"></i>
            <div>
                <div class="tile-stat tile-category" id="airbnb-cat-count">0</div>
                <div class="tile-label">Airbnb Categories</div>
            </div>
        </button>
        <button type="button" class="tile-box clickable border-0 text-left" id="airbnb-records-card">
            <i class="fas fa-list-ul"></i>
            <div>
                <div class="tile-stat tile-records" id="airbnb-records-count">0</div>
                <div class="tile-label">Total Saved Airbnb Lists</div>
            </div>
        </button>
    </div>

    <div class="card card-custom">
        <div class="card-header-accent"><i class="fas fa-magnifying-glass mr-2"></i> Airbnb Search</div>
        <div class="card-body">
            <p class="small text-muted mb-3">
                Andika location, listing id, au Airbnb room URL (mfano: <strong>dar es salaam</strong>, <strong>41684233</strong>, <strong>https://www.airbnb.com/rooms/1066230</strong>) kisha bonyeza Search.
            </p>
            <div class="input-group">
                <input type="text" id="airbnb-query" class="form-control form-control-lg" placeholder="e.g. dar es salaam, 41684233, https://www.airbnb.com/rooms/1066230">
                <div class="input-group-append">
                    <button class="btn btn-primary px-4 font-weight-bold" id="airbnb-search-btn">
                        <i class="fas fa-search"></i> SEARCH
                    </button>
                </div>
            </div>
            <div class="mt-2 d-flex align-items-center" style="gap:8px;">
                <label for="airbnb-limit" class="small text-muted mb-0">Result Limit:</label>
                <select id="airbnb-limit" class="form-control form-control-sm" style="max-width: 140px;">
                    <option value="10">10</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                    <option value="300">300</option>
                    <option value="600">600</option>
                    <option value="1000">1000</option>
                    <option value="3000">3000</option>
                    <option value="6000">6000</option>
                    <option value="10000">10000</option>
                </select>
            </div>
        </div>
    </div>

    <div class="results-wrapper airbnb-results-watermark" id="results-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:10px;">
            <h5 class="font-weight-bold mb-0">Airbnb Listing Results</h5>
            <button class="btn btn-success btn-sm" id="save-results-btn" disabled>
                <i class="fas fa-save"></i> SAVE TO CATEGORY
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><i class="fas fa-house mr-1"></i> Listing</th>
                        <th><i class="fas fa-user mr-1"></i> Host</th>
                        <th><i class="fas fa-phone mr-1"></i> Host Phone</th>
                        <th><i class="fas fa-map-marker-alt mr-1"></i> Location</th>
                        <th><i class="fas fa-location-arrow mr-1"></i> Neighborhood</th>
                        <th><i class="fas fa-route mr-1"></i> Lat/Lng</th>
                        <th><img src="uploads/price.jpg" alt="Price" class="price-icon-img mr-1"> Price</th>
                        <th><i class="fas fa-align-left mr-1"></i> Description</th>
                        <th><i class="fas fa-bolt mr-1"></i> Action</th>
                    </tr>
                </thead>
                <tbody id="results-body"></tbody>
            </table>
        </div>
    </div>
</main>

<div class="search-overlay" id="search-overlay">
    <div class="loader-card">
        <div class="google-spinner" role="status" aria-label="Searching"></div>
        <h4 class="loader-title">AIRBNB ENGINE</h4>
        <p class="loader-text" id="loader-msg">Searching Airbnb listings...</p>
    </div>
</div>

<div class="search-overlay" id="save-category-overlay" style="z-index: 1200;">
    <div class="loader-card" style="max-width:460px; text-align:left;">
        <h4 class="font-weight-bold mb-2"><i class="fas fa-folder-plus text-primary"></i> Save Airbnb Results</h4>
        <p class="text-muted mb-3">Select existing category or create a new one.</p>

        <div class="form-group">
            <label class="small font-weight-bold">Existing Category</label>
            <select id="save-category-select" class="form-control">
                <option value="">-- Select category --</option>
            </select>
        </div>

        <div class="text-center text-muted small mb-2">OR</div>
        <div class="form-group mb-3">
            <label class="small font-weight-bold">New Category Name</label>
            <div class="input-group">
                <input type="text" id="save-category-input" class="form-control" placeholder="e.g. airbnb_zanzibar_feb_2026">
                <div class="input-group-append">
                    <button class="btn btn-outline-primary" type="button" id="add-category-btn">
                        <i class="fas fa-plus"></i> Add
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const AIRBNB_SEARCH_ENDPOINT = 'airbnb_search.php';
const SAVE_ENDPOINT = 'save_to_db.php';
let latestResults = [];
const locationCache = new Map();
let userOriginLabel = 'My Location';
let userOriginCoords = null;

function animateCount(el, target) {
    const duration = 1000;
    const startTs = performance.now();
    function step(now) {
        const p = Math.min((now - startTs) / duration, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.floor(target * eased).toLocaleString();
        if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

async function checkStatus() {
    const box = document.getElementById('status-box');
    const text = document.getElementById('status-text');
    try {
        const res = await fetch(`${AIRBNB_SEARCH_ENDPOINT}?action=status`);
        if (!res.ok) throw new Error('offline');
        box.classList.remove('offline');
        box.classList.add('online');
        text.textContent = 'Airbnb API Online';
    } catch (e) {
        box.classList.remove('online');
        box.classList.add('offline');
        text.textContent = 'Airbnb API Offline';
    }
}

async function detectUserLocation() {
    const label = document.getElementById('user-location-label');
    if (!navigator.geolocation) {
        if (label) label.textContent = 'Your location: browser geolocation not supported.';
        return;
    }
    navigator.geolocation.getCurrentPosition(async (pos) => {
        const lat = pos.coords?.latitude;
        const lng = pos.coords?.longitude;
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            if (label) label.textContent = 'Your location: unable to read coordinates.';
            return;
        }
        userOriginCoords = { lat, lng };
        userOriginLabel = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        if (label) label.textContent = `Your location: ${userOriginLabel}`;
        try {
            const reverseUrl = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=14&addressdetails=1`;
            const res = await fetch(reverseUrl);
            if (!res.ok) return;
            const data = await res.json();
            const a = data.address || {};
            const place = [a.city || a.town || a.village || a.municipality || '', a.state || ''].filter(Boolean).join(', ');
            if (place) {
                userOriginLabel = place;
                if (label) label.textContent = `Your location: ${place}`;
            }
        } catch (e) {}
    }, () => {
        if (label) label.textContent = 'Your location: permission denied, using default.';
    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
}

function buildDirectionUrl(lat, lng, listingName = 'Airbnb Listing') {
    const destination = `${Number(lat).toFixed(6)},${Number(lng).toFixed(6)}`;
    if (userOriginCoords && Number.isFinite(userOriginCoords.lat) && Number.isFinite(userOriginCoords.lng)) {
        const origin = `${userOriginCoords.lat},${userOriginCoords.lng}`;
        return `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}&travelmode=driving&dir_action=navigate`;
    }
    return `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destination)}&travelmode=driving&dir_action=navigate`;
}

function buildMapPreviewUrl(lat, lng, listingName = 'Airbnb Listing') {
    const latFixed = Number(lat).toFixed(6);
    const lngFixed = Number(lng).toFixed(6);
    return `https://www.google.com/maps/@?api=1&map_action=map&center=${encodeURIComponent(`${latFixed},${lngFixed}`)}&zoom=18&basemap=satellite`;
}

function isValidCoordinates(lat, lng) {
    const la = Number(lat);
    const lo = Number(lng);
    return Number.isFinite(la) && Number.isFinite(lo) && la >= -90 && la <= 90 && lo >= -180 && lo <= 180;
}

async function loadAirbnbStats() {
    try {
        const res = await fetch(`${SAVE_ENDPOINT}?action=airbnb_stats`);
        const data = await res.json();
        if (res.ok && data.success && data.stats) {
            animateCount(document.getElementById('airbnb-cat-count'), Number(data.stats.airbnb_categories || 0));
            animateCount(document.getElementById('airbnb-records-count'), Number(data.stats.airbnb_records || 0));
        }
    } catch (e) {
        console.warn('Failed to load Airbnb stats', e);
    }
}

function renderResults(results) {
    const tbody = document.getElementById('results-body');
    tbody.innerHTML = '';

    if (!results.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center text-muted py-4">No listings found for this search.</td>
            </tr>
        `;
        return;
    }

    results.forEach((item, index) => {
        const safeUrl = item.url ? item.url : '#';
        const desc = item.description ? String(item.description).slice(0, 150) : 'N/A';
        const hasCoords = isValidCoordinates(item.lat, item.lng);
        const coordsText = hasCoords ? `${item.lat}, ${item.lng}` : 'N/A';
        const mapUrl = hasCoords ? buildDirectionUrl(item.lat, item.lng, item.name || item.listing_id || 'Airbnb Listing') : '#';
        const previewUrl = hasCoords ? buildMapPreviewUrl(item.lat, item.lng, item.name || item.listing_id || 'Airbnb Listing') : '#';
        const fallbackLocation = [
            (item.city || '').trim(),
            (item.neighborhood || item.location_name || '').trim()
        ].filter(Boolean).join(', ') || 'N/A';
        const row = `
            <tr class="results-row-animate" style="animation-delay:${Math.min(index * 30, 450)}ms;">
                <td data-label="Listing"><span class="table-badge badge-listing">${escapeHtml(item.name || 'N/A')}</span></td>
                <td data-label="Host"><span class="table-badge badge-host">${escapeHtml(item.host_name || 'N/A')}</span></td>
                <td data-label="Host Phone"><span class="table-badge badge-phone">${escapeHtml(item.host_phone || item.phone || 'N/A')}</span></td>
                <td data-label="Location">
                    <div><span class="table-badge badge-location" id="geo-location-main-${index}">${escapeHtml(fallbackLocation)}</span></div>
                    <div class="small text-muted mt-1" id="geo-location-sub-${index}">
                        ${hasCoords ? 'Inatafuta location halisi...' : 'Coordinates si sahihi kwa geocode.'}
                    </div>
                </td>
                <td data-label="Neighborhood"><span class="table-badge badge-neighborhood" id="geo-neighborhood-${index}">${escapeHtml(item.neighborhood || 'N/A')}</span></td>
                <td data-label="Lat/Lng">
                    ${hasCoords
                        ? `<a class="table-badge badge-coords" href="${mapUrl}" target="_blank" rel="noopener" title="Open directions">${escapeHtml(coordsText)}</a>
                           <a class="table-badge badge-map-preview" href="${previewUrl}" target="_blank" rel="noopener" title="Preview house with directions"><i class="fas fa-location-dot mr-1"></i>Preview House</a>
                           <div class="direction-note">Direction from: ${escapeHtml(userOriginLabel)} to ${escapeHtml(item.name || 'listing')}</div>`
                        : `<span class="table-badge badge-neighborhood">N/A</span>`}
                </td>
                <td data-label="Price"><span class="table-badge badge-price">${escapeHtml(item.room_price || item.price || 'N/A')}</span></td>
                <td data-label="Description"><span class="table-badge badge-description">${escapeHtml(desc)}</span></td>
                <td data-label="Action">
                    <a class="btn btn-sm btn-outline-info" href="${encodeURI(safeUrl)}" target="_blank" rel="noopener">Open</a>
                </td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
    });

    enrichDetailedLocations(results);
}

function safeAddressPart(value) {
    return String(value || '').trim();
}

function buildDetailedLocationFromAddress(address) {
    const region = safeAddressPart(
        address.state ||
        address.region ||
        address.province ||
        address.state_district
    );
    const district = safeAddressPart(
        address.county ||
        address.city_district ||
        address.municipality ||
        address.city ||
        address.town
    );
    const street = safeAddressPart(
        address.suburb ||
        address.neighbourhood ||
        address.neighborhood ||
        address.road ||
        address.residential ||
        address.hamlet ||
        address.village
    );

    return {
        region,
        district,
        street,
        full: [region, district, street].filter(Boolean).join(', ')
    };
}

async function reverseGeocodeFromLatLng(lat, lng) {
    const key = `${lat},${lng}`;
    if (locationCache.has(key)) return locationCache.get(key);

    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&accept-language=sw,en`;
    const ctl = new AbortController();
    const t = setTimeout(() => ctl.abort(), 10000);
    let res;
    try {
        res = await fetch(url, { method: 'GET', signal: ctl.signal });
    } finally {
        clearTimeout(t);
    }
    if (!res.ok) {
        throw new Error(`Geocode HTTP ${res.status}`);
    }
    const data = await res.json();
    const detailed = buildDetailedLocationFromAddress(data.address || {});
    locationCache.set(key, detailed);
    return detailed;
}

async function enrichLocationItem(item, index) {
    const hasCoords = isValidCoordinates(item.lat, item.lng);
    if (!hasCoords) return;

    const mainEl = document.getElementById(`geo-location-main-${index}`);
    const subEl = document.getElementById(`geo-location-sub-${index}`);
    const hoodEl = document.getElementById(`geo-neighborhood-${index}`);
    if (!mainEl) return;

    try {
        const detailed = await reverseGeocodeFromLatLng(item.lat, item.lng);
        if (detailed.full) {
            mainEl.textContent = detailed.full;
        }
        const originalNeighborhood = String(item.neighborhood || '').trim();
        if (subEl) {
            subEl.textContent = detailed.full
                ? `Mkoa: ${detailed.region || 'N/A'} | Wilaya: ${detailed.district || 'N/A'} | Neighborhood: ${originalNeighborhood || detailed.district || 'N/A'} | Mtaa: ${detailed.street || 'N/A'}`
                : 'Location details hazikupatikana kikamilifu.';
        }

        if (hoodEl) {
            hoodEl.textContent = [detailed.district, detailed.street].filter(Boolean).join(' / ') || detailed.street || detailed.district || 'N/A';
        }

        // Improve saved record quality using resolved location.
        if (detailed.region) item.location_name = detailed.region;
        item.city = detailed.region || item.city || '';
        item.neighborhood = [originalNeighborhood, detailed.street].filter(Boolean).join(' / ') || detailed.street || detailed.district || item.neighborhood || '';
    } catch (e) {
        if (subEl) subEl.textContent = 'Imeshindwa kupata location halisi kutoka Lat/Lng.';
    }
}

async function enrichDetailedLocations(results) {
    const batchSize = 3;
    for (let i = 0; i < results.length; i += batchSize) {
        const batch = results.slice(i, i + batchSize);
        await Promise.all(batch.map((item, offset) => enrichLocationItem(item, i + offset)));
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function performAirbnbSearch() {
    const query = document.getElementById('airbnb-query').value.trim();
    if (!query) {
        Swal.fire({ icon: 'warning', title: 'Input required', text: 'Please enter hashtag/location first.' });
        return;
    }
    const limit = parseInt(document.getElementById('airbnb-limit').value, 10) || 50;

    const overlay = document.getElementById('search-overlay');
    const resultsWrap = document.getElementById('results-wrapper');
    const saveBtn = document.getElementById('save-results-btn');
    overlay.style.display = 'flex';
    saveBtn.disabled = true;
    latestResults = [];

    try {
        const res = await fetch(`${AIRBNB_SEARCH_ENDPOINT}?query=${encodeURIComponent(query)}&limit=${encodeURIComponent(limit)}`);
        const rawText = await res.text();
        let data = null;
        try {
            data = JSON.parse(rawText);
        } catch (parseErr) {
            throw new Error('Server returned incomplete response. Try smaller limit (e.g. 50/100) or retry.');
        }

        if (!res.ok || !data.success) {
            let serverMsg = data.message || 'Search failed';
            if (data.debug && Array.isArray(data.debug.attempts)) {
                const firstFailed = data.debug.attempts.find((a) => (a.http_code && a.http_code >= 400) || (a.error && a.error !== ''));
                if (firstFailed) {
                    const codePart = firstFailed.http_code ? `HTTP ${firstFailed.http_code}` : '';
                    const errPart = firstFailed.error ? ` - ${firstFailed.error}` : '';
                    serverMsg += ` (${codePart}${errPart})`;
                }
            }
            throw new Error(serverMsg);
        }

        latestResults = Array.isArray(data.records) ? data.records : [];
        renderResults(latestResults);
        animateCount(document.getElementById('airbnb-fetched-count'), latestResults.length);
        saveBtn.disabled = latestResults.length === 0;
        resultsWrap.style.display = 'block';
        if (latestResults.length > 0) {
            Swal.fire({
                icon: 'success',
                title: 'Search complete',
                text: `${latestResults.length} listings found.`,
                timer: 1400,
                showConfirmButton: false
            });
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Search failed', text: err.message || 'Airbnb service failed.' });
    } finally {
        overlay.style.display = 'none';
    }
}

async function loadCategoryOptions() {
    const select = document.getElementById('save-category-select');
    select.innerHTML = '<option value="">-- Select category --</option>';
    try {
        const res = await fetch(`${SAVE_ENDPOINT}?action=list_categories`);
        const data = await res.json();
        if (res.ok && data.success && Array.isArray(data.categories)) {
            data.categories.forEach((cat) => {
                const opt = document.createElement('option');
                opt.value = cat.category_name;
                opt.textContent = cat.category_name;
                select.appendChild(opt);
            });
        }
    } catch (e) {
        console.warn(e);
    }
}

async function openSaveCategoryModal() {
    if (!latestResults.length) {
        Swal.fire({ icon: 'info', title: 'No data', text: 'Search first before saving.' });
        return;
    }

    let categories = [];
    try {
        const res = await fetch(`${SAVE_ENDPOINT}?action=list_categories`);
        const data = await res.json();
        if (res.ok && data.success && Array.isArray(data.categories)) {
            categories = data.categories.map((c) => c.category_name);
        }
    } catch (e) {
        console.warn(e);
    }

    const optionsHtml = categories.map((name) => `<option value="${escapeHtml(name)}">${escapeHtml(name)}</option>`).join('');
    const result = await Swal.fire({
        title: 'Save Airbnb Results',
        html: `
            <div style="text-align:left;">
                <label style="font-weight:600;font-size:13px;">Select Existing Category</label>
                <select id="swal-category-select" class="swal2-input" style="margin:8px 0 12px;">
                    <option value="">-- Select category --</option>
                    ${optionsHtml}
                </select>
                <div style="text-align:center;color:#64748b;font-size:12px;margin:4px 0;">OR</div>
                <label style="font-weight:600;font-size:13px;">Create New Category</label>
                <input id="swal-category-new" class="swal2-input" placeholder="e.g. airbnb_zanzibar_feb_2026">
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const selected = (document.getElementById('swal-category-select')?.value || '').trim();
            const created = (document.getElementById('swal-category-new')?.value || '').trim();
            const category = created || selected;
            if (!category) {
                Swal.showValidationMessage('Please select or enter category name.');
                return false;
            }
            return category;
        }
    });
    if (!result.isConfirmed) {
        return;
    }

    const categoryName = result.value;

    // Send data compatible with save_to_db.php schema.
    const savePayload = latestResults.map((item) => ({
        username: item.username || (`airbnb_${item.listing_id || Math.random().toString(36).slice(2, 10)}`),
        phone: item.host_phone || item.phone || '',
        bio: item.bio || `${item.description || item.name || ''} | Host: ${item.host_name || 'N/A'} | Host Phone: ${item.host_phone || item.phone || 'N/A'} | ${item.city || ''} | ${item.neighborhood || ''} | ${item.lat != null && item.lng != null ? `${item.lat},${item.lng}` : ''} | ${item.room_price || item.price || ''}`,
        listing_id: item.listing_id || '',
        listing_url: item.url || '',
        host_name: item.host_name || '',
        host_phone: item.host_phone || item.phone || '',
        location_name: item.city || '',
        neighborhood: item.neighborhood || '',
        latitude: item.lat != null ? String(item.lat) : '',
        longitude: item.lng != null ? String(item.lng) : '',
        room_price: item.room_price || item.price || '',
        description: item.description || item.name || '',
        source: 'airbnb'
    }));

    try {
        Swal.fire({
            title: 'Saving...',
            text: 'Please wait while saving records',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const res = await fetch(SAVE_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'save_batch_category',
                category_name: categoryName,
                records: savePayload
            })
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.error || 'Save failed');
        }
        await Swal.fire({
            icon: 'success',
            title: 'Saved successfully',
            html: `Category: <b>${escapeHtml(data.category_name)}</b><br>Saved: <b>${Number(data.saved_category || 0)}</b><br>Duplicates: <b>${Number(data.duplicates || 0)}</b>`
        });
        await loadAirbnbStats();
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Save error', text: err.message || 'Unknown error' });
    }
}

async function showAirbnbCategoriesCardDetails() {
    try {
        const res = await fetch(`${SAVE_ENDPOINT}?action=list_airbnb_categories`);
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.error || 'Failed to load categories');
        }
        const rows = Array.isArray(data.categories) ? data.categories : [];
        if (!rows.length) {
            Swal.fire({ icon: 'info', title: 'No Airbnb categories yet', text: 'Save Airbnb results first to create category data.' });
            return;
        }
        const htmlRows = rows.map((r) => `
            <tr>
                <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;">${escapeHtml(r.category_name || 'N/A')}</td>
                <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;text-align:right;">${Number(r.total_rows || 0).toLocaleString()}</td>
            </tr>
        `).join('');
        Swal.fire({
            title: 'Airbnb Categories',
            width: 640,
            html: `
                <div style="max-height:360px;overflow:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr>
                                <th style="text-align:left;padding:8px;border-bottom:2px solid #e2e8f0;">Category</th>
                                <th style="text-align:right;padding:8px;border-bottom:2px solid #e2e8f0;">Records</th>
                            </tr>
                        </thead>
                        <tbody>${htmlRows}</tbody>
                    </table>
                </div>
            `
        });
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Failed to load Airbnb categories.' });
    }
}

async function showAirbnbRecordsCardDetails() {
    try {
        const res = await fetch(`${SAVE_ENDPOINT}?action=list_airbnb_records&limit=80`);
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.error || 'Failed to load records');
        }
        const rows = Array.isArray(data.records) ? data.records : [];
        if (!rows.length) {
            Swal.fire({ icon: 'info', title: 'No saved records', text: 'Hakuna records za Airbnb zilizohifadhiwa bado.' });
            return;
        }
        const htmlRows = rows.map((r) => `
            <tr>
                <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;">${escapeHtml(r.category_name || 'N/A')}</td>
                <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;">${escapeHtml(r.username || '')}</td>
                <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;">${escapeHtml(r.host_name || 'N/A')}</td>
                <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;">${escapeHtml(r.host_phone || r.phone || 'N/A')}</td>
                <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;">${escapeHtml(r.location_name || 'N/A')}</td>
                <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;">${escapeHtml((r.latitude && r.longitude) ? `${r.latitude},${r.longitude}` : 'N/A')}</td>
                <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;">${escapeHtml(r.room_price || 'N/A')}</td>
            </tr>
        `).join('');
        Swal.fire({
            title: 'Recent Saved Airbnb Lists',
            width: 920,
            html: `
                <div style="max-height:420px;overflow:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:12px;">
                        <thead>
                            <tr>
                                <th style="text-align:left;padding:8px;border-bottom:2px solid #e2e8f0;">Category</th>
                                <th style="text-align:left;padding:8px;border-bottom:2px solid #e2e8f0;">Record</th>
                                <th style="text-align:left;padding:8px;border-bottom:2px solid #e2e8f0;">Host</th>
                                <th style="text-align:left;padding:8px;border-bottom:2px solid #e2e8f0;">Host Phone</th>
                                <th style="text-align:left;padding:8px;border-bottom:2px solid #e2e8f0;">Location</th>
                                <th style="text-align:left;padding:8px;border-bottom:2px solid #e2e8f0;">Lat/Lng</th>
                                <th style="text-align:left;padding:8px;border-bottom:2px solid #e2e8f0;">Price</th>
                            </tr>
                        </thead>
                        <tbody>${htmlRows}</tbody>
                    </table>
                </div>
            `
        });
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Failed to load saved Airbnb records.' });
    }
}

(function setupMobileSidebar() {
    const btn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar-wrapper');
    const overlay = document.getElementById('sidebar-overlay');
    if (!btn || !sidebar || !overlay) return;

    function openSidebar() {
        sidebar.classList.add('sidebar-open');
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
        btn.setAttribute('aria-label', 'Close menu');
        btn.querySelector('i').className = 'fas fa-times';
    }

    function closeSidebar() {
        sidebar.classList.remove('sidebar-open');
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        btn.setAttribute('aria-label', 'Open menu');
        btn.querySelector('i').className = 'fas fa-bars';
    }

    btn.addEventListener('click', function() {
        if (sidebar.classList.contains('sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });
    overlay.addEventListener('click', closeSidebar);
})();

document.getElementById('airbnb-search-btn').addEventListener('click', performAirbnbSearch);
document.getElementById('save-results-btn').addEventListener('click', openSaveCategoryModal);
document.getElementById('airbnb-categories-card').addEventListener('click', showAirbnbCategoriesCardDetails);
document.getElementById('airbnb-records-card').addEventListener('click', showAirbnbRecordsCardDetails);

(function initTilesAnimation() {
    const tiles = document.querySelectorAll('.tile-box');
    tiles.forEach((tile, idx) => setTimeout(() => tile.classList.add('tile-visible'), 120 + idx * 70));
})();
document.getElementById('airbnb-query').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        performAirbnbSearch();
    }
});

checkStatus();
loadAirbnbStats();
detectUserLocation();
setInterval(checkStatus, 30000);
</script>
<script src="js/code_protection.js"></script>
</body>
</html>

