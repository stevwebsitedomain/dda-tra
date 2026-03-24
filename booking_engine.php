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
    <title>TRA DDA | Booking Engine</title>

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
            letter-spacing: 0.02em;
            animation: headerGlowFloat 4s ease-in-out infinite;
        }
        .booking-header-logo {
            position: relative;
            z-index: 1;
            display: block;
            margin: 0 auto;
            width: min(320px, 78%);
            max-height: 84px;
            object-fit: contain;
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
        .tile-box i { font-size: 28px; color: var(--accent-gold); }
        .booking-card-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            object-fit: contain;
            flex: 0 0 28px;
        }
        .tile-stat { font-size: 30px; font-weight: 800; line-height: 1; letter-spacing: .01em; }
        .tile-stat.tile-results { color: #0f766e; }
        .tile-stat.tile-locations { color: #1d4ed8; }
        .tile-stat.tile-prices { color: #7c3aed; }
        .tile-stat.tile-openable { color: #b91c1c; }
        .tile-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }

        .card-custom {
            background: #fff;
            margin: 20px 30px;
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

        .results-cards-wrap {
            display: none;
            margin: 0 30px 20px;
        }
        .results-cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }
        .booking-result-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            padding: 15px;
            border-left: 3px solid var(--accent-gold);
        }
        .booking-result-card .host {
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 6px;
        }
        .booking-result-card .meta {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .booking-result-card .desc {
            font-size: 12px;
            color: #374151;
            line-height: 1.4;
            min-height: 48px;
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
        .badge-host { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
        .badge-price { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
        .badge-description { background: #f8fafc; color: #334155; border-color: #cbd5e1; font-weight: 600; }
        .badge-location { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }

        .booking-search-row {
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            gap: 12px;
            margin-bottom: 12px;
        }
        .booking-search-by-wrap {
            display: flex;
            flex-direction: column;
            min-width: 180px;
            max-width: 260px;
        }
        .booking-search-by-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 4px;
        }
        .booking-search-by-select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #fff;
            font-weight: 600;
            color: #1e293b;
        }
        .booking-query-wrap {
            flex: 1;
            min-width: 180px;
        }
        .booking-query-wrap .form-control {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }
        .booking-search-btn-wrap .btn {
            border-radius: 8px;
            white-space: nowrap;
        }
        @media (max-width: 768px) {
            .booking-search-row { flex-direction: column; }
            .booking-search-by-wrap { max-width: 100%; }
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
                width: 280px;
            }
            .sidebar-wrapper.sidebar-open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .tt-header { padding: 10px 15px 10px 60px; gap: 10px; }
            .tt-page-header { padding: 26px 15px 20px; }
            .tiles-wrapper { grid-template-columns: repeat(2, 1fr); padding: 15px; margin-top: 0; }
            .tile-box { min-height: 110px; padding: 18px; }
            .tile-stat { font-size: 26px; }
            .card-custom { margin: 15px; }
            .results-cards-wrap { margin: 0 15px 15px; }
            .results-cards-grid { grid-template-columns: 1fr; }
            .results-wrapper { margin: 0 15px 20px; padding: 15px; }
        }
        @media (max-width: 575px) {
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
        <a href="airbnb_engine.php" class="sidebar-link"><i class="fab fa-airbnb"></i> Airbnb Engine</a>
        <a href="airbnb_realtime.php" class="sidebar-link"><i class="fas fa-bolt"></i> Airbnb Real Time</a>
        <a href="airbnb2_engine.php" class="sidebar-link"><i class="fab fa-airbnb"></i> Airbnb 2</a>
        <a href="upgraded_airbnb.php" class="sidebar-link"><i class="fas fa-star"></i> Upgraded Airbnb</a>
        <a href="booking_engine.php" class="sidebar-link active"><i class="fas fa-hotel"></i> Booking Engine</a>
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
        <div class="font-weight-bold text-muted">BOOKING.COM SEARCH ENGINE</div>
        <div id="status-box" class="status-badge offline">
            <i class="fas fa-circle-notch"></i>
            <span id="status-text">Checking service...</span>
        </div>
    </header>

    <section class="tt-page-header">
        <img src="uploads/booking.png" alt="Booking Logo" class="booking-header-logo" onerror="this.style.display='none'">
    </section>
    <div class="tiles-wrapper">
        <div class="tile-box"><i class="fas fa-list"></i><div><div class="tile-stat tile-results js-count" id="booking-stat-results" data-count="0">0</div><div class="tile-label">Results</div></div></div>
        <div class="tile-box"><i class="fas fa-map-marker-alt"></i><div><div class="tile-stat tile-locations js-count" id="booking-stat-locations" data-count="0">0</div><div class="tile-label">Locations</div></div></div>
        <div class="tile-box"><img src="uploads/price.jpg" alt="Price" class="booking-card-icon" onerror="this.style.display='none'"><div><div class="tile-stat tile-prices js-count" id="booking-stat-prices" data-count="0">0</div><div class="tile-label">With Price</div></div></div>
        <div class="tile-box"><i class="fas fa-link"></i><div><div class="tile-stat tile-openable js-count" id="booking-stat-open" data-count="0">0</div><div class="tile-label">Open Links</div></div></div>
    </div>

    <div class="card card-custom">
        <div class="card-header-accent"><i class="fas fa-magnifying-glass mr-2"></i> Booking Search</div>
        <div class="card-body">
            <p class="small text-muted mb-3">
                Chagua <strong>Search by</strong>, andika location au neno (mfano: <strong>man</strong>, <strong>Tanzania</strong>) kisha bonyeza Search.
            </p>
            <div class="booking-search-row">
                <div class="booking-search-by-wrap">
                    <label class="booking-search-by-label" for="booking-search-by">Search by</label>
                    <select id="booking-search-by" class="form-control form-control-lg booking-search-by-select">
                        <option value="locationToLatLong">Location to Lat Long</option>
                        <option value="searchDestination">Search Hotel Destination</option>
                    </select>
                </div>
                <div class="booking-query-wrap">
                    <input type="text" id="booking-query" class="form-control form-control-lg" placeholder="e.g. Tanzania, mikoa, Zanzibar, Dar">
                </div>
                <div class="booking-search-btn-wrap">
                    <button class="btn btn-primary btn-lg px-4 font-weight-bold" id="booking-search-btn">
                        <i class="fas fa-search"></i> SEARCH
                    </button>
                </div>
            </div>
            <div class="mt-2 d-flex align-items-center" style="gap:8px;">
                <label for="booking-limit" class="small text-muted mb-0">Result Limit:</label>
                <select id="booking-limit" class="form-control form-control-sm" style="max-width: 140px;">
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="500" selected>500</option>
                    <option value="1000">1,000</option>
                    <option value="2000">2,000</option>
                    <option value="5000">5,000</option>
                    <option value="10000">10,000</option>
                </select>
            </div>
        </div>
    </div>

    <section class="results-cards-wrap" id="results-cards-wrap">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="font-weight-bold mb-0">Top 3 Booking Results</h5>
            <span class="small text-muted" id="result-counter-text"></span>
        </div>
        <div class="results-cards-grid" id="top-cards-grid"></div>
    </section>

    <div class="results-wrapper booking-results-watermark" id="results-wrapper">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap: 10px;">
            <h5 class="font-weight-bold mb-0">Booking Results Table</h5>
            <button type="button" class="btn btn-success btn-sm font-weight-bold" id="booking-save-category-btn" style="display: none;">
                <i class="fas fa-database"></i> Save to category
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Region</th>
                        <th><img src="uploads/price.jpg" alt="Price" class="price-icon-img mr-1">Amount</th>
                        <th>Description</th>
                        <th>Specific Location</th>
                        <th>Action</th>
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
        <h4 class="loader-title">BOOKING ENGINE</h4>
        <p class="loader-text">Searching Booking.com records...</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BOOKING_SEARCH_ENDPOINT = 'booking_search.php';
const BOOKING_SAVE_ENDPOINT = 'save_to_db.php';
let latestResults = [];

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function animateCount(el, target) {
    const duration = 1000;
    const start = 0;
    const startTs = performance.now();
    function step(now) {
        const p = Math.min((now - startTs) / duration, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        const val = Math.floor(start + (target - start) * eased);
        el.textContent = val.toLocaleString();
        if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

function updateTopStats(results) {
    const total = results.length;
    const uniqueLocations = new Set(results.map((r) => (r.location || '').trim()).filter(Boolean)).size;
    const withPrice = results.filter((r) => String(r.price || '').trim() !== '').length;
    const openable = results.filter((r) => String(r.url || '').trim() !== '').length;
    const stats = {
        'booking-stat-results': total,
        'booking-stat-locations': uniqueLocations,
        'booking-stat-prices': withPrice,
        'booking-stat-open': openable
    };
    Object.entries(stats).forEach(([id, value]) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.setAttribute('data-count', String(value));
        animateCount(el, value);
    });
}

async function checkStatus() {
    const box = document.getElementById('status-box');
    const text = document.getElementById('status-text');
    try {
        const res = await fetch(`${BOOKING_SEARCH_ENDPOINT}?action=status`);
        if (!res.ok) throw new Error('offline');
        box.classList.remove('offline');
        box.classList.add('online');
        text.textContent = 'Booking API Online';
    } catch (e) {
        box.classList.remove('online');
        box.classList.add('offline');
        text.textContent = 'Booking API Offline';
    }
}

function renderTopCards(results) {
    const wrap = document.getElementById('results-cards-wrap');
    const grid = document.getElementById('top-cards-grid');
    const counter = document.getElementById('result-counter-text');
    grid.innerHTML = '';

    if (!results.length) {
        wrap.style.display = 'none';
        counter.textContent = '';
        updateTopStats([]);
        return;
    }

    const topThree = results.slice(0, 3);
    topThree.forEach((item) => {
        const card = `
            <article class="booking-result-card">
                <div class="host">${escapeHtml(item.host_name || 'N/A')}</div>
                <div class="meta">
                    <strong><img src="uploads/price.jpg" alt="Price" class="price-icon-img mr-1">Price:</strong> ${escapeHtml(item.price || 'N/A')}<br>
                    <strong>Location:</strong> ${escapeHtml(item.location || 'N/A')}
                </div>
                <div class="desc">${escapeHtml((item.description || 'No description').slice(0, 120))}</div>
            </article>
        `;
        grid.insertAdjacentHTML('beforeend', card);
    });

    wrap.style.display = 'block';
    counter.textContent = `${results.length} results found`;
    updateTopStats(results);
}

function renderTable(results) {
    const tbody = document.getElementById('results-body');
    tbody.innerHTML = '';

    if (!results.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted py-4">No Booking results found for this search.</td>
            </tr>
        `;
        const saveBtn = document.getElementById('booking-save-category-btn');
        if (saveBtn) saveBtn.style.display = 'none';
        return;
    }

    results.forEach((item) => {
        const actionUrl = (item.url || '').trim();
        const hasLatLng = item.latitude != null && item.longitude != null && !isNaN(item.latitude) && !isNaN(item.longitude);
        const dest = hasLatLng ? (item.latitude + ',' + item.longitude) : '';
        const mapUrl = dest ? ('https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(dest)) : '';
        const mapLinkHtml = '<i class="fas fa-map-marker-alt"></i> Map &amp; Directions';
        const mapLinkHtmlAction = '<i class="fas fa-map-marked-alt"></i> Map &amp; Directions';
        const locationCell = hasLatLng
            ? `<span class="table-badge badge-location">${escapeHtml(item.location || 'N/A')}</span>
               <a class="btn btn-sm btn-outline-primary ml-1 mt-1 js-map-directions" href="${mapUrl}" data-destination="${escapeHtml(dest)}" data-restore-html="${escapeHtml(mapLinkHtml)}" target="_blank" rel="noopener" title="Fungua ramani na maelekezo (kutoka mahali ulipo)">${mapLinkHtml}</a>`
            : `<span class="table-badge badge-location">${escapeHtml(item.location || 'N/A')}</span>`;
        const actionParts = [];
        if (actionUrl) actionParts.push(`<a class="btn btn-sm btn-outline-info" href="${String(actionUrl).replace(/"/g, '&quot;')}" target="_blank" rel="noopener">Open</a>`);
        if (dest) actionParts.push(`<a class="btn btn-sm btn-outline-primary js-map-directions" href="${mapUrl}" data-destination="${escapeHtml(dest)}" data-restore-html="${escapeHtml(mapLinkHtmlAction)}" target="_blank" rel="noopener" title="Fungua ramani na maelekezo (kutoka mahali ulipo)">${mapLinkHtmlAction}</a>`);
        const actionCell = actionParts.length ? actionParts.join(' ') : '<span class="text-muted small">—</span>';
        const row = `
            <tr>
                <td data-label="Region"><span class="table-badge badge-host">${escapeHtml(item.host_name || 'N/A')}</span></td>
                <td data-label="Amount"><span class="table-badge badge-price">${escapeHtml(item.price || 'N/A')}</span></td>
                <td data-label="Description"><span class="table-badge badge-description">${escapeHtml((item.description || 'N/A').slice(0, 140))}</span></td>
                <td data-label="Specific Location">${locationCell}</td>
                <td data-label="Action">${actionCell}</td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
    });
    const saveBtn = document.getElementById('booking-save-category-btn');
    if (saveBtn) saveBtn.style.display = results.length ? 'inline-flex' : 'none';
}

function getResponseArray(data) {
    if (Array.isArray(data)) return data;
    if (data && Array.isArray(data.data)) return data.data;
    if (data && data.data && Array.isArray(data.data.data)) return data.data.data;
    if (data && data.data != null) return [data.data];
    return [];
}

/** Tanzania only filter (mikoa / regions) */
function filterTanzaniaOnly(list) {
    return list.filter((item) => {
        const country = (item.country || item.description || '').toLowerCase();
        const name = (item.host_name || '').toLowerCase();
        const desc = (item.description || '').toLowerCase();
        return country.includes('tanzania') || name.includes('tanzania') || desc.includes('tanzania') || (!country && !desc);
    });
}

/** Normalize GET API response (searchDestination / locationToLatLong) to table row shape; keep latitude/longitude for map */
function normalizeGetResults(searchBy, data) {
    const rows = [];
    if (searchBy === 'searchDestination') {
        const list = getResponseArray(data);
        list.forEach((item) => {
            if (!item || typeof item !== 'object') return;
            const country = (item.country || '').trim();
            if (country && !country.toLowerCase().includes('tanzania')) return;
            const name = item.name || item.label || '';
            const label = item.label || item.name || name;
            const city = (item.city_name || '').trim();
            const nrHotels = item.nr_hotels ?? item.hotels ?? '';
            const lat = item.latitude != null ? Number(item.latitude) : '';
            const lng = item.longitude != null ? Number(item.longitude) : '';
            const loc = (lat !== '' && lng !== '') ? `${lat}, ${lng}` : '';
            rows.push({
                host_name: label || name || '—',
                price: nrHotels !== '' ? nrHotels + ' hotels' : '—',
                description: country + (city ? ', ' + city : ''),
                location: loc || '—',
                url: (item.image_url || '').trim() || '',
                latitude: lat !== '' ? lat : null,
                longitude: lng !== '' ? lng : null
            });
        });
    } else if (searchBy === 'locationToLatLong') {
        const list = getResponseArray(data);
        if (!list.length && data && typeof data === 'object' && (data.latitude != null || data.lat != null)) {
            list.push(data);
        }
        list.forEach((item) => {
            if (!item || typeof item !== 'object') return;
            const lat = item.latitude ?? item.lat ?? '';
            const lng = item.longitude ?? item.lng ?? item.lon ?? '';
            const name = item.name || item.label || item.query || item.place || 'Location';
            const country = (item.country || item.city || '').trim();
            if (country && !country.toLowerCase().includes('tanzania')) return;
            rows.push({
                host_name: name,
                price: '—',
                description: country || '—',
                location: (lat !== '' && lng !== '') ? `${lat}, ${lng}` : '—',
                url: (item.image_url || item.url || '').trim() || '',
                latitude: lat !== '' ? Number(lat) : null,
                longitude: lng !== '' ? Number(lng) : null
            });
        });
    }
    return filterTanzaniaOnly(rows).length ? filterTanzaniaOnly(rows) : rows;
}

async function performBookingSearch() {
    const query = document.getElementById('booking-query').value.trim();
    if (!query) {
        Swal.fire({ icon: 'warning', title: 'Input required', text: 'Please enter a search query first.' });
        return;
    }

    const searchBy = document.getElementById('booking-search-by').value;
    const isGetSearch = searchBy === 'locationToLatLong' || searchBy === 'searchDestination';

    const overlay = document.getElementById('search-overlay');
    const resultsWrap = document.getElementById('results-wrapper');
    overlay.style.display = 'flex';
    latestResults = [];

    try {
        if (isGetSearch) {
            const res = await fetch(`${BOOKING_SEARCH_ENDPOINT}?action=${encodeURIComponent(searchBy)}&query=${encodeURIComponent(query)}`);
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'GET search failed');
            }
            const payload = data.data != null ? data.data : data;
            const list = normalizeGetResults(searchBy, payload);
            latestResults = list;
            renderTopCards(latestResults);
            renderTable(latestResults);
            resultsWrap.style.display = 'block';
            Swal.fire({ icon: 'success', title: 'Search complete', text: list.length + ' result(s) found.', timer: 1400, showConfirmButton: false });
        } else {
            const limit = parseInt(document.getElementById('booking-limit').value, 10) || 500;
            const res = await fetch(`${BOOKING_SEARCH_ENDPOINT}?query=${encodeURIComponent(query)}&limit=${encodeURIComponent(limit)}`);
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Booking search failed');
            }
            latestResults = Array.isArray(data.records) ? data.records : [];
            renderTopCards(latestResults);
            renderTable(latestResults);
            resultsWrap.style.display = 'block';
            Swal.fire({
                icon: 'success',
                title: 'Search complete',
                text: `${latestResults.length} Booking records found.`,
                timer: 1400,
                showConfirmButton: false
            });
        }
    } catch (err) {
        renderTopCards([]);
        renderTable([]);
        resultsWrap.style.display = 'block';
        Swal.fire({ icon: 'error', title: 'Search failed', text: err.message || 'Booking service failed.' });
    } finally {
        overlay.style.display = 'none';
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

(function initTileAnimation() {
    const tiles = document.querySelectorAll('.tile-box');
    tiles.forEach((tile, idx) => setTimeout(() => tile.classList.add('tile-visible'), 120 + idx * 70));
    updateTopStats([]);
})();

document.getElementById('booking-search-btn').addEventListener('click', performBookingSearch);
document.getElementById('booking-query').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        performBookingSearch();
    }
});

async function performBookingSaveToCategory() {
    if (!latestResults.length) {
        Swal.fire({ icon: 'warning', title: 'No data', text: 'Run a search first, then save results to a category.' });
        return;
    }
    let categories = [];
    try {
        const res = await fetch(BOOKING_SAVE_ENDPOINT + '?action=list_categories');
        const data = await res.json();
        if (data.success && Array.isArray(data.categories)) categories = data.categories;
    } catch (e) {}
    const opts = categories.map((c) => `<option value="${escapeHtml(c.category_name || '')}">${escapeHtml(c.category_name || '')}</option>`).join('');
    const result = await Swal.fire({
        title: 'Save to category',
        html: `
            <p class="text-muted small mb-2">Chagua category au andika jina jipya.</p>
            <select id="swal-booking-cat-select" class="swal2-input" style="margin:8px 0 12px; width:100%;">
                <option value="">-- Chagua category --</option>
                ${opts}
            </select>
            <input id="swal-booking-cat-new" class="swal2-input" placeholder="Au andika jina jipya (mf. booking_zanzibar_2026)">
        `,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const selected = (document.getElementById('swal-booking-cat-select') && document.getElementById('swal-booking-cat-select').value) ? document.getElementById('swal-booking-cat-select').value.trim() : '';
            const created = (document.getElementById('swal-booking-cat-new') && document.getElementById('swal-booking-cat-new').value) ? document.getElementById('swal-booking-cat-new').value.trim() : '';
            const category = created || selected;
            if (!category) {
                Swal.showValidationMessage('Chagua category au andika jina jipya.');
                return false;
            }
            return category;
        }
    });
    if (!result.isConfirmed || !result.value) return;
    const categoryName = result.value;
    const slug = (s) => String(s).replace(/\s+/g, '_').replace(/[^a-zA-Z0-9_-]/g, '').slice(0, 80);
    const savePayload = latestResults.map((item, idx) => {
        const uname = item.host_name
            ? ('booking_' + slug(item.host_name) + '_' + (item.latitude != null ? item.latitude : '') + '_' + (item.longitude != null ? item.longitude : '') + '_' + idx).replace(/__/g, '_').slice(0, 255)
            : 'booking_' + idx + '_' + Date.now();
        return {
            username: uname,
            phone: '',
            bio: (item.description || '') + ' | ' + (item.host_name || ''),
            source: 'booking',
            listing_id: '',
            listing_url: item.url || '',
            host_name: item.host_name || '',
            host_phone: '',
            location_name: item.description || item.host_name || '',
            neighborhood: '',
            latitude: item.latitude != null ? String(item.latitude) : '',
            longitude: item.longitude != null ? String(item.longitude) : '',
            room_price: item.price || '',
            description: item.description || ''
        };
    });
    try {
        Swal.fire({ title: 'Saving...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const res = await fetch(BOOKING_SAVE_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'save_batch_category', category_name: categoryName, records: savePayload })
        });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.error || 'Save failed');
        await Swal.fire({
            icon: 'success',
            title: 'Saved',
            html: `Category: <b>${escapeHtml(data.category_name || categoryName)}</b><br>Saved: <b>${Number(data.saved_category || 0)}</b><br>Duplicates skipped: <b>${Number(data.duplicates || 0)}</b>`
        });
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Save failed', text: err.message || 'Unknown error' });
    }
}

const saveCatBtn = document.getElementById('booking-save-category-btn');
if (saveCatBtn) saveCatBtn.addEventListener('click', performBookingSaveToCategory);

// Map & Directions: use your location as start so map shows "jina la sehemu ya kuanzia"
document.body.addEventListener('click', function(e) {
    const link = e.target.closest('a.js-map-directions');
    if (!link || !link.dataset.destination) return;
    e.preventDefault();
    const destination = link.dataset.destination;
    const fallbackUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(destination);

    function openWithOrigin(lat, lng) {
        const url = 'https://www.google.com/maps/dir/?api=1&origin=' + encodeURIComponent(lat + ',' + lng) + '&destination=' + encodeURIComponent(destination);
        window.open(url, '_blank', 'noopener');
    }

    if (!navigator.geolocation) {
        window.open(fallbackUrl, '_blank', 'noopener');
        return;
    }
    link.style.pointerEvents = 'none';
    const restoreHtml = link.getAttribute('data-restore-html') || 'Map &amp; Directions';
    link.innerHTML = ' Inaomba eneo... ';
    navigator.geolocation.getCurrentPosition(
        function(pos) {
            openWithOrigin(pos.coords.latitude, pos.coords.longitude);
            link.style.pointerEvents = '';
            link.innerHTML = restoreHtml;
        },
        function() {
            window.open(fallbackUrl, '_blank', 'noopener');
            link.style.pointerEvents = '';
            link.innerHTML = restoreHtml;
        },
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
    );
});

checkStatus();
setInterval(checkStatus, 30000);
</script>
<script src="js/code_protection.js"></script>
</body>
</html>

