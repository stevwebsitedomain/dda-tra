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
    <title>TRA DDA | Airbnb 2</title>

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
            --sidebar-width: 260px;
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
            gap: 16px;
        }
        .nav-search-wrap {
            flex: 1;
            max-width: 420px;
            position: relative;
        }
        .nav-search-input {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            background: #f8fafc;
            font-size: 14px;
            padding: 10px 18px 10px 42px;
            outline: none;
        }
        .nav-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
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
        @keyframes headerGlowFloat {
            0%, 100% { transform: translateY(0); text-shadow: 0 3px 14px rgba(6, 20, 45, 0.45); }
            50% { transform: translateY(-2px); text-shadow: 0 6px 16px rgba(6, 20, 45, 0.55); }
        }
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 16px;
            opacity: 0;
            transform: translateY(12px);
            transition: opacity .45s ease, transform .45s ease;
        }
        .tile-box.tile-visible { opacity: 1; transform: translateY(0); }
        .tile-box i {
            font-size: 28px;
            color: var(--accent-gold);
        }
        .tile-stat {
            font-size: 30px;
            font-weight: 800;
            color: #1f2937;
            line-height: 1;
            letter-spacing: .01em;
        }
        .tile-stat.tile-listings { color: #0f766e; }
        .tile-stat.tile-hosts { color: #1d4ed8; }
        .tile-stat.tile-stars { color: #b45309; }
        .tile-stat.tile-reviews { color: #7c3aed; }
        .tile-label {
            font-size: 11px;
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
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
            border: 1px solid transparent;
            max-width: 100%;
            word-break: break-word;
        }
        .badge-listing { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .badge-host { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .badge-phone { background: #f5f3ff; color: #6d28d9; border-color: #ddd6fe; }
        .badge-location { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
        .badge-price { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
        .badge-stars { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .badge-reviews { background: #f0f9ff; color: #0369a1; border-color: #bae6fd; }
        .badge-guest { background: #f8fafc; color: #334155; border-color: #cbd5e1; }
        .badge-availability { background: #fdf2f8; color: #be185d; border-color: #fbcfe8; }
        .badge-images { background: #eef2ff; color: #4338ca; border-color: #c7d2fe; }
        .results-wrapper .table thead th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 800;
            white-space: nowrap;
            border-top: none;
            border-bottom: 1px solid #e2e8f0;
        }
        .th-listing { color: #047857; }
        .th-host { color: #1d4ed8; }
        .th-location { color: #c2410c; }
        .th-price { color: #15803d; }
        .th-stars { color: #b45309; }
        .th-reviews { color: #0369a1; }
        .th-guest { color: #334155; }
        .th-availability { color: #be185d; }
        .th-images { color: #4338ca; }
        .th-action { color: #0f766e; }
        .map-direction-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #0f766e;
            text-decoration: none;
        }
        .map-direction-link:hover {
            color: #0d9488;
            text-decoration: underline;
        }
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
        @keyframes googleSpin { 100% { transform: rotate(360deg); } }
        @keyframes loaderTextPulse { 0%, 100% { opacity: 0.75; } 50% { opacity: 1; } }
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
                width: 260px;
            }
            .sidebar-wrapper.sidebar-open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .tt-header { padding: 10px 15px 10px 60px; gap: 10px; flex-wrap: wrap; }
            .nav-search-wrap { max-width: none; width: 100%; order: 3; }
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
            .tiles-wrapper {
                grid-template-columns: 1fr;
            }
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
                min-width: 105px;
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
        <a href="airbnb_engine.php" class="sidebar-link"><i class="fab fa-airbnb"></i> Airbnb Engine</a>
        <a href="airbnb_realtime.php" class="sidebar-link"><i class="fas fa-bolt"></i> Airbnb Real Time</a>
        <a href="airbnb2_engine.php" class="sidebar-link active"><i class="fab fa-airbnb"></i> Airbnb 2</a>
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
        <div class="font-weight-bold text-muted">AIRBNB 2 SCRAPER PORTAL</div>
        <div class="nav-search-wrap">
            <i class="fas fa-search nav-search-icon"></i>
            <input type="text" id="nav-search" class="nav-search-input" placeholder="Search location, city, or area...">
        </div>
        <div id="status-box" class="status-badge offline">
            <i class="fas fa-circle-notch"></i>
            <span id="status-text">Checking service...</span>
        </div>
    </header>

    <section class="tt-page-header">
        <img src="uploads/airbnb2.png" alt="Airbnb Logo" class="airbnb-header-logo" onerror="this.style.display='none'">
    </section>

    <div class="tiles-wrapper">
        <div class="tile-box">
            <i class="fas fa-list"></i>
            <div>
                <div class="tile-stat tile-listings" id="stat-total">0</div>
                <div class="tile-label">Listings</div>
            </div>
        </div>
        <div class="tile-box">
            <i class="fas fa-user-friends"></i>
            <div>
                <div class="tile-stat tile-hosts" id="stat-hosts">0</div>
                <div class="tile-label">Unique Hosts</div>
            </div>
        </div>
        <div class="tile-box">
            <i class="fas fa-star"></i>
            <div>
                <div class="tile-stat tile-stars" id="stat-stars">0.00</div>
                <div class="tile-label">Average Stars</div>
            </div>
        </div>
        <div class="tile-box">
            <i class="fas fa-comments"></i>
            <div>
                <div class="tile-stat tile-reviews" id="stat-reviews">0</div>
                <div class="tile-label">With Reviews</div>
            </div>
        </div>
    </div>

    <div class="card card-custom">
        <div class="card-header-accent"><i class="fas fa-magnifying-glass mr-2"></i> Airbnb 2 Search</div>
        <div class="card-body">
            <p class="small text-muted mb-3">
                Enter a location (example: <strong>Dar es Salaam</strong>, <strong>Zanzibar</strong>, <strong>Nairobi</strong>), then click Search.
            </p>
            <div class="input-group">
                <input type="text" id="airbnb2-query" class="form-control form-control-lg" placeholder="e.g. Dar es Salaam">
                <div class="input-group-append">
                    <button class="btn btn-primary px-4 font-weight-bold" id="airbnb2-search-btn">
                        <i class="fas fa-search"></i> SEARCH
                    </button>
                </div>
            </div>
            <div class="mt-2 d-flex align-items-center" style="gap:8px;">
                <label for="airbnb2-limit" class="small text-muted mb-0">Result Limit:</label>
                <select id="airbnb2-limit" class="form-control form-control-sm" style="max-width: 140px;">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="150">150</option>
                    <option value="300">300</option>
                </select>
            </div>
        </div>
    </div>

    <div class="results-wrapper airbnb-results-watermark" id="results-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:10px;">
            <h5 class="font-weight-bold mb-0">Airbnb 2 Results</h5>
            <button class="btn btn-success btn-sm" id="save-results-btn" disabled>
                <i class="fas fa-save"></i> SAVE TO CATEGORY
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th class="th-listing"><i class="fas fa-building mr-1"></i>Listing</th>
                        <th class="th-host"><i class="fas fa-user-tie mr-1"></i>Host</th>
                        <th class="th-location"><i class="fas fa-map-marked-alt mr-1"></i>Address / Location</th>
                        <th class="th-price"><img src="uploads/price.jpg" alt="Price" class="price-icon-img mr-1">Price</th>
                        <th class="th-stars"><i class="fas fa-star mr-1"></i>Stars</th>
                        <th class="th-reviews"><i class="fas fa-comments mr-1"></i>Reviews</th>
                        <th class="th-guest"><i class="fas fa-users mr-1"></i>Guest</th>
                        <th class="th-availability"><i class="fas fa-calendar-check mr-1"></i>Availability</th>
                        <th class="th-images"><i class="fas fa-images mr-1"></i>Images</th>
                        <th class="th-action"><i class="fas fa-hand-point-right mr-1"></i>Action</th>
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
        <h4 class="loader-title">AIRBNB 2 ENGINE</h4>
        <p class="loader-text">Fetching listings and statistics...</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const AIRBNB2_SEARCH_ENDPOINT = 'airbnb2_search.php';
const SAVE_ENDPOINT = 'save_to_db.php';
let latestResults = [];
const locationCache = new Map();

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function toNumber(value, fallback = 0) {
    const n = Number(value);
    return Number.isFinite(n) ? n : fallback;
}

function animateCount(el, target, decimals = 0) {
    const duration = 1000;
    const startTs = performance.now();
    function step(now) {
        const p = Math.min((now - startTs) / duration, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        const current = target * eased;
        el.textContent = decimals > 0 ? current.toFixed(decimals) : Math.floor(current).toLocaleString();
        if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

async function checkStatus() {
    const box = document.getElementById('status-box');
    const text = document.getElementById('status-text');
    try {
        const res = await fetch(`${AIRBNB2_SEARCH_ENDPOINT}?action=status`);
        if (!res.ok) throw new Error('offline');
        box.classList.remove('offline');
        box.classList.add('online');
        text.textContent = 'Airbnb 2 API Online';
    } catch (e) {
        box.classList.remove('online');
        box.classList.add('offline');
        text.textContent = 'Airbnb 2 API Offline';
    }
}

function updateStats(stats, records) {
    const safeStats = stats || {};
    animateCount(document.getElementById('stat-total'), Number(safeStats.total_listings ?? records.length), 0);
    animateCount(document.getElementById('stat-hosts'), Number(safeStats.unique_hosts ?? 0), 0);
    animateCount(document.getElementById('stat-stars'), toNumber(safeStats.avg_stars ?? 0), 2);
    animateCount(document.getElementById('stat-reviews'), Number(safeStats.with_reviews ?? 0), 0);
}

function renderResults(results) {
    const tbody = document.getElementById('results-body');
    tbody.innerHTML = '';

    if (!results.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center text-muted py-4">No listings found for this search.</td>
            </tr>
        `;
        return;
    }

    results.forEach((item, index) => {
        const hasCoords = item.lat != null && item.lng != null && item.lat !== '' && item.lng !== '';
        const locationText = [item.city, item.neighborhood].filter(Boolean).join(', ');
        const addressBlock = item.address || locationText || 'N/A';
        const coordsText = hasCoords ? `${item.lat}, ${item.lng}` : '';
        const directionsUrl = hasCoords
            ? `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent('My Location')}&destination=${encodeURIComponent(`${item.lat},${item.lng}`)}&travelmode=driving`
            : '#';
        const safeUrl = item.url ? encodeURI(item.url) : '#';
        const openBtn = item.url
            ? `<a class="btn btn-sm btn-outline-info" href="${safeUrl}" target="_blank" rel="noopener">Open</a>`
            : `<span class="badge badge-light border">N/A</span>`;

        const row = `
            <tr>
                <td data-label="Listing"><span class="table-badge badge-listing">${escapeHtml(item.name || 'N/A')}</span></td>
                <td data-label="Host">
                    <div><span class="table-badge badge-host">${escapeHtml(item.host_name || 'N/A')}</span></div>
                    <div class="mt-1">${item.host_phone ? `<span class="table-badge badge-phone">${escapeHtml(item.host_phone)}</span>` : ''}</div>
                </td>
                <td data-label="Address / Location">
                    <div><span class="table-badge badge-location" id="geo-location-main-${index}">${escapeHtml(addressBlock)}</span></div>
                    <div class="small text-muted mt-1" id="geo-location-sub-${index}">
                        ${hasCoords ? 'Inatafuta location halisi...' : ''}
                    </div>
                    <div>
                        ${hasCoords
                            ? `<a class="map-direction-link" href="${directionsUrl}" target="_blank" rel="noopener"><i class="fas fa-location-arrow"></i> ${escapeHtml(coordsText)} (Directions)</a>`
                            : ''}
                    </div>
                </td>
                <td data-label="Price"><span class="table-badge badge-price">${escapeHtml(item.room_price || 'N/A')}</span></td>
                <td data-label="Stars"><span class="table-badge badge-stars">${escapeHtml(item.stars || 'N/A')}</span></td>
                <td data-label="Reviews"><span class="table-badge badge-reviews">${escapeHtml(item.reviews_count || '0')}</span></td>
                <td data-label="Guest"><span class="table-badge badge-guest">${escapeHtml(item.guest_capacity || 'N/A')}</span></td>
                <td data-label="Availability"><span class="table-badge badge-availability">${escapeHtml(item.availability || 'N/A')}</span></td>
                <td data-label="Images"><span class="table-badge badge-images">${escapeHtml(item.images_count || '0')}</span></td>
                <td data-label="Action">${openBtn}</td>
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
    const res = await fetch(url, { method: 'GET' });
    if (!res.ok) {
        throw new Error(`Geocode HTTP ${res.status}`);
    }
    const data = await res.json();
    const detailed = buildDetailedLocationFromAddress(data.address || {});
    locationCache.set(key, detailed);
    return detailed;
}

async function enrichLocationItem(item, index) {
    const hasCoords = item.lat != null && item.lng != null && item.lat !== '' && item.lng !== '';
    if (!hasCoords) return;

    const mainEl = document.getElementById(`geo-location-main-${index}`);
    const subEl = document.getElementById(`geo-location-sub-${index}`);
    if (!mainEl) return;

    try {
        const detailed = await reverseGeocodeFromLatLng(item.lat, item.lng);
        if (detailed.full) {
            mainEl.textContent = detailed.full;
        }
        if (subEl) {
            subEl.textContent = detailed.full
                ? `Mkoa: ${detailed.region || 'N/A'} | Wilaya: ${detailed.district || 'N/A'} | Mtaa: ${detailed.street || 'N/A'}`
                : 'Location details hazikupatikana kikamilifu.';
        }

        if (detailed.region) item.location_name = detailed.region;
        if (detailed.street) item.neighborhood = detailed.street;
        if (detailed.full) item.address = detailed.full;
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

async function performSearch() {
    const queryFromMain = document.getElementById('airbnb2-query').value.trim();
    const queryFromNav = document.getElementById('nav-search').value.trim();
    const query = queryFromMain || queryFromNav;
    if (!query) {
        Swal.fire({ icon: 'warning', title: 'Input required', text: 'Please enter a location first.' });
        return;
    }

    const limit = parseInt(document.getElementById('airbnb2-limit').value, 10) || 20;
    const overlay = document.getElementById('search-overlay');
    const saveBtn = document.getElementById('save-results-btn');
    const resultsWrap = document.getElementById('results-wrapper');
    overlay.style.display = 'flex';
    saveBtn.disabled = true;
    latestResults = [];

    try {
        const res = await fetch(`${AIRBNB2_SEARCH_ENDPOINT}?query=${encodeURIComponent(query)}&limit=${encodeURIComponent(limit)}`);
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.message || data.error || 'Search failed');
        }

        latestResults = Array.isArray(data.records) ? data.records : [];
        renderResults(latestResults);
        updateStats(data.stats || {}, latestResults);
        saveBtn.disabled = latestResults.length === 0;
        resultsWrap.style.display = 'block';

        Swal.fire({
            icon: 'success',
            title: 'Search complete',
            text: `${latestResults.length} listings loaded.`,
            timer: 1400,
            showConfirmButton: false
        });
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Search failed', text: err.message || 'Unknown error' });
    } finally {
        overlay.style.display = 'none';
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
        title: 'Save Airbnb 2 Results',
        html: `
            <div style="text-align:left;">
                <label style="font-weight:600;font-size:13px;">Select Existing Category</label>
                <select id="swal-category-select" class="swal2-input" style="margin:8px 0 12px;">
                    <option value="">-- Select category --</option>
                    ${optionsHtml}
                </select>
                <div style="text-align:center;color:#64748b;font-size:12px;margin:4px 0;">OR</div>
                <label style="font-weight:600;font-size:13px;">Create New Category</label>
                <input id="swal-category-new" class="swal2-input" placeholder="e.g. airbnb2_dar_feb_2026">
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
    if (!result.isConfirmed) return;

    const categoryName = result.value;
    const payload = latestResults.map((item) => ({
        username: item.username || (`airbnb2_${item.listing_id || Math.random().toString(36).slice(2, 10)}`),
        phone: item.host_phone || item.phone || '',
        bio: item.bio || `${item.description || item.name || ''} | Host: ${item.host_name || 'N/A'} | Address: ${item.address || 'N/A'} | City: ${item.city || ''} | Neighborhood: ${item.neighborhood || ''} | Price: ${item.room_price || ''} | Guests: ${item.guest_capacity || ''} | Beds: ${item.beds || ''} | Baths: ${item.bathrooms || ''} | Stars: ${item.stars || ''} | Reviews: ${item.reviews_count || ''}`,
        listing_id: item.listing_id || '',
        listing_url: item.url || '',
        host_name: item.host_name || '',
        host_phone: item.host_phone || '',
        location_name: item.city || '',
        neighborhood: item.neighborhood || '',
        latitude: item.lat != null ? String(item.lat) : (item.latitude || ''),
        longitude: item.lng != null ? String(item.lng) : (item.longitude || ''),
        room_price: item.room_price || '',
        description: item.description || item.name || '',
        source: 'airbnb2'
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
                records: payload
            })
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.error || 'Save failed');
        }
        Swal.fire({
            icon: 'success',
            title: 'Saved successfully',
            html: `Category: <b>${escapeHtml(data.category_name)}</b><br>Saved: <b>${Number(data.saved_category || 0)}</b><br>Duplicates: <b>${Number(data.duplicates || 0)}</b>`
        });
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Save error', text: err.message || 'Unknown error' });
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

(function initTilesAnimation() {
    const tiles = document.querySelectorAll('.tile-box');
    tiles.forEach((tile, idx) => setTimeout(() => tile.classList.add('tile-visible'), 120 + idx * 70));
    updateStats({}, []);
})();

document.getElementById('airbnb2-search-btn').addEventListener('click', performSearch);
document.getElementById('save-results-btn').addEventListener('click', openSaveCategoryModal);
document.getElementById('airbnb2-query').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        performSearch();
    }
});
document.getElementById('nav-search').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('airbnb2-query').value = this.value.trim();
        performSearch();
    }
});

checkStatus();
setInterval(checkStatus, 30000);
</script>
<script src="js/code_protection.js"></script>
</body>
</html>
