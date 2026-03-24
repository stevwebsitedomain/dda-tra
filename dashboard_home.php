<?php
require_once __DIR__ . '/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please Login First");
    exit();
}

$user_name = $_SESSION['user_name'] ?? 'Admin';

try {
    $conn = get_db_connection();
} catch (Exception $e) {
    die("DB error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

function safe_count($conn, $sql) {
    $res = $conn->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_assoc();
    return (int)($row['c'] ?? 0);
}

$totalCategories = safe_count($conn, "SELECT COUNT(*) AS c FROM data_categories");
$totalCategoryData = safe_count($conn, "SELECT COUNT(*) AS c FROM category_data");
$totalMainInstagram = safe_count($conn, "SELECT COUNT(*) AS c FROM instagram_data");
$totalUncategorized = safe_count($conn, "
    SELECT COUNT(*) AS c
    FROM instagram_data i
    WHERE NOT EXISTS (
        SELECT 1
        FROM category_data cd
        WHERE cd.username = i.username
    )
");
$totalMain = $totalCategoryData + $totalUncategorized;
$tiktokRecords = safe_count($conn, "SELECT COUNT(*) AS c FROM category_data WHERE source = 'tiktok'");
$airbnbRecords = safe_count($conn, "SELECT COUNT(*) AS c FROM category_data WHERE source = 'airbnb'");
$bookingRecords = safe_count($conn, "SELECT COUNT(*) AS c FROM category_data WHERE source = 'booking'");
$usersTotal = safe_count($conn, "SELECT COUNT(*) AS c FROM users");

$sourceBreakdown = [
    ['name' => 'TikTok', 'value' => $tiktokRecords],
    ['name' => 'Airbnb', 'value' => $airbnbRecords],
    ['name' => 'Booking', 'value' => $bookingRecords],
    ['name' => 'Other', 'value' => max(0, $totalCategoryData - ($tiktokRecords + $airbnbRecords + $bookingRecords))]
];

$categoryBreakdown = [];
$categoryRes = $conn->query("
    SELECT COALESCE(NULLIF(TRIM(category_name), ''), 'Uncategorized') AS name, COUNT(*) AS value
    FROM category_data
    GROUP BY name
    ORDER BY value DESC
    LIMIT 10
");
if ($categoryRes) {
    while ($row = $categoryRes->fetch_assoc()) {
        $categoryBreakdown[] = [
            'name' => (string)($row['name'] ?? 'Uncategorized'),
            'value' => (int)($row['value'] ?? 0)
        ];
    }
}
if (!$categoryBreakdown) {
    $categoryBreakdown[] = ['name' => 'Uncategorized', 'value' => (int)$totalCategoryData];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <title>TRA DDA | Dashboard</title>
    <link rel="icon" href="dda.jpg" type="image/jpeg">
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
            font-size: 16px;
            background: var(--light-bg);
            margin: 0;
            overflow-x: hidden;
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }
        .sidebar-wrapper {
            width: var(--sidebar-width);
            background: var(--tra-navy);
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
        .sidebar-wrapper { display: flex; flex-direction: column; }
        .sidebar-nav { flex: 1; }
        .sidebar-coat-section { display: none !important; }
        .sidebar-logo-section { padding: 15px; text-align: center; background: rgba(0,0,0,.2); font-size: 0.95rem; }
        .sidebar-logo-section img { width: 100%; height: 100px; border-radius: 0; background: #fff; padding: 0; object-fit: cover; object-position: center; display: block; }
        .card-sidebar-info { background: rgba(255,255,255,.05); margin: 15px; border-radius: 8px; padding: 15px; border-left: 3px solid var(--accent-gold); font-size: 15px; }
        .sidebar-link { padding: 12px 25px; color: #bdc3c7; display: flex; align-items: center; text-decoration: none !important; transition: .3s; font-size: 15px; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(255,255,255,.1); color: #fff; }
        .sidebar-link i { margin-right: 15px; width: 20px; text-align: center; }

        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .tt-header {
            background: #fff;
            min-height: 65px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 0 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .header-title {
            min-width: 0;
            line-height: 1.35;
            font-size: 1.05rem;
        }
        .tt-page-header {
            background: var(--tra-blue-gradient);
            padding: 28px 16px 24px;
            color: #fff;
            text-align: center;
            position: relative;
            overflow: hidden;
            width: 100% !important;
            max-width: none !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            box-sizing: border-box;
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
        .tt-page-header h1,
        .tt-page-header .h3 {
            font-size: 1.75rem;
            letter-spacing: 0.02em;
            animation: headerGlowFloat 4s ease-in-out infinite;
        }
        .tt-page-header .small {
            font-size: 1rem;
            opacity: 0.95;
        }
        @keyframes headerGlowFloat {
            0%, 100% { transform: translateY(0); text-shadow: 0 3px 14px rgba(6, 20, 45, 0.45); }
            50% { transform: translateY(-2px); text-shadow: 0 6px 16px rgba(6, 20, 45, 0.55); }
        }

        .tiles-wrapper { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; padding: 20px 30px; margin-top: 0; }
        .tile-box {
            background: #fff;
            padding: 24px;
            min-height: 126px;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,.08);
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 3px solid transparent;
            opacity: 0;
            transform: translateY(16px);
            text-decoration: none !important;
            color: inherit;
            cursor: pointer;
        }
        .tile-box:hover { border-bottom-color: var(--accent-gold); }
        .tile-box i { font-size: 32px; color: var(--accent-gold); }
        .tile-stat { font-size: 34px; font-weight: 800; color: #1f2937; line-height: 1; letter-spacing: .01em; }
        .tile-stat.tile-tiktok { color: #0f172a; }
        .tile-stat.tile-airbnb { color: #b91c1c; }
        .tile-stat.tile-booking { color: #1d4ed8; }
        .tile-stat.tile-export { color: #047857; }
        .tile-stat.tile-category { color: #7c3aed; }
        .tile-stat.tile-main { color: #0f766e; }
        .tile-stat.tile-users { color: #92400e; }
        .tile-stat.tile-analytics { color: #be185d; }
        .tile-label { font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }
        .tile-box.tile-visible {
            opacity: 1;
            transform: translateY(0);
            transition: opacity .5s ease, transform .5s ease;
        }

        .card-custom { background: #fff; margin: 0 30px 20px; border-radius: 10px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .card-header-accent { border-top: 3px solid var(--accent-gold); font-weight: 700; font-size: 1.1rem; background: #fff; padding: 14px 20px; color: #1f2937; }
        .card-watermark { position: relative; overflow: hidden; }
        .card-watermark::after {
            content: ''; position: absolute; inset: 0; top: 0; left: 0; right: 0; bottom: 0;
            background: url('dda.jpg') center/20% no-repeat; opacity: 0.06; pointer-events: none; z-index: 0;
        }
        .card-watermark .card-header-accent, .card-watermark .charts-grid, .card-watermark .card-body { position: relative; z-index: 1; }
        .charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; padding: 16px; }
        .chart-holder { height: 360px; width: 100%; }

        .summary-table { margin: 0; border-collapse: separate; border-spacing: 0; }
        .summary-table th { font-size: 13px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; font-weight: 700; padding: 14px 18px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
        .summary-table td { font-size: 16px; padding: 14px 18px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
        .summary-table td:first-child { font-weight: 600; color: #0b1e3b; font-size: 16px; }
        .summary-table td:last-child { font-weight: 700; font-variant-numeric: tabular-nums; letter-spacing: .02em; color: #0e2245; font-size: 18px; }
        .summary-table tbody tr:hover { background: #f8fafc; }
        .status-chip { border-radius: 999px; padding: 6px 12px; border: 1px solid #e2e8f0; background: #fff; font-size: 13px; color: #334155; }
        .sidebar-api-status { background: rgba(255,255,255,.06); }
        .api-status-icon { font-size: 8px; vertical-align: middle; color: #94a3b8; transition: color .3s; }
        .api-status-icon.live { color: #22c55e; }
        .api-status-icon.offline { color: #ef4444; }
        .api-status-text { color: #94a3b8; transition: color .3s; }
        .api-status-text.live { color: #22c55e; }
        .api-status-text.offline { color: #ef4444; }
        @keyframes api-pulse { 0%,100% { opacity: 1; } 50% { opacity: .6; } }
        .api-live-chip .api-dot-icon { font-size: 8px; color: #94a3b8; transition: color .3s; }
        .api-live-chip .api-dot-icon.live { color: #22c55e; }
        .api-live-chip .api-dot-icon.offline { color: #ef4444; }
        .api-live-chip #header-api-label.live { color: #22c55e; }
        .api-live-chip #header-api-label.offline { color: #ef4444; }

        .mobile-menu-btn { display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; width: 44px; height: 44px; border-radius: 8px; background: var(--tra-navy); color: #fff; border: none; align-items: center; justify-content: center; font-size: 20px; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,.2); -webkit-tap-highlight-color: transparent; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 999; }

        @media (max-width: 991px) {
            .mobile-menu-btn { display: flex; }
            .sidebar-wrapper { transform: translateX(-100%); transition: transform .3s ease; width: 280px; }
            .sidebar-wrapper.sidebar-open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .tt-header { padding: 10px 15px 10px 60px; flex-wrap: wrap; }
            .header-title { font-size: 12px; }
            .status-chip { font-size: 11px; padding: 5px 10px; }
            .tt-page-header { padding: 25px 15px 20px; }
            .tiles-wrapper { grid-template-columns: repeat(2, 1fr); padding: 15px; margin-top: 0; }
            .card-custom { margin: 0 15px 15px; }
            .charts-grid { grid-template-columns: 1fr; }
            .chart-holder { height: 300px; }
        }
        @media (max-width: 767px) {
            .summary-table thead { display: none; }
            .summary-table, .summary-table tbody, .summary-table tr, .summary-table td { display: block; width: 100%; }
            .summary-table tr {
                border-bottom: 1px solid #eef2f7;
                padding: 12px 14px;
            }
            .summary-table td {
                border: 0;
                padding: 5px 0 !important;
            }
            .summary-table td:first-child {
                font-size: 12px;
                text-transform: uppercase;
                color: #64748b;
                font-weight: 700;
            }
        }
        @media (max-width: 575px) {
            .tiles-wrapper { grid-template-columns: 1fr; }
            .tile-box { min-height: 110px; padding: 18px; }
            .tile-stat { font-size: 28px; }
            .tt-header { padding-left: 15px; }
            .header-title { width: 100%; padding-top: 4px; }
            .chart-holder { height: 260px; }
        }

        @supports (padding: max(0px)) {
            .mobile-menu-btn { left: max(15px, env(safe-area-inset-left)); }
            .tt-header { padding-left: max(15px, env(safe-area-inset-left)); }
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
        <a href="dashboard_home.php" class="sidebar-link active"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="analytics.php" class="sidebar-link"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="dashboard.php" class="sidebar-link"><i class="fab fa-tiktok"></i> TikTok</a>
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

<main class="main-content">
    <header class="tt-header">
        <div class="font-weight-bold text-muted header-title"><i class="fas fa-shield-alt mr-2"></i>INSTITUTIONAL CONTROL DASHBOARD</div>
        <div class="d-flex align-items-center gap-2">
            <div class="status-chip api-live-chip d-flex align-items-center" id="header-api-status">
                <i class="fas fa-circle api-dot-icon mr-2" id="header-api-icon"></i>
                <span id="header-api-label">API …</span>
            </div>
            <div class="status-chip"><i class="fas fa-database mr-1"></i>Live Summary</div>
        </div>
    </header>

    <section class="tt-page-header">
        <h1 class="h3 mb-1">Dashboard Summary</h1>
        <div class="small">Statistics ya kila section ya sidebar kwa muonekano wa taasisi ya serikali.</div>
    </section>

    <div class="tiles-wrapper">
        <a href="download.php?view=main" class="tile-box"><i class="fas fa-server"></i><div><div class="tile-stat tile-main js-count" data-count="<?php echo (int)$totalMain; ?>">0</div><div class="tile-label">Main Table Records</div></div></a>
        <a href="dashboard.php" class="tile-box"><i class="fab fa-tiktok"></i><div><div class="tile-stat tile-tiktok js-count" data-count="<?php echo (int)$tiktokRecords; ?>">0</div><div class="tile-label">TikTok Records</div></div></a>
        <a href="airbnb_engine.php" class="tile-box"><img src="uploads/airbnb3.jpg" alt="Airbnb" class="airbnb-card-icon" onerror="this.style.display='none'"><div><div class="tile-stat tile-airbnb js-count" data-count="<?php echo (int)$airbnbRecords; ?>">0</div><div class="tile-label">Airbnb Records</div></div></a>
        <a href="booking_engine.php" class="tile-box"><img src="uploads/bookingicon.svg" alt="Booking" class="airbnb-card-icon" onerror="this.style.display='none'"><div><div class="tile-stat tile-booking js-count" data-count="<?php echo (int)$bookingRecords; ?>">0</div><div class="tile-label">Booking Records</div></div></a>
        <a href="download.php?view=categorized" class="tile-box"><i class="fas fa-layer-group"></i><div><div class="tile-stat tile-export js-count" data-count="<?php echo (int)$totalCategoryData; ?>">0</div><div class="tile-label">Export Records Total</div></div></a>
        <a href="download.php?view=categories" class="tile-box"><i class="fas fa-tags"></i><div><div class="tile-stat tile-category js-count" data-count="<?php echo (int)$totalCategories; ?>">0</div><div class="tile-label">Categories</div></div></a>
        <a href="settings_dashboard.php" class="tile-box"><i class="fas fa-users"></i><div><div class="tile-stat tile-users js-count" data-count="<?php echo (int)$usersTotal; ?>">0</div><div class="tile-label">System Users</div></div></a>
        <a href="analytics.php" class="tile-box"><i class="fas fa-chart-pie"></i><div><div class="tile-stat tile-analytics js-count" data-count="<?php echo (int)$totalCategoryData; ?>">0</div><div class="tile-label">Analytics Base</div></div></a>
    </div>

    <div class="card card-custom card-watermark">
        <div class="card-header-accent"><i class="fas fa-chart-pie mr-2"></i>Dashboard Pie Analytics</div>
        <div class="charts-grid">
            <div class="chart-holder" id="sourcePie"></div>
            <div class="chart-holder" id="sectionHalfDonut"></div>
        </div>
    </div>

</main>

<script src="https://fastly.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
<script>
const sourceData = <?php echo json_encode($sourceBreakdown, JSON_UNESCAPED_UNICODE); ?>;
const categoryData = <?php echo json_encode($categoryBreakdown, JSON_UNESCAPED_UNICODE); ?>;

const sourcePie = echarts.init(document.getElementById('sourcePie'));
sourcePie.setOption({
    tooltip: {
        trigger: 'item',
        formatter: (p) => `${p.name}<br/>Records: <b>${Number(p.value || 0).toLocaleString()}</b><br/>Asilimia: <b>${Number(p.percent || 0).toFixed(1)}%</b>`
    },
    legend: { top: '5%', left: 'center' },
    series: [{
        name: 'Source',
        type: 'pie',
        radius: ['38%', '70%'],
        label: { formatter: '{b}: {d}%' },
        data: sourceData
    }]
});

const halfDonut = echarts.init(document.getElementById('sectionHalfDonut'));
halfDonut.setOption({
    tooltip: {
        trigger: 'item',
        formatter: (p) => `${p.name}<br/>Records: <b>${Number(p.value || 0).toLocaleString()}</b><br/>Asilimia: <b>${Number(p.percent || 0).toFixed(1)}%</b>`
    },
    legend: { top: '5%', left: 'center' },
    series: [{
        name: 'Category Share',
        type: 'pie',
        radius: ['40%', '72%'],
        label: { formatter: '{b}: {d}%' },
        data: categoryData
    }]
});

window.addEventListener('resize', function() {
    sourcePie.resize();
    halfDonut.resize();
});

(function() {
    const btn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar-wrapper');
    const overlay = document.getElementById('sidebar-overlay');
    const navLinks = document.querySelectorAll('.sidebar-link');
    function openSidebar() {
        sidebar.classList.add('sidebar-open');
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
        btn.querySelector('i').className = 'fas fa-times';
    }
    function closeSidebar() {
        sidebar.classList.remove('sidebar-open');
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        btn.querySelector('i').className = 'fas fa-bars';
    }
    btn.addEventListener('click', function() {
        sidebar.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
    });
    overlay.addEventListener('click', closeSidebar);
    navLinks.forEach(function(link) {
        link.addEventListener('click', closeSidebar);
    });
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            closeSidebar();
        }
    });
})();

function animateTileCounters() {
    const counters = document.querySelectorAll('.js-count');
    counters.forEach((el) => {
        const target = parseInt(el.getAttribute('data-count') || '0', 10);
        const duration = 1200;
        const start = performance.now();
        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const value = Math.round(target * eased);
            el.textContent = value.toLocaleString();
            if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    });
}

function animateTilesIn() {
    const tiles = document.querySelectorAll('.tile-box');
    tiles.forEach((tile, idx) => {
        setTimeout(() => tile.classList.add('tile-visible'), 120 + (idx * 70));
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        animateTilesIn();
        animateTileCounters();
    });
} else {
    animateTilesIn();
    animateTileCounters();
}
</script>
<script src="js/code_protection.js?v=<?php echo time(); ?>"></script>
</body>
</html>

