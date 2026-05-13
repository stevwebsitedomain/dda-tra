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
    <title>TRA DDA | Airbnb Real Time</title>
    <link rel="icon" href="dda.jpg" type="image/jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/responsive-layout.css" rel="stylesheet">
    <link href="assets/dashboard-common.css" rel="stylesheet">
    <style>
        :root { --sidebar-width:260px; --tra-navy:#0b1e3b; --accent:#9333ea; --bg:#f5f3ff; --head:linear-gradient(135deg,#0e2245 0%,#1c3d7a 100%); --accent-gold:#c5a059; }
        body { margin:0; font-family:'Open Sans',sans-serif; background:var(--bg); }
        .sidebar-wrapper { width:var(--sidebar-width); background:var(--tra-navy); height:100vh; position:fixed; left:0; top:0; z-index:1000; color:#fff; overflow-y:auto; scrollbar-width:none; }
        .sidebar-wrapper::-webkit-scrollbar { display:none; }
        .sidebar-logo-section { padding:15px; text-align:center; background:rgba(0,0,0,.2); }
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
        .card-sidebar-info { background:rgba(255,255,255,.05); margin:15px; border-radius:8px; padding:15px; border-left:3px solid #c5a059; }
        .sidebar-link { padding:12px 25px; color:#bdc3c7; display:flex; align-items:center; text-decoration:none!important; font-size:14px; }
        .sidebar-link:hover,.sidebar-link.active { background:rgba(255,255,255,.1); color:#fff; }
        .sidebar-link i { margin-right:15px; width:20px; text-align:center; }
        .main-content { margin-left:var(--sidebar-width); min-height:100vh; overflow-x:auto; scrollbar-width:none; }
        .main-content::-webkit-scrollbar { display:none; }
        .top { background:#fff; min-height:64px; display:flex; align-items:center; justify-content:space-between; padding:0 30px; box-shadow:0 1px 3px rgba(0,0,0,.1); }
        .hero { background:var(--head); color:#fff; padding:16px 12px 12px; text-align:center; border-bottom:3px solid var(--accent-gold); }
        .hero h1 { margin:0; font-size:28px; font-weight:800; letter-spacing:.02em; }
        .hero p { margin:8px 0 0; opacity:.95; }
        .panel { margin:18px 30px; background:#fff; border-radius:10px; box-shadow:0 3px 12px rgba(0,0,0,.06); }
        .panel .head { padding:14px 18px; border-top:3px solid var(--accent); font-weight:700; }
        .panel .body { padding:18px; }
        .search-help { font-size:12px; color:#64748b; margin-bottom:10px; }
        .results-wrap { display:none; margin:0 30px 24px; background:#fff; border-radius:10px; box-shadow:0 3px 12px rgba(0,0,0,.06); padding:18px; }
        .badge-r { display:inline-flex; align-items:center; border:1px solid #e9d5ff; background:#faf5ff; color:#6b21a8; font-size:12px; font-weight:700; padding:4px 8px; border-radius:6px; }
        .table thead th { font-size:13px; text-transform:uppercase; letter-spacing:.04em; border-top:none; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
        .th-listing { color:#1d4ed8; }
        .th-host { color:#7c3aed; }
        .th-location { color:#0f766e; }
        .th-latlng { color:#1e40af; }
        .th-price { color:#047857; }
        .th-action { color:#b45309; }
        .loc-text { color:#0f766e; font-weight:700; }
        .price-text { color:#047857; font-weight:800; }
        .coords-link { color:#1d4ed8; font-weight:700; text-decoration:none; }
        .coords-link:hover { text-decoration:underline; color:#1e40af; }
        .direction-note { font-size:11px; color:#64748b; margin-top:4px; }
        .result-row { opacity:0; transform:translateY(8px); animation:resultIn .35s ease forwards; }
        @keyframes resultIn { to { opacity:1; transform:translateY(0); } }
        .overlay { display:none; position:fixed; inset:0; z-index:10000; background:rgba(255,255,255,.45); backdrop-filter:blur(8px); align-items:center; justify-content:center; }
        .overlay-card { background:#fff; border-radius:12px; padding:28px; width:92%; max-width:460px; text-align:center; box-shadow:0 12px 40px rgba(0,0,0,.14); }
        .loader-title { margin:6px 0 8px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; font-size:1.05rem; background:linear-gradient(90deg,#1a73e8,#34a853,#fbbc05,#ea4335); -webkit-background-clip:text; background-clip:text; color:transparent; animation:loaderTitleGlow 2.4s ease-in-out infinite; }
        .loader-text { color:#475569; font-weight:700; margin-bottom:0; animation:loaderTextPulse 1.4s ease-in-out infinite; }
        .google-spinner { width:54px; height:54px; margin:0 auto 10px; border-radius:50%; border:5px solid transparent; border-top-color:#1a73e8; border-right-color:#34a853; border-bottom-color:#fbbc05; border-left-color:#ea4335; animation:googleSpin 1s linear infinite; }
        @keyframes googleSpin { 100% { transform:rotate(360deg); } }
        @keyframes loaderTextPulse { 0%,100% { opacity:.75; } 50% { opacity:1; } }
        @keyframes loaderTitleGlow { 0%,100% { filter:drop-shadow(0 0 0 rgba(26,115,232,0)); } 50% { filter:drop-shadow(0 2px 8px rgba(26,115,232,.25)); } }
        .mobile-menu-btn { display:none; position:fixed; top:15px; left:15px; z-index:1001; width:44px; height:44px; border-radius:8px; background:var(--tra-navy); color:#fff; border:none; align-items:center; justify-content:center; font-size:20px; }
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:999; }
        @media (max-width:991px){ .mobile-menu-btn{display:flex} .sidebar-wrapper{transform:translateX(-100%);transition:transform .3s ease} .sidebar-wrapper.sidebar-open{transform:translateX(0)} .main-content{margin-left:0} .top{padding:10px 15px 10px 60px;flex-wrap:wrap} .panel,.results-wrap{margin:15px} }
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
        <a href="airbnb_realtime.php" class="sidebar-link active"><i class="fas fa-bolt"></i> Airbnb Real Time</a>
        <a href="airbnb2_engine.php" class="sidebar-link"><i class="fab fa-airbnb"></i> Airbnb 2</a>
        <a href="upgraded_airbnb.php" class="sidebar-link"><i class="fas fa-star"></i> Upgraded Airbnb</a>
        <a href="booking_engine.php" class="sidebar-link"><i class="fas fa-hotel"></i> Booking Engine</a>
        <a href="download.php" class="sidebar-link"><i class="fas fa-file-export"></i> Export Records</a>
        <a href="settings_dashboard.php" class="sidebar-link"><i class="fas fa-user-cog"></i> Settings</a>
        <a href="contact_developer.php" class="sidebar-link" title="Contact Developer"><i class="fas fa-headset"></i> Contact Developer</a>
        <a href="logout.php" class="sidebar-link text-danger mt-3"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
    <header class="top">
        <div class="font-weight-bold text-muted">AIRBNB REALTIME DETAIL ENGINE</div>
        <div class="small text-muted">
            <div id="status-text">Checking API...</div>
            <div id="user-location-text">Your location: detecting...</div>
        </div>
    </header>
    <section class="hero">
        <img src="uploads/airbnb2.png" alt="Airbnb Logo" class="airbnb-header-logo" onerror="this.style.display='none'">
    </section>

    <div class="panel">
        <div class="head"><i class="fas fa-magnifying-glass mr-2"></i>Realtime Search</div>
        <div class="body">
            <div class="search-help">Unaweza kuandika location text au room URLs/IDs nyingi (separate by comma, space, au new line).</div>
            <textarea id="query-input" class="form-control mb-3" rows="4" placeholder="dar es salaam&#10;https://www.airbnb.com/rooms/1066230"></textarea>
            <div class="d-flex align-items-center flex-wrap" style="gap:10px;">
                <label class="mb-0 small text-muted">Limit</label>
                <select id="limit-input" class="form-control form-control-sm" style="max-width:160px;">
                    <option value="100" selected>100</option>
                    <option value="200">200</option>
                    <option value="500">500</option>
                    <option value="1000">1000</option>
                    <option value="2000">2000</option>
                    <option value="5000">5000</option>
                    <option value="10000">10000</option>
                </select>
                <button class="btn btn-primary btn-sm" id="search-btn"><i class="fas fa-bolt mr-1"></i> Fetch Realtime</button>
                <button class="btn btn-success btn-sm" id="save-btn" disabled><i class="fas fa-save mr-1"></i> Save to Category</button>
            </div>
        </div>
    </div>

    <div class="results-wrap airbnb-results-watermark" id="results-wrap">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="th-listing"><i class="fas fa-building mr-1"></i>Listing</th>
                        <th class="th-host"><i class="fas fa-user-tie mr-1"></i>Host</th>
                        <th class="th-location"><i class="fas fa-map-marker-alt mr-1"></i>Location</th>
                        <th class="th-latlng"><i class="fas fa-route mr-1"></i>Lat/Lng</th>
                        <th class="th-price"><img src="uploads/price.jpg" alt="Price" class="price-icon-img mr-1">Price</th>
                        <th class="th-action"><i class="fas fa-hand-point-right mr-1"></i>Action</th>
                    </tr>
                </thead>
                <tbody id="results-body"></tbody>
            </table>
        </div>
    </div>
</main>

<div class="overlay" id="overlay">
    <div class="overlay-card">
        <div class="google-spinner" role="status" aria-label="Searching"></div>
        <h5 class="loader-title">AIRBNB REAL TIME</h5>
        <p class="loader-text" id="overlay-text">Fetching realtime listings...</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const ENDPOINT = 'airbnb_realtime_search.php';
const SAVE_ENDPOINT = 'save_to_db.php';
let latestResults = [];
let userOriginLabel = 'My Location';
let userOriginCoords = null;

function esc(v){return String(v??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}
function toggleOverlay(show, text){const o=document.getElementById('overlay');document.getElementById('overlay-text').textContent=text||'Processing...';o.style.display=show?'flex':'none';}

async function checkStatus(){
    try{
        const r=await fetch(`${ENDPOINT}?action=status`);
        document.getElementById('status-text').textContent=r.ok?'Realtime API Online':'Realtime API Offline';
    }catch(e){document.getElementById('status-text').textContent='Realtime API Offline';}
}

async function detectUserLocation() {
    const textEl = document.getElementById('user-location-text');
    if (!navigator.geolocation) {
        if (textEl) textEl.textContent = 'Your location: browser geolocation not supported.';
        return;
    }

    navigator.geolocation.getCurrentPosition(async (pos) => {
        const lat = pos.coords && Number.isFinite(pos.coords.latitude) ? pos.coords.latitude : null;
        const lng = pos.coords && Number.isFinite(pos.coords.longitude) ? pos.coords.longitude : null;
        if (lat == null || lng == null) {
            if (textEl) textEl.textContent = 'Your location: unable to read coordinates.';
            return;
        }
        userOriginCoords = { lat, lng };
        userOriginLabel = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        if (textEl) textEl.textContent = `Your location: ${userOriginLabel}`;

        try {
            const reverseUrl = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=14&addressdetails=1`;
            const r = await fetch(reverseUrl);
            if (!r.ok) return;
            const data = await r.json();
            const a = data.address || {};
            const place = [a.city || a.town || a.village || a.municipality || '', a.state || ''].filter(Boolean).join(', ');
            if (place) {
                userOriginLabel = place;
                if (textEl) textEl.textContent = `Your location: ${place}`;
            }
        } catch (e) {}
    }, () => {
        if (textEl) textEl.textContent = 'Your location: permission denied, using default.';
    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
}

function buildDirectionUrl(lat, lng) {
    const destination = `${lat},${lng}`;
    if (userOriginCoords && Number.isFinite(userOriginCoords.lat) && Number.isFinite(userOriginCoords.lng)) {
        const origin = `${userOriginCoords.lat},${userOriginCoords.lng}`;
        return `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}&travelmode=driving`;
    }
    return `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent('My Location')}&destination=${encodeURIComponent(destination)}&travelmode=driving`;
}

function renderRows(rows){
    const body=document.getElementById('results-body'); body.innerHTML='';
    if(!rows.length){ body.innerHTML='<tr><td colspan="6" class="text-center text-muted py-4">No records returned.</td></tr>'; return; }
    rows.forEach((item, idx)=>{
        const latlng=(item.lat&&item.lng)?`${item.lat}, ${item.lng}`:'N/A';
        const loc=[item.location_name,item.neighborhood].filter(Boolean).join(', ')||'N/A';
        const link=item.url?`<a class="btn btn-sm btn-outline-info" href="${encodeURI(item.url)}" target="_blank" rel="noopener">Open</a>`:'N/A';
        const coordsCell=(item.lat&&item.lng)
            ? `<a class="coords-link" href="${buildDirectionUrl(item.lat, item.lng)}" target="_blank" rel="noopener">${esc(latlng)}</a>
               <div class="direction-note">Direction from: ${esc(userOriginLabel)}</div>`
            : 'N/A';
        body.insertAdjacentHTML('beforeend', `
            <tr class="result-row" style="animation-delay:${Math.min(idx * 35, 420)}ms;">
                <td><span class="badge-r">${esc(item.name||item.listing_id||'N/A')}</span></td>
                <td>${esc(item.host_name||'N/A')}</td>
                <td><span class="loc-text">${esc(loc)}</span></td>
                <td>${coordsCell}</td>
                <td><span class="badge-r badge-price">${esc(item.room_price||'N/A')}</span></td>
                <td>${link}</td>
            </tr>
        `);
    });
}

async function runSearch(){
    const query=(document.getElementById('query-input').value||'').trim();
    const limit=parseInt(document.getElementById('limit-input').value,10)||100;
    if(!query){ Swal.fire({icon:'warning',title:'Input required',text:'Write location text or paste room URLs/IDs first.'}); return; }
    toggleOverlay(true,'Fetching realtime listings...');
    try{
        const r=await fetch(`${ENDPOINT}?query=${encodeURIComponent(query)}&limit=${encodeURIComponent(limit)}`);
        const data=await r.json();
        if(!r.ok||!data.success){ throw new Error(data.message||'Search failed'); }
        latestResults=Array.isArray(data.records)?data.records:[];
        renderRows(latestResults);
        document.getElementById('results-wrap').style.display='block';
        document.getElementById('save-btn').disabled=latestResults.length===0;
        Swal.fire({icon:'success',title:'Done',text:`${latestResults.length} realtime records loaded.`,timer:1400,showConfirmButton:false});
    }catch(e){
        Swal.fire({icon:'error',title:'Search failed',text:e.message||'Unknown error'});
    }finally{ toggleOverlay(false); }
}

async function saveToCategory(){
    if(!latestResults.length){ return; }
    let categories=[];
    try{
        const r=await fetch(`${SAVE_ENDPOINT}?action=list_categories`);
        const d=await r.json();
        if(r.ok&&d.success&&Array.isArray(d.categories)){ categories=d.categories.map(c=>c.category_name); }
    }catch(e){}
    const options=categories.map(n=>`<option value="${esc(n)}">${esc(n)}</option>`).join('');
    const x=await Swal.fire({
        title:'Save Realtime Results',
        html:`<div style="text-align:left;">
            <label style="font-weight:600;font-size:13px;">Existing Category</label>
            <select id="swal-cat-select" class="swal2-input" style="margin:8px 0 12px;"><option value="">-- Select category --</option>${options}</select>
            <div style="text-align:center;color:#64748b;font-size:12px;margin:4px 0;">OR</div>
            <label style="font-weight:600;font-size:13px;">New Category</label>
            <input id="swal-cat-new" class="swal2-input" placeholder="e.g. realtime_airbnb_feb_2026">
        </div>`,
        showCancelButton:true, confirmButtonText:'Save', cancelButtonText:'Cancel',
        preConfirm:()=>{
            const selected=(document.getElementById('swal-cat-select')?.value||'').trim();
            const created=(document.getElementById('swal-cat-new')?.value||'').trim();
            const category=created||selected;
            if(!category){ Swal.showValidationMessage('Select or type category name.'); return false; }
            return {category};
        }
    });
    if(!x.isConfirmed||!x.value||!x.value.category){ return; }
    toggleOverlay(true,'Saving to category...');
    try{
        const payloadRecords=latestResults.map(item=>({
            username:item.listing_id||item.name||'airbnb_realtime',
            phone:item.host_phone||'',
            bio:item.description||'',
            source:'airbnb_realtime',
            listing_id:item.listing_id||'',
            listing_url:item.url||'',
            host_name:item.host_name||'',
            host_phone:item.host_phone||'',
            location_name:item.location_name||'',
            neighborhood:item.neighborhood||'',
            latitude:item.lat!=null?String(item.lat):'',
            longitude:item.lng!=null?String(item.lng):'',
            room_price:item.room_price||'',
            description:item.description||item.name||''
        }));
        const r=await fetch(SAVE_ENDPOINT,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'save_batch_category',category_name:x.value.category,records:payloadRecords})});
        const d=await r.json();
        if(!r.ok||!d.success){ throw new Error(d.error||'Save failed'); }
        Swal.fire({icon:'success',title:'Saved',text:`Saved ${d.saved_category} records. Duplicates: ${d.duplicates}`});
    }catch(e){
        Swal.fire({icon:'error',title:'Save failed',text:e.message||'Unknown error'});
    }finally{ toggleOverlay(false); }
}

document.getElementById('search-btn').addEventListener('click', runSearch);
document.getElementById('save-btn').addEventListener('click', saveToCategory);
checkStatus(); setInterval(checkStatus,30000);
detectUserLocation();

(function(){const btn=document.getElementById('mobile-menu-btn');const sidebar=document.querySelector('.sidebar-wrapper');const overlay=document.getElementById('sidebar-overlay');if(!btn||!sidebar||!overlay)return;function open(){sidebar.classList.add('sidebar-open');overlay.style.display='block';}function close(){sidebar.classList.remove('sidebar-open');overlay.style.display='none';}btn.addEventListener('click',()=>sidebar.classList.contains('sidebar-open')?close():open());overlay.addEventListener('click',close);})();
</script>
<script src="js/code_protection.js"></script>
</body>
</html>

