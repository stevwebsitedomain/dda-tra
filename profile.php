<?php
session_start();
require_once __DIR__ . '/config.php';

/* ------------------ Default session profile ------------------ */
if (!isset($_SESSION['profile'])) {
  $_SESSION['profile'] = [
    'display_name' => 'Administrator',
    'email'        => 'admin@example.com',
    'phone'        => '+255 712 000 000',
    'role'         => 'Owner',
    'avatar'       => ''  // path to uploaded avatar
  ];
}
$me = $_SESSION['profile'];
$notice = null;
$toast_success = null; // for English toast after avatar upload

/* ------------------ CSRF ------------------ */
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$CSRF = $_SESSION['csrf'];

/* ------------------ Handle avatar upload ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '')==='upload_avatar') {
  if (!isset($_POST['csrf']) || !hash_equals($CSRF, $_POST['csrf'])) {
    $notice = ['type'=>'danger','msg'=>'CSRF token mismatch. Please reload the page.'];
  } elseif (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $notice = ['type'=>'danger','msg'=>'No image uploaded or an error occurred.'];
  } else {
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $mime = mime_content_type($_FILES['avatar']['tmp_name']);
    $size = $_FILES['avatar']['size'];
    if (!isset($allowed[$mime])) {
      $notice = ['type'=>'danger','msg'=>'Allowed: JPG, PNG, WEBP only.'];
    } elseif ($size > 3*1024*1024) {
      $notice = ['type'=>'danger','msg'=>'Max size is 3MB. Please compress the image.'];
    } else {
      $dir = __DIR__ . '/uploads';
      if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
      $ext = $allowed[$mime];
      $name = 'avatar_'.session_id().'_'.time().'.'.$ext;
      $path = $dir . '/' . $name;
      if (move_uploaded_file($_FILES['avatar']['tmp_name'], $path)) {
        $url = 'uploads/'.$name;
        $_SESSION['profile']['avatar'] = $url;
        $me['avatar'] = $url;
        // English popup toast:
        $toast_success = 'Profile picture uploaded successfully.';
      } else {
        $notice = ['type'=>'danger','msg'=>'Failed to save the image.'];
      }
    }
  }
}

/* ------------------ Handle profile save ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '')==='save_profile') {
  if (!isset($_POST['csrf']) || !hash_equals($CSRF, $_POST['csrf'])) {
    $notice = ['type'=>'danger','msg'=>'CSRF token mismatch. Please reload the page.'];
  } else {
    $_SESSION['profile']['display_name'] = trim($_POST['display_name'] ?? $me['display_name']);
    $_SESSION['profile']['email']        = trim($_POST['email'] ?? $me['email']);
    $_SESSION['profile']['phone']        = trim($_POST['phone'] ?? $me['phone']);
    $_SESSION['profile']['role']         = trim($_POST['role'] ?? $me['role']);
    $me = $_SESSION['profile'];
    $notice = ['type'=>'success','msg'=>'Profile details saved.'];
  }
}

/* ------------------ Optional stats from your DB ------------------ */
$stats = ['total'=>0,'projects'=>0,'last7'=>0];
$mysqli = null;
try { $mysqli = get_db_connection(); } catch (Exception $e) { $mysqli = null; }
if ($mysqli) {
  $row = $mysqli->query("SELECT 
    COUNT(*) AS total_records,
    COUNT(DISTINCT username) AS total_projects,
    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS last7
  FROM instagram_data")->fetch_assoc();
  $stats['total']    = (int)($row['total_records'] ?? 0);
  $stats['projects'] = (int)($row['total_projects'] ?? 0);
  $stats['last7']    = (int)($row['last7'] ?? 0);
  $mysqli->close();
}

/* ------------------ Helpers ------------------ */
$avatar = $me['avatar'] ?: '';
$initial = strtoupper(substr($me['display_name'],0,1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DDA-TRA — Profile</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

<style>
:root{
  /* Light / white theme */
  --bg:#f6f7fb; 
  --card:#ffffff; 
  --text:#0f172a; 
  --muted:#64748b; 
  --accent:#ff7a00; 
  --accent2:#ff9a3b; 
  --border:#e9eef5;
}
*{box-sizing:border-box}
body{
  min-height:100vh; background:var(--bg); color:var(--text);
  font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial,sans-serif
}

/* NAV */
.navbar{
  background:#ffffff;
  border-bottom:1px solid var(--border);
}
.navbar-brand .badge-brand{background:var(--accent)}
.nav-link,.navbar-brand span{color:#111827}
.nav-avatar{width:32px;height:32px;border-radius:50%;object-fit:cover;background:#ddd}

/* HERO */
.hero{
  position:relative;border:1px solid var(--border); border-radius:14px;
  background:linear-gradient(135deg, rgba(255,122,0,.10), rgba(255,255,255,.6));
  padding:16px; overflow:hidden;
}

/* CARDS (white) */
.card-lite{
  background:var(--card); border:1px solid var(--border); border-radius:16px;
  box-shadow:0 8px 22px rgba(16,24,40,.05);
  transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease;
}
.card-lite:hover{ transform: translateY(-2px); box-shadow:0 14px 32px rgba(16,24,40,.08); }

/* PROFILE AVATAR */
.profile-avatar{
  width:104px;height:104px;border-radius:18px; overflow:hidden; position:relative;
  display:flex;align-items:center;justify-content:center; font-weight:900; font-size:34px;
  background:#f1f5f9; border:1px solid var(--border);
}
.profile-avatar img{width:100%;height:100%;object-fit:cover}
.badge-soft{background:#fff4e9;color:#8a3b00;border:1px solid #ffe1c6}

/* KPI mini */
.kpi-mini{background:#ffffff; border:1px solid var(--border); border-radius:12px; text-align:center;}
.kpi-mini .num{font-weight:800;font-size:20px}
.kpi-mini .lbl{font-size:12px;color:var(--muted)}

/* INPUTS */
.form-control, .form-select{
  background:#ffffff; border:1px solid #e5e7eb; color:#111827;
}
.form-control:focus, .form-select:focus{
  border-color:#93c5fd; box-shadow:0 0 0 .18rem rgba(147,197,253,.25);
}
.form-text{color:#6b7280}

/* BUTTONS */
.btn-accent{background:linear-gradient(90deg,var(--accent),var(--accent2)); border:none; color:#fff !important; font-weight:700}
.btn-accent:hover{filter:brightness(1.05)}
.btn-outline-lite{border:1px solid var(--border); color:#111827; background:#fff}
.btn-outline-lite:hover{background:#f9fafb}

/* ANIMATIONS */
.fade-in{opacity:0; transform:translateY(10px); animation:fadeIn .5s forwards}
.fade-in.d2{animation-delay:.1s} .fade-in.d3{animation-delay:.2s} .fade-in.d4{animation-delay:.3s}
@keyframes fadeIn{to{opacity:1; transform:translateY(0)}}

/* TOAST (top-right) */
.toast-container{position:fixed; top:1rem; right:1rem; z-index:1080;}
</style>
</head>
<body>

<!-- NAV -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container-fluid px-3 px-md-4">
    <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
      <span class="badge rounded-2 badge-brand text-white px-2 py-1 fw-bold">DDA KINONDONI</span>
      <span class="fw-semibold">Profile</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">← Back to Dashboard</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
            <?php if($avatar): ?>
              <img class="nav-avatar me-2" src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar">
            <?php else: ?>
              <span class="nav-avatar me-2 d-inline-flex align-items-center justify-content-center text-white" style="background:#ff7a00"><?php echo $initial; ?></span>
            <?php endif; ?>
            <?php echo htmlspecialchars($me['display_name']); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item active" href="profile.php">My Profile</a></li>
            <li><a class="dropdown-item" href="settings.php">Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO HEADER -->
<div class="container-fluid px-3 px-md-4 mt-3">
  <div class="hero fade-in">
    <div class="d-flex align-items-center gap-3">
      <i class="fa-solid fa-id-badge text-warning" style="font-size:22px"></i>
      <div>
    
        <h4 class="mb-0">Profile Center</h4>
      </div>
    </div>
  </div>
</div>

<div class="container-fluid px-3 px-md-4 my-3">

  <?php if ($notice): ?>
    <div class="alert alert-<?php echo $notice['type']; ?> alert-dismissible fade show mt-2" role="alert">
      <?php echo htmlspecialchars($notice['msg']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <!-- LEFT: Profile card + upload -->
    <div class="col-12 col-lg-4">
      <div class="card-lite p-3 fade-in d2">
        <div class="d-flex align-items-center gap-3">
          <div class="profile-avatar">
            <?php if($avatar): ?>
              <img id="avatarPreview" src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar">
            <?php else: ?>
              <span id="avatarInitial"><?php echo $initial; ?></span>
            <?php endif; ?>
          </div>
          <div>
            <h5 class="mb-1"><?php echo htmlspecialchars($me['display_name']); ?></h5>
            <div class="text-muted small"><?php echo htmlspecialchars($me['email']); ?></div>
            <span class="badge badge-soft mt-1"><i class="fa-solid fa-shield-halved me-1"></i><?php echo htmlspecialchars($me['role']); ?></span>
          </div>
        </div>

        <hr>

        <!-- Upload form with live preview -->
        <form method="post" enctype="multipart/form-data" class="d-grid gap-2">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($CSRF); ?>">
          <input type="hidden" name="action" value="upload_avatar">
          <div>
            <label class="form-label">Change Profile Picture</label>
            <input class="form-control" type="file" id="avatarInput" name="avatar" accept="image/png,image/jpeg,image/webp" required>
            <div class="form-text">JPG/PNG/WEBP, ≤ 3MB. You’ll see a preview before saving.</div>
          </div>
          <button class="btn btn-accent"><i class="fa-solid fa-cloud-arrow-up me-1"></i>Upload</button>
        </form>

        <hr>

        <!-- Quick stats -->
        <div class="row g-2">
          <div class="col-4"><div class="kpi-mini p-2"><div class="num"><?php echo number_format($stats['total']); ?></div><div class="lbl">Records</div></div></div>
          <div class="col-4"><div class="kpi-mini p-2"><div class="num"><?php echo number_format($stats['projects']); ?></div><div class="lbl">Projects</div></div></div>
          <div class="col-4"><div class="kpi-mini p-2"><div class="num"><?php echo number_format($stats['last7']); ?></div><div class="lbl">Last 7d</div></div></div>
        </div>
      </div>
    </div>

    <!-- RIGHT: Edit profile -->
    <div class="col-12 col-lg-8">
      <div class="card-lite p-3 fade-in d3">
        <div class="d-flex align-items-center justify-content-between">
          <h6 class="mb-0"><i class="fa-solid fa-user-pen me-2 text-warning"></i>Edit Profile</h6>
          <a href="dashboard.php" class="btn btn-outline-lite btn-sm">Back to Dashboard</a>
        </div>
        <hr>
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($CSRF); ?>">
          <input type="hidden" name="action" value="save_profile">
          <div class="col-md-6">
            <label class="form-label">Display name</label>
            <input class="form-control" name="display_name" value="<?php echo htmlspecialchars($me['display_name']); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($me['email']); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input class="form-control" name="phone" value="<?php echo htmlspecialchars($me['phone']); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Role</label>
            <select class="form-select" name="role">
              <?php
                $roles = ['Owner','Admin','Manager','Analyst','Viewer'];
                $cur = $me['role'];
                foreach($roles as $r){
                  $sel = ($r===$cur) ? 'selected' : '';
                  echo "<option $sel>".htmlspecialchars($r)."</option>";
                }
              ?>
            </select>
          </div>
          <div class="col-12 d-flex gap-2 mt-2">
            <button class="btn btn-accent"><i class="fa-solid fa-floppy-disk me-1"></i>Save Changes</button>
            <button type="reset" class="btn btn-outline-lite">Reset</button>
          </div>
        </form>
      </div>

      <!-- Extra: Preferences (UI only) -->
      <div class="card-lite p-3 mt-3 fade-in d4">
        <h6 class="mb-2"><i class="fa-solid fa-gear me-2 text-warning"></i>Preferences</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="emailNotif" checked>
              <label class="form-check-label" for="emailNotif">Email notifications</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="tips" checked>
              <label class="form-check-label" for="tips">In-app tips</label>
            </div>
          </div>
        </div>
        <div class="text-muted small mt-2">(* UI only for now.)</div>
      </div>
    </div>
  </div>

</div>

<!-- Bootstrap Toast (top-right) -->
<div class="toast-container">
  <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        <?php echo htmlspecialchars($toast_success ?? ''); ?>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<script>
// Live preview for avatar before upload
const input = document.getElementById('avatarInput');
if (input){
  input.addEventListener('change', (e)=>{
    const file = e.target.files?.[0];
    if(!file) return;
    if(!file.type.match(/^image\//)) return;
    const url = URL.createObjectURL(file);
    let img = document.getElementById('avatarPreview');
    const initial = document.getElementById('avatarInitial');
    if(!img){
      img = document.createElement('img');
      img.id = 'avatarPreview';
      const holder = document.querySelector('.profile-avatar');
      holder.innerHTML = '';
      holder.appendChild(img);
    }
    if(initial) initial.remove();
    img.src = url;
  });
}

// Show success toast after avatar upload (English)
<?php if (!empty($toast_success)): ?>
  document.addEventListener('DOMContentLoaded', () => {
    const t = document.getElementById('successToast');
    if (t && t.querySelector('.toast-body').textContent.trim() !== '') {
      const toast = new bootstrap.Toast(t, { delay: 3000 });
      toast.show();
    }
  });
<?php endif; ?>
</script>
<script src="js/code_protection.js"></script>
</body>
</html>
