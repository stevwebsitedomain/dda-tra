<?php
require_once __DIR__ . '/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please Login First");
    exit();
}
$user_name = $_SESSION['user_name'] ?? 'Admin';
try { $conn = get_db_connection(); } catch (Exception $e) { die("DB error"); }

function c($conn, $sql){ $r=$conn->query($sql); if(!$r) return 0; $row=$r->fetch_assoc(); return (int)($row['c']??0); }
$tiktok = c($conn, "SELECT COUNT(*) c FROM category_data WHERE source='tiktok'");
$airbnb = c($conn, "SELECT COUNT(*) c FROM category_data WHERE source='airbnb'");
$booking = c($conn, "SELECT COUNT(*) c FROM category_data WHERE source='booking'");
$total = c($conn, "SELECT COUNT(*) c FROM category_data");

$regionRows = [];
$regionSql = "
    SELECT
        COALESCE(NULLIF(TRIM(c.category_name), ''), 'Uncategorized') AS region_name,
        COUNT(cd.id) AS total_count
    FROM data_categories c
    LEFT JOIN category_data cd ON cd.category_id = c.id
    GROUP BY c.id, c.category_name
    ORDER BY c.category_name ASC
";
$regionRes = $conn->query($regionSql);
if ($regionRes) {
    while ($r = $regionRes->fetch_assoc()) {
        $name = trim((string)($r['region_name'] ?? ''));
        if ($name === '') $name = 'Unknown';
        $regionRows[] = [
            'name' => $name,
            'value' => (int)($r['total_count'] ?? 0)
        ];
    }
}
$conn->close();

$regionTotal = 0;
foreach ($regionRows as $rr) {
    $regionTotal += (int)$rr['value'];
}
if ($regionTotal <= 0) {
    $regionRows = [['name' => 'No Region Data', 'value' => 0, 'percent' => 0]];
} else {
    foreach ($regionRows as &$rr) {
        $rr['percent'] = round(((int)$rr['value'] / $regionTotal) * 100, 1);
    }
    unset($rr);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TRA DDA | Analytics</title>
<link rel="icon" href="dda.jpg" type="image/jpeg">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="assets/responsive-layout.css" rel="stylesheet">
<link href="assets/dashboard-common.css" rel="stylesheet">
<style>
    :root { --sidebar-width:300px; --tra-navy:#0b1e3b; --tra-blue-gradient:linear-gradient(135deg,#0e2245 0%,#1c3d7a 100%); --accent-gold:#c5a059; --light-bg:#f4f7fa; }
    body { font-family:'Open Sans',sans-serif; background:var(--light-bg); margin:0; }
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
    .tt-header { background:#fff; min-height:65px; display:flex; align-items:center; justify-content:space-between; padding:0 30px; box-shadow:0 1px 3px rgba(0,0,0,.1); }
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
    .tiles-wrapper { display:grid; grid-template-columns:repeat(4,1fr); gap:15px; padding:20px 30px; margin-top:0; }
    .tile-box { background:#fff; padding:26px; min-height:126px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.08); display:flex; align-items:center; gap:16px; opacity:0; transform:translateY(14px); transition:opacity .45s ease, transform .45s ease; }
    .tile-box.tile-visible { opacity:1; transform:translateY(0); }
    .tile-box i { font-size:28px; color:var(--accent-gold); }
    .tile-stat { font-size:30px; font-weight:800; line-height:1; letter-spacing:.01em; }
    .tile-stat.tile-tiktok { color:#0f172a; }
    .tile-stat.tile-airbnb { color:#b91c1c; }
    .tile-stat.tile-booking { color:#1d4ed8; }
    .tile-stat.tile-total { color:#047857; }
    .tile-label { font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#64748b; margin-top:4px; }
    .card-custom { background:#fff; margin:20px 30px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.05); border:none; }
    .card-header-accent { border-top:3px solid var(--accent-gold); font-weight:700; background:#fff; padding:14px 20px; }
    .chart-scroll { overflow-x:auto; overflow-y:hidden; padding-bottom:8px; }
    .chart { height:560px; width:100%; min-width:1200px; }
    .mobile-menu-btn{display:none}
    .sidebar-overlay{display:none}
    @media (max-width:991px){ .mobile-menu-btn{display:flex;position:fixed;top:15px;left:15px;z-index:1001;width:44px;height:44px;border-radius:8px;background:var(--tra-navy);color:#fff;border:none;align-items:center;justify-content:center}
      .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999}
      .sidebar-wrapper{transform:translateX(-100%);transition:transform .3s ease;width:280px}.sidebar-wrapper.sidebar-open{transform:translateX(0)}
      .main-content{margin-left:0}.tt-header{padding:10px 15px 10px 60px}.tt-page-header{padding:25px 15px 20px}.tiles-wrapper{grid-template-columns:repeat(2,1fr);padding:15px;margin-top:0}.card-custom{margin:15px}
    }
    @media (max-width:575px){ .tiles-wrapper{grid-template-columns:1fr;} .tile-box{min-height:110px;padding:18px;} .tile-stat{font-size:26px;} }
</style>
</head>
<body>
<button type="button" class="mobile-menu-btn" id="mobile-menu-btn"><i class="fas fa-bars"></i></button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<aside class="sidebar-wrapper">
  <div class="sidebar-logo-section"><img src="<?php echo htmlspecialchars(tra_sidebar_logo_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="TRA LOGO"><div class="mt-2 font-weight-bold small text-uppercase">Tanzania Revenue Authority</div></div>
  <nav class="sidebar-nav">
    <a href="dashboard_home.php" class="sidebar-link"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="analytics.php" class="sidebar-link active"><i class="fas fa-chart-line"></i> Analytics</a>
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
  <header class="tt-header"><div class="font-weight-bold text-muted"><i class="fas fa-chart-pie mr-2"></i>ANALYTICS CENTER</div><div class="small text-muted">Institutional visual analytics</div></header>
  <section class="tt-page-header"><h1 class="h3 mb-1">Analytics</h1><div class="small">Muonekano na sidebar sawa na dashboard.</div></section>
  <div class="tiles-wrapper">
    <div class="tile-box"><i class="fab fa-tiktok"></i><div><div class="tile-stat tile-tiktok js-count" data-count="<?php echo (int)$tiktok; ?>">0</div><div class="tile-label">TikTok Records</div></div></div>
    <div class="tile-box"><i class="fas fa-house"></i><div><div class="tile-stat tile-airbnb js-count" data-count="<?php echo (int)$airbnb; ?>">0</div><div class="tile-label">Airbnb Records</div></div></div>
    <div class="tile-box"><i class="fas fa-hotel"></i><div><div class="tile-stat tile-booking js-count" data-count="<?php echo (int)$booking; ?>">0</div><div class="tile-label">Booking Records</div></div></div>
    <div class="tile-box"><i class="fas fa-layer-group"></i><div><div class="tile-stat tile-total js-count" data-count="<?php echo (int)$total; ?>">0</div><div class="tile-label">Total Records</div></div></div>
  </div>
  <div class="card card-custom">
    <div class="card-header-accent"><i class="fas fa-chart-bar mr-2"></i>Category Analytics</div>
    <div class="card-body chart-scroll"><div id="container" class="chart"></div></div>
  </div>
</main>
<script src="https://fastly.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
<script>
const regionData = <?php echo json_encode($regionRows, JSON_UNESCAPED_UNICODE); ?>;
const chartDom = document.getElementById('container');
const myChart = chartDom ? echarts.init(chartDom, null, { renderer: 'canvas', useDirtyRect: false }) : null;
const sourceRegions = Array.isArray(regionData) && regionData.length ? regionData : [{ name: 'No Category Data', value: 0, percent: 0 }];
const sourceNames = sourceRegions.map((r) => String(r.name || 'Unknown'));
const sourceRecords = sourceRegions.map((r) => Number(r.value || 0));
const sourcePercents = sourceRegions.map((r) => Number(r.percent || 0));
const regionPalette = ['#5470c6', '#91cc75', '#fac858', '#ee6666', '#73c0de', '#3ba272', '#fc8452', '#9a60b4', '#ea7ccc', '#2f4554', '#61a0a8', '#d48265'];
const regionColorMap = new Map();
sourceNames.forEach((name, idx) => {
  if (!regionColorMap.has(name)) {
    regionColorMap.set(name, regionPalette[idx % regionPalette.length]);
  }
});

if (myChart) {
  const barData = sourceRecords.map((val, i) => ({
    value: val,
    itemStyle: { color: regionColorMap.get(sourceNames[i]) || regionPalette[i % regionPalette.length] }
  }));

  myChart.setOption({
    animation: true,
    animationDuration: 1400,
    animationEasing: 'cubicOut',
    animationDurationUpdate: 700,
    title: { text: 'Bar Graph ya Kila Category' },
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'cross' },
      formatter: function (params) {
        if (!params || !params.length) return '';
        const idx = Number(params[0].dataIndex || 0);
        return `${sourceNames[idx]}<br/>Records: <b>${Number(sourceRecords[idx] || 0).toLocaleString()}</b><br/>Asilimia: <b>${Number(sourcePercents[idx] || 0).toFixed(1)}%</b>`;
      }
    },
    legend: { data: ['Category Records'] },
    toolbox: {
      show: true,
      feature: {
        dataView: { readOnly: true },
        restore: {},
        saveAsImage: {}
      }
    },
    dataZoom: [
      { type: 'inside', start: 0, end: 100 },
      { type: 'slider', start: 0, end: 100, height: 18, bottom: 16 }
    ],
    grid: { left: 70, right: 55, top: 70, bottom: 90 },
    xAxis: [{
      type: 'category',
      boundaryGap: true,
      data: sourceNames,
      axisLabel: { interval: 0, rotate: 25 }
    }],
    yAxis: [{ type: 'value', name: 'Records', min: 0 }],
    series: [
      {
        name: 'Category Records',
        type: 'bar',
        yAxisIndex: 0,
        data: barData,
        label: {
          show: true,
          position: 'top',
          formatter: (p) => `${Number(sourceRecords[p.dataIndex] || 0).toLocaleString()}`
        },
        itemStyle: { borderRadius: [0, 0, 0, 0] }
      }
    ]
  });

  window.addEventListener('resize', myChart.resize);
}

(function() {
 const btn=document.getElementById('mobile-menu-btn');
 const sidebar=document.querySelector('.sidebar-wrapper');
 const overlay=document.getElementById('sidebar-overlay');
 if(!btn||!sidebar||!overlay) return;
 function open(){sidebar.classList.add('sidebar-open');overlay.style.display='block';}
 function close(){sidebar.classList.remove('sidebar-open');overlay.style.display='none';}
 btn.addEventListener('click',()=>sidebar.classList.contains('sidebar-open')?close():open());
 overlay.addEventListener('click',close);
})();

function animateCount(el, target){
  const start = 0;
  const duration = 1200;
  const startTs = performance.now();
  function step(now){
    const p = Math.min((now - startTs) / duration, 1);
    const val = Math.floor(start + (target - start) * (1 - Math.pow(1 - p, 3)));
    el.textContent = val.toLocaleString();
    if (p < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}

function animateTilesAndCounters(){
  const tiles = document.querySelectorAll('.tile-box');
  tiles.forEach((tile, idx) => setTimeout(() => tile.classList.add('tile-visible'), 120 + idx * 70));
  document.querySelectorAll('.js-count').forEach((el) => {
    const target = parseInt(el.getAttribute('data-count') || '0', 10);
    animateCount(el, Number.isFinite(target) ? target : 0);
  });
}
animateTilesAndCounters();
</script>
<script src="js/code_protection.js"></script>
</body>
</html>

