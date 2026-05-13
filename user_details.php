<?php
session_start();
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require_once __DIR__ . '/config.php';

/* ================== INPUT ================== */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { die('Invalid ID'); }

/* ================== DB ================== */
try {
  $conn = get_db_connection();
} catch (Exception $e) {
  die("DB Connection failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

/* ================== HELPERS ================== */
function col_exists($conn, $table, $col){
  $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '".$conn->real_escape_string($col)."'");
  return ($res && $res->num_rows>0);
}
function detect_country_from_phone($phone){
  $p = preg_replace('/\D+/', '', $phone ?? '');
  if (!$p) return null;
  if (str_starts_with($p,'255')) return 'Tanzania';
  if (str_starts_with($p,'254')) return 'Kenya';
  if (str_starts_with($p,'256')) return 'Uganda';
  if (str_starts_with($p,'250')) return 'Rwanda';
  if (str_starts_with($p,'234')) return 'Nigeria';
  if (str_starts_with($p,'233')) return 'Ghana';
  if (str_starts_with($p,'27'))  return 'South Africa';
  if (str_starts_with($p,'44'))  return 'United Kingdom';
  if (str_starts_with($p,'353')) return 'Ireland';
  if (str_starts_with($p,'49'))  return 'Germany';
  if (str_starts_with($p,'33'))  return 'France';
  if (str_starts_with($p,'39'))  return 'Italy';
  if (str_starts_with($p,'34'))  return 'Spain';
  if (str_starts_with($p,'91'))  return 'India';
  if (str_starts_with($p,'86'))  return 'China';
  if (str_starts_with($p,'81'))  return 'Japan';
  if (str_starts_with($p,'92'))  return 'Pakistan';
  if (str_starts_with($p,'880')) return 'Bangladesh';
  if (str_starts_with($p,'61'))  return 'Australia';
  if (str_starts_with($p,'971')) return 'United Arab Emirates';
  if (str_starts_with($p,'977')) return 'Nepal';
  if (str_starts_with($p,'1'))   return 'United States';
  return null;
}
function resolve_display_country($phone, $country_col_val){
  $byPhone = detect_country_from_phone($phone);
  if ($byPhone) return $byPhone;
  return $country_col_val ?: 'Unknown';
}

/* ================== CSRF TOKEN ================== */
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['csrf'];

/* ================== EDIT / DELETE (POST) ================== */
$hasCountry = col_exists($conn, 'instagram_data', 'country');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['csrf']) && hash_equals($CSRF, $_POST['csrf'])) {
  if ($_POST['action'] === 'edit') {
    // Sanitize inputs
    $username = trim($_POST['username'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');
    $country  = $hasCountry ? trim($_POST['country'] ?? '') : null;

    // Prepare update
    if ($hasCountry) {
      $stmt = $conn->prepare("UPDATE instagram_data SET username=?, phone=?, bio=?, country=? WHERE id=? LIMIT 1");
      $stmt->bind_param("ssssi", $username, $phone, $bio, $country, $id);
    } else {
      $stmt = $conn->prepare("UPDATE instagram_data SET username=?, phone=?, bio=? WHERE id=? LIMIT 1");
      $stmt->bind_param("sssi", $username, $phone, $bio, $id);
    }
    if ($stmt && $stmt->execute()) {
      $updated = true;
    }
    if ($stmt) $stmt->close();

  } elseif ($_POST['action'] === 'delete') {
    $stmt = $conn->prepare("DELETE FROM instagram_data WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    if ($stmt && $stmt->execute()) {
      if ($stmt->affected_rows > 0) {
        if ($stmt) $stmt->close();
        $conn->close();
        header("Location: download.php?deleted=1");
        exit;
      }
    }
    if ($stmt) $stmt->close();
  }
}

/* ================== FETCH RECORD ================== */
$selectCols = "id, username, phone, bio, created_at".($hasCountry ? ", country" : "");
$sql = "SELECT $selectCols FROM instagram_data WHERE id=$id LIMIT 1";
$res = $conn->query($sql);
$rec = ($res && $res->num_rows) ? $res->fetch_assoc() : null;
if (!$rec) { $conn->close(); die('Record not found'); }
$conn->close();

/* ================== NAV AVATAR (SESSION) ================== */
$avatar = $_SESSION['profile']['avatar'] ?? '';
$display_name = $_SESSION['profile']['display_name'] ?? 'User';
$initial = strtoupper(substr($display_name,0,1));

/* ================== VIEW DATA ================== */
$username = $rec['username'] ?? 'Unknown';
$phone    = $rec['phone'] ?? '';
$bio      = $rec['bio'] ?? '';
$created  = $rec['created_at'] ?? '';
$country_col_val = $hasCountry ? ($rec['country'] ?? '') : '';
$country_disp = resolve_display_country($phone, $country_col_val);

/* JSON PREVIEW */
$jsonPreview = json_encode($rec, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>View Result — #<?php echo (int)$rec['id']; ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
:root{ --bg:#f6f7fb; --card:#fff; --text:#0f172a; --muted:#64748b; --border:#eef1f5; --primary:#ff7a00; }
body{background:var(--bg); color:var(--text); font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial,sans-serif}

/* NAV */
.navbar-brand .badge-brand{background:#ff7a00}
.nav-avatar{width:32px;height:32px;border-radius:50%;object-fit:cover;background:linear-gradient(135deg,#ddd,#bbb);}

/* HERO CARD */
.card-hero{border:1px solid var(--border);border-radius:16px;box-shadow:0 10px 30px rgba(16,24,40,.06);background:#fff;opacity:0;transform:translateY(8px);transition:all .5s ease}
.card-hero.show{opacity:1;transform:translateY(0)}
.badge-soft{background:#fff4e9;border:1px solid #ffe1c6;color:#a04b00}
.meta dt{color:#64748b;width:160px}
.meta dd{margin-bottom:.5rem}
.bio-box{border:1px dashed #fed7aa;background:#fff8f1;border-radius:12px}

/* BUTTONS */
.btn-orange{background:#ff7a00;color:#fff;border-color:#ff7a00}
.btn-orange:hover{background:#ff8f2b;border-color:#ff8f2b}
.btn-danger-soft{background:#fff1f2;color:#dc2626;border:1px solid #fecdd3}

/* MODALS */
.modal-content{border-radius:14px;box-shadow:0 12px 34px rgba(16,24,40,.12)}
pre.json{background:#0f172a;color:#e2e8f0;border-radius:10px;padding:12px;max-height:60vh;overflow:auto}

/* EXCEL PREVIEW TABLE */
.excel-wrap{border:1px solid #e2e8f0;border-radius:10px;overflow:auto;background:#fff}
.excel-table{width:100%;border-collapse:separate;border-spacing:0}
.excel-table thead th{position:sticky;top:0;background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:10px 12px;font-weight:700}
.excel-table td{border-bottom:1px solid #f1f5f9;padding:10px 12px}
.excel-table tr:hover td{background:#fafcff}
</style>
</head>
<body>

<!-- NAV -->
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container-fluid px-3 px-md-4">
    <a class="navbar-brand d-flex align-items-center gap-2" href="download.php">
      <span class="badge rounded-2 badge-brand text-white px-2 py-1 fw-bold">Cense7</span>
      <span class="fw-semibold">View Result</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="download.php">← Back to List</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
            <?php if($avatar): ?>
              <img class="nav-avatar me-2" src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar">
            <?php else: ?>
              <span class="nav-avatar me-2 d-inline-flex align-items-center justify-content-center text-white" style="background:#ff7a00"><?php echo $initial; ?></span>
            <?php endif; ?>
            Profile
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
            <li><a class="dropdown-item" href="settings.php">Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container px-3 px-md-4 my-3">
  <div class="card-hero p-3 p-md-4 fade show">
    <div class="d-flex align-items-start justify-content-between flex-wrap">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-white" style="width:56px;height:56px;background:#ff7a00;font-weight:800;">
          <?php echo strtoupper(substr($username ?: 'U',0,1)); ?>
        </div>
        <div>
          <h4 class="mb-0"><?php echo htmlspecialchars($username); ?></h4>
          <div class="text-muted">Record #<?php echo (int)$rec['id']; ?> • <?php echo date('M d, Y H:i', strtotime($created)); ?></div>
        </div>
      </div>
      <div class="d-flex gap-2 mt-3 mt-md-0">
        <a class="btn btn-outline-secondary" href="download.php">Back</a>
        <a class="btn btn-orange" href="export.php?format=csv&id=<?php echo (int)$rec['id']; ?>">Export CSV</a>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#jsonModal">Preview JSON</button>
        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#excelModal">Preview Excel</button>
      </div>
    </div>

    <hr>

    <div class="row g-3">
      <div class="col-12 col-lg-7">
        <div class="bio-box p-3">
          <div class="fw-semibold mb-2">Bio</div>
          <div class="<?php echo $bio ? '' : 'text-muted'; ?>">
            <?php echo $bio ? nl2br(htmlspecialchars($bio)) : 'No bio provided.'; ?>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-5">
        <dl class="row meta">
          <dt class="col-sm-5">Username</dt><dd class="col-sm-7"><?php echo htmlspecialchars($username); ?></dd>
          <dt class="col-sm-5">Phone</dt><dd class="col-sm-7"><?php echo $phone ? htmlspecialchars($phone) : '<span class="text-muted">N/A</span>'; ?></dd>
          <dt class="col-sm-5">Country</dt><dd class="col-sm-7"><?php echo htmlspecialchars($country_disp); ?></dd>
          <dt class="col-sm-5">Created</dt><dd class="col-sm-7"><?php echo date('M d, Y H:i', strtotime($created)); ?></dd>
          <dt class="col-sm-5">Status</dt><dd class="col-sm-7">
            <?php if($phone): ?><span class="badge bg-success-subtle text-success border">Phone ✓</span><?php endif; ?>
            <?php if($bio): ?><span class="badge bg-primary-subtle text-primary border">Bio ✓</span><?php endif; ?>
          </dd>
        </dl>
      </div>
    </div>

    <hr class="my-3">

    <!-- EDIT / DELETE CONTROLS -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#editForm">Edit</button>
      <button class="btn btn-danger-soft" data-bs-toggle="modal" data-bs-target="#deleteModal">Delete</button>
    </div>

    <!-- EDIT FORM -->
    <div class="collapse mt-3" id="editForm">
      <div class="card card-body border-0" style="background:#fffaf3">
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($CSRF); ?>">
          <input type="hidden" name="action" value="edit">
          <div class="col-md-6">
            <label class="form-label">Username</label>
            <input class="form-control" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input class="form-control" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Bio</label>
            <textarea class="form-control" name="bio" rows="3"><?php echo htmlspecialchars($bio); ?></textarea>
          </div>
          <?php if($hasCountry): ?>
          <div class="col-md-6">
            <label class="form-label">Country</label>
            <input class="form-control" name="country" value="<?php echo htmlspecialchars($country_col_val); ?>">
            <div class="form-text">* Ikiwa hautajaza, nchi itaamuliwa na namba ya simu.</div>
          </div>
          <?php endif; ?>
          <div class="col-12">
            <button class="btn btn-orange">Save Changes</button>
            <a class="btn btn-outline-secondary" href="user_details.php?id=<?php echo (int)$rec['id']; ?>">Cancel</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<!-- JSON MODAL -->
<div class="modal fade" id="jsonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">JSON Preview — #<?php echo (int)$rec['id']; ?></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <pre class="json" id="jsonBox"><?php echo htmlspecialchars($jsonPreview); ?></pre>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" id="copyJson">Copy JSON</button>
        <a class="btn btn-orange" href='data:application/json;charset=utf-8,<?php echo rawurlencode($jsonPreview); ?>' download="record_<?php echo (int)$rec['id']; ?>.json">Download JSON</a>
      </div>
    </div>
  </div>
</div>

<!-- EXCEL-LIKE PREVIEW MODAL -->
<div class="modal fade" id="excelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Excel Preview — #<?php echo (int)$rec['id']; ?></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="excel-wrap">
          <table class="excel-table">
            <thead>
              <tr><th>Field</th><th>Value</th></tr>
            </thead>
            <tbody>
              <tr><td>ID</td><td><?php echo (int)$rec['id']; ?></td></tr>
              <tr><td>Username</td><td><?php echo htmlspecialchars($username); ?></td></tr>
              <tr><td>Phone</td><td><?php echo $phone ? htmlspecialchars($phone) : 'N/A'; ?></td></tr>
              <tr><td>Country</td><td><?php echo htmlspecialchars($country_disp); ?></td></tr>
              <tr><td>Bio</td><td><?php echo $bio ? nl2br(htmlspecialchars($bio)) : 'No bio'; ?></td></tr>
              <tr><td>Created At</td><td><?php echo date('M d, Y H:i', strtotime($created)); ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <a class="btn btn-outline-success" href="export.php?format=excel&id=<?php echo (int)$rec['id']; ?>">Download Excel</a>
        <a class="btn btn-outline-primary" href="export.php?format=csv&id=<?php echo (int)$rec['id']; ?>">Download CSV</a>
      </div>
    </div>
  </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="post">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($CSRF); ?>">
      <input type="hidden" name="action" value="delete">
      <div class="modal-header">
        <h5 class="modal-title text-danger">Confirm Delete</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Una uhakika unataka kufuta rekodi <strong>#<?php echo (int)$rec['id']; ?></strong> (<?php echo htmlspecialchars($username); ?>)?
        Hatua hii haiwezi kurudishwa nyuma.
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger" type="submit">Yes, Delete</button>
      </div>
    </form>
  </div>
</div>

<script>
// On-load animation for hero card
window.addEventListener('DOMContentLoaded', ()=> {
  document.querySelector('.card-hero')?.classList.add('show');
});

// Copy JSON
document.getElementById('copyJson')?.addEventListener('click', ()=>{
  const txt = document.getElementById('jsonBox').innerText;
  navigator.clipboard.writeText(txt).then(()=>{
    const btn = document.getElementById('copyJson');
    const old = btn.textContent;
    btn.textContent = 'Copied!';
    setTimeout(()=> btn.textContent = old, 1200);
  });
});
</script>
<script src="js/code_protection.js?v=<?php echo time(); ?>"></script>
</body>
</html>
