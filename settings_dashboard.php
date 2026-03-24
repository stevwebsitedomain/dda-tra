<?php
require_once __DIR__ . '/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please Login First");
    exit();
}
$user_name = $_SESSION['user_name'] ?? 'Admin';
$saved = false;
$errors = [];

$defaults = [
    'theme' => 'light',
    'language' => 'sw',
    'results_per_page' => 10,
    'api_limit' => 30,
    'data_retention' => 30
];
$allowedThemes = ['light', 'dark', 'blue'];
$allowedLanguages = ['sw', 'en'];
$allowedPerPage = [10, 25, 50, 100];

if (!isset($_SESSION['settings']) || !is_array($_SESSION['settings'])) {
    $_SESSION['settings'] = $defaults;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = trim((string)($_POST['theme'] ?? $defaults['theme']));
    if (!in_array($theme, $allowedThemes, true)) {
        $theme = $defaults['theme'];
    }

    $language = trim((string)($_POST['language'] ?? $defaults['language']));
    if (!in_array($language, $allowedLanguages, true)) {
        $language = $defaults['language'];
    }

    $resultsPerPageInput = (int)($_POST['results_per_page'] ?? $defaults['results_per_page']);
    if (!in_array($resultsPerPageInput, $allowedPerPage, true)) {
        $resultsPerPageInput = $defaults['results_per_page'];
    }

    $dataRetentionInput = (int)($_POST['data_retention'] ?? $defaults['data_retention']);
    if ($dataRetentionInput < 1 || $dataRetentionInput > 3650) {
        $errors[] = 'Data retention lazima iwe kati ya 1 na 3650.';
        $dataRetentionInput = $defaults['data_retention'];
    }

    $apiLimitInput = (int)($_POST['api_limit'] ?? $defaults['api_limit']);
    if ($apiLimitInput < 1 || $apiLimitInput > 5000) {
        $errors[] = 'API limit lazima iwe kati ya 1 na 5000.';
        $apiLimitInput = $defaults['api_limit'];
    }

    $_SESSION['settings'] = [
        'theme' => $theme,
        'language' => $language,
        'results_per_page' => $resultsPerPageInput,
        'data_retention' => $dataRetentionInput,
        'api_limit' => $apiLimitInput
    ];
    $_SESSION['settings_saved_at'] = date('Y-m-d H:i:s');
    $saved = count($errors) === 0;
}

$settings = array_merge($defaults, $_SESSION['settings']);
$resultsPerPage = (int)$settings['results_per_page'];
$apiLimit = (int)$settings['api_limit'];
$retentionDays = (int)$settings['data_retention'];
$configuredFields = count($settings);
$lastSavedAt = $_SESSION['settings_saved_at'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRA DDA | Settings</title>
    <link rel="icon" href="dda.jpg" type="image/jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/responsive-layout.css" rel="stylesheet">
<link href="assets/dashboard-common.css" rel="stylesheet">
    <style>
        :root { --sidebar-width:300px; --tra-navy:#0b1e3b; --tra-blue-gradient:linear-gradient(135deg,#0e2245 0%,#1c3d7a 100%); --accent-gold:#c5a059; --light-bg:#f4f7fa; }
        body { font-family:'Open Sans',sans-serif; background:var(--light-bg); margin:0; overflow-x:hidden; -webkit-text-size-adjust:100%; }
        .sidebar-wrapper { width:var(--sidebar-width); background:var(--tra-navy); height:100vh; position:fixed; left:0; top:0; z-index:1000; color:#fff; overflow-y:auto; scrollbar-width:none; -ms-overflow-style:none; }
        .sidebar-wrapper::-webkit-scrollbar { display:none; }
        .sidebar-logo-section { padding:15px; text-align:center; background:rgba(0,0,0,.2); }
        .sidebar-logo-section img { width:100%; height:100px; border-radius:0; background:#fff; padding:0; object-fit:cover; object-position:center; display:block; }
        .card-sidebar-info { background:rgba(255,255,255,.05); margin:15px; border-radius:8px; padding:15px; border-left:3px solid var(--accent-gold); }
        .sidebar-link { padding:12px 25px; color:#bdc3c7; display:flex; align-items:center; text-decoration:none!important; transition:.3s; font-size:14px; }
        .sidebar-link:hover,.sidebar-link.active { background:rgba(255,255,255,.1); color:#fff; }
        .sidebar-link i { margin-right:15px; width:20px; text-align:center; }
        .sidebar-wrapper { display:flex; flex-direction:column; }
        .sidebar-nav { flex:1; }
        .sidebar-api-status { background:rgba(255,255,255,.06); }
        .api-status-icon { font-size:8px; color:#94a3b8; }
        .api-status-icon.live { color:#22c55e; }
        .api-status-icon.offline { color:#ef4444; }
        .api-status-text { color:#94a3b8; }
        .api-status-text.live { color:#22c55e; }
        .api-status-text.offline { color:#ef4444; }
        .sidebar-coat-section { display:none !important; }
        .main-content { margin-left:var(--sidebar-width); min-height:100vh; }
        .tt-header { background:#fff; min-height:65px; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:0 30px; box-shadow:0 1px 3px rgba(0,0,0,.1); }
        .header-title { min-width:0; line-height:1.35; }
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
        @keyframes headerGlowFloat {
            0%, 100% { transform: translateY(0); text-shadow: 0 3px 14px rgba(6, 20, 45, 0.45); }
            50% { transform: translateY(-2px); text-shadow: 0 6px 16px rgba(6, 20, 45, 0.55); }
        }
        .tiles-wrapper { display:grid; grid-template-columns:repeat(4,1fr); gap:15px; padding:20px 30px; margin-top:0; }
        .tile-box { background:#fff; padding:26px; min-height:126px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.08); display:flex; align-items:center; gap:16px; opacity:0; transform:translateY(12px); transition:opacity .45s ease, transform .45s ease; }
        .tile-box.tile-visible { opacity:1; transform:translateY(0); }
        .tile-box i { font-size:28px; color:var(--accent-gold); }
        .tile-stat { font-size:30px; font-weight:800; line-height:1; letter-spacing:.01em; }
        .tile-stat.tile-config { color:#7c3aed; }
        .tile-stat.tile-results { color:#0f766e; }
        .tile-stat.tile-api { color:#1d4ed8; }
        .tile-stat.tile-retention { color:#b91c1c; }
        .tile-label { font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:.05em; margin-top:4px; }
        .card-custom { background:#fff; margin:20px 30px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.05); border:none; }
        .card-header-accent { border-top:3px solid var(--accent-gold); font-weight:700; background:#fff; padding:14px 20px; }
        .form-label { font-weight:600; color:#334155; }
        .mobile-menu-btn{display:none}.sidebar-overlay{display:none}
        @media (max-width:991px){ .mobile-menu-btn{display:flex;position:fixed;top:15px;left:15px;z-index:1001;width:44px;height:44px;border-radius:8px;background:var(--tra-navy);color:#fff;border:none;align-items:center;justify-content:center}
          .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999}
          .sidebar-wrapper{transform:translateX(-100%);transition:transform .3s ease;width:280px}.sidebar-wrapper.sidebar-open{transform:translateX(0)}
          .main-content{margin-left:0}.tt-header{padding:10px 15px 10px 60px;flex-wrap:wrap}.header-title{font-size:12px}.tt-page-header{padding:25px 15px 20px}.tiles-wrapper{grid-template-columns:repeat(2,1fr);padding:15px;margin-top:0}.card-custom{margin:15px}
        }
        @media (max-width:575px){ .tiles-wrapper{grid-template-columns:1fr;} .tile-box{min-height:110px;padding:18px;} .tile-stat{font-size:26px;} .tt-header{padding-left:15px;padding-right:15px;} .header-title{width:100%;} }
    </style>
</head>
<body>
<button type="button" class="mobile-menu-btn" id="mobile-menu-btn"><i class="fas fa-bars"></i></button>
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
        <a href="settings_dashboard.php" class="sidebar-link active"><i class="fas fa-user-cog"></i> Settings</a>
        <a href="contact_developer.php" class="sidebar-link" title="Contact Developer"><i class="fas fa-headset"></i> Contact Developer</a>
        <a href="logout.php" class="sidebar-link text-danger mt-3"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
    <div class="sidebar-coat-section">
        <img src="coat_of_arms.png" alt="Coat of Arms" class="sidebar-coat-img" onerror="this.style.display='none'">
    </div>
</aside>

<main class="main-content">
    <header class="tt-header">
        <div class="font-weight-bold text-muted header-title"><i class="fas fa-user-cog mr-2"></i>SETTINGS CONTROL</div>
        <div class="small text-muted">Institutional configuration</div>
    </header>
    <section class="tt-page-header">
        <h1 class="h3 mb-1">Settings</h1>
        <div class="small">Muonekano wa settings umewekwa sawa na dashboard + sidebar.</div>
    </section>
    <div class="tiles-wrapper">
        <div class="tile-box"><i class="fas fa-sliders-h"></i><div><div class="tile-stat tile-config js-count" data-count="<?php echo $configuredFields; ?>">0</div><div class="tile-label">Configured Fields</div></div></div>
        <div class="tile-box"><i class="fas fa-list-ol"></i><div><div class="tile-stat tile-results js-count" data-count="<?php echo max(0, $resultsPerPage); ?>">0</div><div class="tile-label">Results Per Page</div></div></div>
        <div class="tile-box"><i class="fas fa-tachometer-alt"></i><div><div class="tile-stat tile-api js-count" data-count="<?php echo max(0, $apiLimit); ?>">0</div><div class="tile-label">API Limit / Min</div></div></div>
        <div class="tile-box"><i class="fas fa-calendar-alt"></i><div><div class="tile-stat tile-retention js-count" data-count="<?php echo max(0, $retentionDays); ?>">0</div><div class="tile-label">Retention Days</div></div></div>
    </div>

    <div class="card card-custom">
        <div class="card-header-accent"><i class="fas fa-sliders-h mr-2"></i>System Preferences</div>
        <div class="card-body">
            <?php if ($saved): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>Settings saved successfully.</div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars(implode(' ', $errors)); ?>
                </div>
            <?php endif; ?>
            <?php if ($lastSavedAt !== ''): ?>
                <div class="small text-muted mb-3">Last saved: <?php echo htmlspecialchars($lastSavedAt); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label class="form-label">Theme</label>
                        <select class="form-control" name="theme">
                            <option value="light" <?php echo (($settings['theme'] ?? 'light') === 'light') ? 'selected' : ''; ?>>Light</option>
                            <option value="dark" <?php echo (($settings['theme'] ?? '') === 'dark') ? 'selected' : ''; ?>>Dark</option>
                            <option value="blue" <?php echo (($settings['theme'] ?? '') === 'blue') ? 'selected' : ''; ?>>Blue</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="form-label">Language</label>
                        <select class="form-control" name="language">
                            <option value="sw" <?php echo (($settings['language'] ?? 'sw') === 'sw') ? 'selected' : ''; ?>>Kiswahili</option>
                            <option value="en" <?php echo (($settings['language'] ?? '') === 'en') ? 'selected' : ''; ?>>English</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="form-label">Results per page</label>
                        <select class="form-control" name="results_per_page">
                            <option value="10" <?php echo $resultsPerPage === 10 ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo $resultsPerPage === 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $resultsPerPage === 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $resultsPerPage === 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="form-label">Data retention (days)</label>
                        <input type="number" class="form-control" name="data_retention" min="1" max="3650" value="<?php echo htmlspecialchars((string)$retentionDays); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="form-label">API request limit / minute</label>
                        <input type="number" class="form-control" name="api_limit" min="1" max="5000" value="<?php echo htmlspecialchars((string)$apiLimit); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save mr-2"></i>Save Settings</button>
            </form>
        </div>
    </div>
</main>

<script>
(function() {
 const btn=document.getElementById('mobile-menu-btn');
 const sidebar=document.querySelector('.sidebar-wrapper');
 const overlay=document.getElementById('sidebar-overlay');
 const navLinks=document.querySelectorAll('.sidebar-link');
 if(!btn||!sidebar||!overlay) return;
 function open(){sidebar.classList.add('sidebar-open');overlay.style.display='block';document.body.style.overflow='hidden';}
 function close(){sidebar.classList.remove('sidebar-open');overlay.style.display='none';document.body.style.overflow='';}
 btn.addEventListener('click',()=>sidebar.classList.contains('sidebar-open')?close():open());
 overlay.addEventListener('click',close);
 navLinks.forEach((link)=>link.addEventListener('click', close));
 window.addEventListener('resize', function() {
   if (window.innerWidth > 991) close();
 });
})();

function animateCount(el, target){
 const duration = 1100;
 const startTs = performance.now();
 function step(now){
   const p = Math.min((now - startTs) / duration, 1);
   const val = Math.floor(target * (1 - Math.pow(1 - p, 3)));
   el.textContent = val.toLocaleString();
   if (p < 1) requestAnimationFrame(step);
 }
 requestAnimationFrame(step);
}

(function animateTiles(){
 const tiles = document.querySelectorAll('.tile-box');
 tiles.forEach((tile, idx) => setTimeout(() => tile.classList.add('tile-visible'), 120 + idx * 70));
 document.querySelectorAll('.js-count').forEach((el) => {
   const target = parseInt(el.getAttribute('data-count') || '0', 10);
   animateCount(el, Number.isFinite(target) ? target : 0);
 });
})();

async function checkApiStatus() {
  const icon = document.getElementById('api-status-icon');
  const text = document.getElementById('api-status-text');
  if (!icon || !text) return;
  try {
    const res = await fetch('tiktok_search.php?action=status', { method: 'GET' });
    if (res.ok) {
      icon.classList.remove('offline');
      icon.classList.add('live');
      text.classList.remove('offline');
      text.classList.add('live');
      text.textContent = 'API Live';
      return;
    }
    throw new Error('offline');
  } catch (e) {
    icon.classList.remove('live');
    icon.classList.add('offline');
    text.classList.remove('live');
    text.classList.add('offline');
    text.textContent = 'API Offline';
  }
}

checkApiStatus();
setInterval(checkApiStatus, 30000);
</script>
<script src="js/code_protection.js"></script>
</body>
</html>

