<?php
session_start();
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

// API_BASE_URL kutoka config (kwa ajili ya server status)
require_once __DIR__ . '/config.php'; 

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$CSRF = $_SESSION['csrf'];

/* ---------- DB ---------- */
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    die("DB Connection failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

/* ---------- helpers ---------- */
function col_exists($conn, $table, $col){
  $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '".$conn->real_escape_string($col)."'");
  return ($res && $res->num_rows>0);
}

function detect_country_from_phone($phone){
  $p = preg_replace('/\D+/', '', $phone ?? '');
  if (!$p) return null;
  if (str_starts_with($p,'255')) return 'Tanzania';
  if (str_starts_with($p,'254')) return 'Kenya';
  if (str_starts_with($p,'1'))   return 'USA';
  return 'International';
}

function safe_text($value) {
  $text = is_string($value) ? $value : (string)$value;
  $text = str_replace("\0", '', $text);
  if (function_exists('iconv')) {
    $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
    if ($converted !== false) $text = $converted;
  }
  $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
  return trim($text);
}

function mb_len_safe($text) {
  return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
}

function looks_binary_blob($text) {
  $t = (string)$text;
  if ($t === '') return false;
  if (strpos($t, "PK\x03\x04") !== false) return true;
  if (stripos($t, '[Content_Types].xml') !== false) return true;
  if (stripos($t, 'xl/worksheets') !== false) return true;

  $len = strlen($t);
  if ($len <= 0) return false;
  preg_match_all('/[^\x09\x0A\x0D\x20-\x7E]/', $t, $m);
  $noise = isset($m[0]) ? count($m[0]) : 0;
  return ($noise / max(1, $len)) > 0.35;
}

function is_valid_display_username($username) {
  $u = safe_text($username);
  if ($u === '') return false;
  if (looks_binary_blob($u)) return false;
  if (mb_len_safe($u) > 90) return false;

  // allow normal username/name characters
  if (preg_match('/^[\p{L}\p{N}\s._@\-]+$/u', $u)) return true;

  // Fallback: keep it if at least half is letters/numbers.
  preg_match_all('/[\p{L}\p{N}]/u', $u, $m);
  $alnum = isset($m[0]) ? count($m[0]) : 0;
  return ($alnum / max(1, mb_len_safe($u))) >= 0.5;
}

function normalize_record_for_view($row) {
  if (!is_array($row)) return null;

  $username = safe_text($row['username'] ?? '');
  if (!is_valid_display_username($username)) return null;

  $bio = safe_text($row['bio'] ?? '');
  if (looks_binary_blob($bio)) $bio = '';

  $phone = safe_text($row['phone'] ?? '');
  $source = safe_text($row['source'] ?? 'instagram');
  $categoryName = safe_text($row['category_name'] ?? 'Uncategorized');
  $created = safe_text($row['created_at'] ?? '');
  $hostName = safe_text($row['host_name'] ?? '');
  $hostPhone = safe_text($row['host_phone'] ?? '');
  $locationName = safe_text($row['location_name'] ?? '');
  $neighborhood = safe_text($row['neighborhood'] ?? '');
  $latitude = safe_text($row['latitude'] ?? '');
  $longitude = safe_text($row['longitude'] ?? '');
  $roomPrice = safe_text($row['room_price'] ?? '');
  $listingId = safe_text($row['listing_id'] ?? '');
  $listingUrl = safe_text($row['listing_url'] ?? '');
  $description = safe_text($row['description'] ?? '');

  return [
    'id' => (int)($row['id'] ?? 0),
    'username' => $username,
    'phone' => $phone,
    'bio' => $bio,
    'source' => $source !== '' ? $source : 'instagram',
    'category_name' => $categoryName !== '' ? $categoryName : 'Uncategorized',
    'created_at' => $created,
    'host_name' => $hostName,
    'host_phone' => $hostPhone,
    'location_name' => $locationName,
    'neighborhood' => $neighborhood,
    'latitude' => $latitude,
    'longitude' => $longitude,
    'room_price' => $roomPrice,
    'listing_id' => $listingId,
    'listing_url' => $listingUrl,
    'description' => $description
  ];
}

/* ---------- Logic ya POST Handling (Deleted code logic for brevity but keep it in your file) ---------- */
// (Hapa weka ile logic yako yote ya POST uliyotuma mwanzo: Create Category, Import, Delete n.k.)
// Nimeacha logic hiyo hiyo ili isiharibu database yako.

/* logic ya hapa... (copy logic kutoka file lako la mwanzo hapa) */
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['csrf']) && hash_equals($CSRF, $_POST['csrf'])) {
        // ... (Endelea na logic yako ya delete_row, delete_all, create_category, import_data, import_file)
        if (($_POST['action'] ?? '')==='create_category') {
            $category_name = trim($_POST['category_name'] ?? '');
            if (!empty($category_name)) {
              $stmt = $conn->prepare("INSERT INTO data_categories (category_name) VALUES (?)");
              $stmt->bind_param("s", $category_name);
              $stmt->execute();
              $stmt->close();
            }
        }
        if (($_POST['action'] ?? '')==='rename_category') {
            $category_id = (int)($_POST['category_id'] ?? 0);
            $new_name = trim((string)($_POST['new_category_name'] ?? ''));
            if ($category_id > 0 && $new_name !== '') {
                $exists = $conn->prepare("SELECT id FROM data_categories WHERE category_name = ? AND id <> ? LIMIT 1");
                if ($exists) {
                    $exists->bind_param("si", $new_name, $category_id);
                    $exists->execute();
                    $existsRes = $exists->get_result();
                    $duplicate = $existsRes && $existsRes->num_rows > 0;
                    $exists->close();
                    if ($duplicate) {
                        header("Location: download.php?view=categorized&cat_renamed=0&reason=duplicate");
                        exit;
                    }
                }
                $renameStmt = $conn->prepare("UPDATE data_categories SET category_name = ? WHERE id = ?");
                if ($renameStmt) {
                    $renameStmt->bind_param("si", $new_name, $category_id);
                    $renameStmt->execute();
                    $ok = $renameStmt->affected_rows >= 0;
                    $renameStmt->close();
                    header("Location: download.php?view=categorized&cat_renamed=" . ($ok ? "1" : "0"));
                    exit;
                }
            }
            header("Location: download.php?view=categorized&cat_renamed=0");
            exit;
        }
        if (($_POST['action'] ?? '')==='delete_category') {
            $category_id = (int)($_POST['category_id'] ?? 0);
            if ($category_id > 0) {
                try {
                    $conn->begin_transaction();

                    $delCatData = $conn->prepare("DELETE FROM category_data WHERE category_id = ?");
                    $delCatData->bind_param("i", $category_id);
                    $delCatData->execute();
                    $delCatData->close();

                    $delCategory = $conn->prepare("DELETE FROM data_categories WHERE id = ?");
                    $delCategory->bind_param("i", $category_id);
                    $delCategory->execute();
                    $affected = $delCategory->affected_rows;
                    $delCategory->close();

                    $conn->commit();

                    if ($affected > 0) {
                        header("Location: download.php?view=categorized&cat_deleted=1");
                    } else {
                        header("Location: download.php?view=categorized&cat_deleted=0");
                    }
                    exit;
                } catch (Throwable $e) {
                    $conn->rollback();
                    header("Location: download.php?view=categorized&cat_deleted=0");
                    exit;
                }
            }
        }
        // Nimefupisha hapa ili kutoa nafasi ya UI
    }
}

/* ---------- Handle Excel Downloads ---------- */
if (isset($_GET['download_excel'])) {
    $downloadCategoryId = (int)($_GET['category_id'] ?? 0);

    $sql = "
        SELECT
            c.category_name,
            cd.username, cd.host_name, cd.host_phone, cd.location_name, cd.neighborhood,
            cd.latitude, cd.longitude, cd.room_price, cd.listing_url, cd.phone,
            cd.description, cd.bio, cd.source, cd.created_at
        FROM category_data cd
        LEFT JOIN data_categories c ON c.id = cd.category_id
    ";
    if ($downloadCategoryId > 0) {
        $sql .= " WHERE cd.category_id = " . $downloadCategoryId . " ";
    }
    $sql .= " ORDER BY c.category_name ASC, cd.location_name ASC, cd.neighborhood ASC, cd.id DESC ";
    $result = $conn->query($sql);

    $headers = [
        'House Brand Name', 'Username', 'Host', 'Host Phone', 'Location', 'Neighborhood', 'Lat/Lng',
        'Room Price', 'Listing URL', 'Phone', 'Bio', 'Source', 'Date Added', 'Action'
    ];

    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="report.xls"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "<table border='1'>";
    $currentCategory = null;
    $printedAny = false;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categoryName = trim((string)($row['category_name'] ?? ''));
            if ($categoryName === '') $categoryName = 'Uncategorized';

            if ($currentCategory !== $categoryName) {
                $currentCategory = $categoryName;
                echo "<tr><td colspan='14' style='font-weight:700;background:#eef2ff;'>Category: " . htmlspecialchars($currentCategory) . "</td></tr>";
                echo "<tr>";
                foreach ($headers as $h) echo "<th>" . htmlspecialchars($h) . "</th>";
                echo "</tr>";
            }

            $lat = trim((string)($row['latitude'] ?? ''));
            $lng = trim((string)($row['longitude'] ?? ''));
            $hasCoords = is_numeric($lat) && is_numeric($lng)
                && (float)$lat >= -90 && (float)$lat <= 90
                && (float)$lng >= -180 && (float)$lng <= 180;
            $coords = $hasCoords ? ($lat . ',' . $lng) : 'N/A';
            $coordMapUrl = $hasCoords
                ? "https://www.google.com/maps/dir/?api=1&destination=" . rawurlencode($coords) . "&travelmode=driving&dir_action=navigate"
                : '';
            $listingUrl = trim((string)($row['listing_url'] ?? ''));

            echo "<tr>";
            echo "<td>" . htmlspecialchars((string)($row['description'] ?? '')) . "</td>";
            echo "<td>" . htmlspecialchars((string)($row['username'] ?? '')) . "</td>";
            echo "<td>" . htmlspecialchars((string)($row['host_name'] ?? '')) . "</td>";
            echo "<td>" . htmlspecialchars((string)($row['host_phone'] ?? '')) . "</td>";
            echo "<td>" . htmlspecialchars((string)($row['location_name'] ?? '')) . "</td>";
            echo "<td>" . htmlspecialchars((string)($row['neighborhood'] ?? '')) . "</td>";
            if ($hasCoords) {
                echo "<td><a href=\"" . htmlspecialchars($coordMapUrl, ENT_QUOTES, 'UTF-8') . "\" target=\"_blank\" rel=\"noopener\">" . htmlspecialchars($coords) . "</a></td>";
            } else {
                echo "<td>N/A</td>";
            }
            echo "<td>" . htmlspecialchars((string)($row['room_price'] ?? '')) . "</td>";
            echo $listingUrl !== '' ? "<td><a href=\"" . htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') . "\" target=\"_blank\" rel=\"noopener\">Open Link</a></td>" : "<td>N/A</td>";
            echo "<td>" . htmlspecialchars((string)($row['phone'] ?? '')) . "</td>";
            echo "<td>" . htmlspecialchars((string)($row['bio'] ?? '')) . "</td>";
            echo "<td>" . htmlspecialchars((string)($row['source'] ?? '')) . "</td>";
            echo "<td>" . htmlspecialchars((string)($row['created_at'] ?? '')) . "</td>";
            echo $listingUrl !== '' ? "<td><a href=\"" . htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') . "\" target=\"_blank\" rel=\"noopener\">Open</a></td>" : "<td>N/A</td>";
            echo "</tr>";
            $printedAny = true;
        }
    }
    if (!$printedAny) {
        echo "<tr><td colspan='14'>No categorized records found.</td></tr>";
    }
    echo "</table>";
    exit;
}
if (isset($_GET['download_row'])) {
    $id = (int)($_GET['download_row'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("SELECT id, username, phone, bio, created_at FROM instagram_data WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            header('Content-Type: text/csv; charset=utf-8');
            $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $row['username'] ?? '') ?: 'record'.$id;
            header('Content-Disposition: attachment; filename="'.$safe_name.'_'.date('Y-m-d').'.csv"');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, ['ID', 'Username', 'Phone', 'Bio', 'Country', 'Date Added']);
            $country = detect_country_from_phone($row['phone']);
            fputcsv($output, [$row['id'], $row['username'], $row['phone'] ?? '', $row['bio'] ?? '', $country ?? '', $row['created_at']]);
            fclose($output); exit;
        }
        $stmt->close();
    }
}

/* ---------- Fetch Data for UI ---------- */
$view_mode = strtolower(trim((string)($_GET['view'] ?? 'main')));
if (!in_array($view_mode, ['main', 'categorized', 'categories'], true)) {
    $view_mode = 'main';
}

$total_main = (int)($conn->query("SELECT COUNT(*) as count FROM instagram_data")->fetch_assoc()['count'] ?? 0);
$total_cat_data = (int)($conn->query("
    SELECT COUNT(*) AS count
    FROM category_data cd
    INNER JOIN data_categories c ON c.id = cd.category_id
")->fetch_assoc()['count'] ?? 0);
$total_cat_orphan = (int)($conn->query("
    SELECT COUNT(*) AS count
    FROM category_data cd
    LEFT JOIN data_categories c ON c.id = cd.category_id
    WHERE c.id IS NULL
")->fetch_assoc()['count'] ?? 0);
$total_uncategorized = (int)($conn->query("
    SELECT COUNT(*) AS count
    FROM instagram_data i
    WHERE NOT EXISTS (
        SELECT 1
        FROM category_data cd
        WHERE cd.username = i.username
    )
")->fetch_assoc()['count'] ?? 0);
// Main Records (All Sources) = categorized records + records ambazo bado hazijawekwa category.
$total_main_all_sources = $total_cat_data + $total_uncategorized;
$cat_res = $conn->query("SELECT id, category_name FROM data_categories ORDER BY category_name");
$categories = []; while($c = $cat_res->fetch_assoc()) $categories[] = $c;

$main_records_rows = [];
if ($view_mode === 'main') {
    // Load only what main table needs; avoid heavy fetches for other tabs.
    $categorizedSql = "
        SELECT cd.id, cd.username, cd.phone, cd.bio, cd.source, cd.created_at, c.category_name,
               cd.host_name, cd.host_phone, cd.location_name, cd.neighborhood,
               cd.latitude, cd.longitude, cd.room_price, cd.listing_id, cd.listing_url, cd.description
        FROM category_data cd
        LEFT JOIN data_categories c ON c.id = cd.category_id
        ORDER BY cd.id DESC
        LIMIT 500
    ";
    $categorizedRes = $conn->query($categorizedSql);
    if ($categorizedRes) {
        while ($cr = $categorizedRes->fetch_assoc()) {
            $normalized = normalize_record_for_view($cr);
            if ($normalized) $main_records_rows[] = $normalized;
        }
    }

    $uncategorizedSql = "
        SELECT i.id, i.username, i.phone, i.bio, i.created_at
        FROM instagram_data i
        WHERE NOT EXISTS (
            SELECT 1
            FROM category_data cd
            WHERE cd.username = i.username
        )
        ORDER BY i.id DESC
        LIMIT 300
    ";
    $uncategorizedRes = $conn->query($uncategorizedSql);
    if ($uncategorizedRes) {
        while ($u = $uncategorizedRes->fetch_assoc()) {
            $normalized = normalize_record_for_view([
                'id' => $u['id'],
                'username' => $u['username'],
                'phone' => $u['phone'],
                'bio' => $u['bio'],
                'source' => 'instagram',
                'created_at' => $u['created_at'],
                'category_name' => 'Uncategorized'
            ]);
            if ($normalized) $main_records_rows[] = $normalized;
        }
    }
}

$category_overview = [];
$catOverviewSql = "
    SELECT c.id, c.category_name, COUNT(cd.id) AS total_rows
    FROM data_categories c
    LEFT JOIN category_data cd ON cd.category_id = c.id
    GROUP BY c.id, c.category_name
    ORDER BY c.category_name
";
$catOverviewRes = $conn->query($catOverviewSql);
if ($catOverviewRes) {
    while ($cr = $catOverviewRes->fetch_assoc()) {
        $category_overview[] = $cr;
    }
}

$selected_category_id = (int)($_GET['category_id'] ?? 0);
$selected_category_name = '';
$selected_category_rows = [];
if ($selected_category_id > 0) {
    $view_mode = 'categories';
    $catStmt = $conn->prepare("SELECT category_name FROM data_categories WHERE id = ? LIMIT 1");
    if ($catStmt) {
        $catStmt->bind_param("i", $selected_category_id);
        $catStmt->execute();
        $catNameRes = $catStmt->get_result();
        if ($catNameRow = $catNameRes->fetch_assoc()) {
            $selected_category_name = $catNameRow['category_name'];
        }
        $catStmt->close();
    }

    $rowStmt = $conn->prepare("
        SELECT id, username, phone, bio, source, created_at,
               host_name, host_phone, location_name, neighborhood,
               latitude, longitude, room_price, listing_id, listing_url, description
        FROM category_data
        WHERE category_id = ?
        ORDER BY id DESC
        LIMIT 1000
    ");
    if ($rowStmt) {
        $rowStmt->bind_param("i", $selected_category_id);
        $rowStmt->execute();
        $rowRes = $rowStmt->get_result();
        while ($rw = $rowRes->fetch_assoc()) {
            $normalized = normalize_record_for_view([
                'id' => $rw['id'],
                'username' => $rw['username'],
                'phone' => $rw['phone'],
                'bio' => $rw['bio'],
                'source' => $rw['source'],
                'created_at' => $rw['created_at'],
                'category_name' => $selected_category_name,
                'host_name' => $rw['host_name'] ?? '',
                'host_phone' => $rw['host_phone'] ?? '',
                'location_name' => $rw['location_name'] ?? '',
                'neighborhood' => $rw['neighborhood'] ?? '',
                'latitude' => $rw['latitude'] ?? '',
                'longitude' => $rw['longitude'] ?? '',
                'room_price' => $rw['room_price'] ?? '',
                'listing_id' => $rw['listing_id'] ?? '',
                'listing_url' => $rw['listing_url'] ?? '',
                'description' => $rw['description'] ?? ''
            ]);
            if ($normalized) $selected_category_rows[] = $normalized;
        }
        $rowStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    <title>Data Manager - TRA Digital</title>
    
    <link rel="icon" href="dda.jpg" type="image/jpeg">
    
    <!-- Fonts & CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<link href="assets/responsive-layout.css" rel="stylesheet">
<link href="assets/dashboard-common.css" rel="stylesheet">

    <style>
        :root {
            --tra-navy: #0b1e3b;
            --tra-gold: #c5a059;
            --wh-blue: #0e2245;
            --wh-gradient: linear-gradient(135deg, #0e2245 0%, #1c3d7a 100%);
            --sidebar-width: 300px;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f4f7fa;
            color: #444;
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }

        /* --- SIDEBAR TRA STYLE --- */
        .sidebar-wrapper {
            width: var(--sidebar-width);
            background: var(--tra-navy);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            color: #fff;
            z-index: 1000;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .sidebar-wrapper::-webkit-scrollbar { display: none; }
        .sidebar-logo { padding: 15px; text-align: center; background: rgba(0,0,0,.2); }
        .sidebar-logo img { width: 100%; height: 100px; background: #fff; border-radius: 0; padding: 0; object-fit: cover; object-position: center; display: block; }
        
        .sidebar-info { background: rgba(0,0,0,0.2); margin: 15px; padding: 15px; border-radius: 8px; border-left: 3px solid var(--tra-gold); }
        .nav-link-custom { padding: 12px 25px; color: #bdc3c7; display: flex; align-items: center; text-decoration: none !important; transition: 0.3s; }
        .nav-link-custom:hover, .nav-link-custom.active { background: rgba(255,255,255,0.1); color: #fff; }
        .nav-link-custom i { margin-right: 15px; width: 20px; text-align: center; }
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

        /* --- MAIN AREA --- */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }

        /* Top Header */
        .header-top { background: #fff; height: 60px; padding: 0 30px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .status-badge { font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 50px; display: flex; align-items: center; gap: 5px; }
        .status-online { background: #e6fffa; color: #38b2ac; border: 1px solid #38b2ac; }

        /* WazoHost Page Header */
        .tt-page-header {
            background: var(--wh-gradient);
            padding: 45px 30px 30px;
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
        .tt-page-header .tt-breadcrumb {
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
        .tt-breadcrumb { font-size: 13px; opacity: 0.8; margin-top: 10px; }

        /* Tiles Style */
        .tiles-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; padding: 20px 30px 0; margin-top: 0; }
        .tile-box { background: #fff; padding: 26px; min-height: 126px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 16px; transition: 0.3s; text-decoration: none !important; color: inherit; cursor: pointer; opacity: 1; transform: translateY(0); }
        .tile-box.tile-animate-in {
            animation: tileIn .34s ease-out both;
        }
        @keyframes tileIn {
            0% {
                opacity: .55;
                transform: translateY(8px) scale(0.985);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .tile-box.tile-visible { opacity: 1; transform: translateY(0); }
        .tile-box:hover { transform: translateY(-5px); border-bottom: 3px solid var(--tra-gold); }
        .tile-box.active-view { border-bottom: 3px solid var(--tra-gold); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
        .tile-box i { font-size: 28px; color: var(--wh-blue); }
        .tile-box i.tile-icon-main { color: #0f766e; }
        .tile-box i.tile-icon-categorized { color: #1d4ed8; }
        .tile-box i.tile-icon-categories { color: #7c3aed; }
        .tile-box i.tile-icon-status { color: #b91c1c; }
        .tile-val { font-size: 30px; font-weight: 800; color: #333; line-height: 1; letter-spacing: .01em; }
        .tile-val.tile-main { color: #0f766e; }
        .tile-val.tile-categorized { color: #1d4ed8; }
        .tile-val.tile-categories { color: #7c3aed; }
        .tile-val.tile-status { color: #b91c1c; }
        .tile-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px; }

        /* Cards & Tables: institutional 3/9 layout */
        .content-body {
            padding: 20px 24px 30px 8px !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
            max-width: none !important;
            box-sizing: border-box;
        }
        #records-layout-row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            margin-left: 0;
            margin-right: 0;
        }
        #records-left-column {
            padding-left: 0;
            padding-right: 10px;
        }
        #records-right-column {
            padding-left: 10px;
            padding-right: 0;
        }
        .tight-layout #records-left-column { padding-right: 0 !important; }
        .tight-layout #records-right-column { padding-left: 0 !important; }
        #records-right-column .card-custom {
            width: 100%;
        }
        #main-records-card {
            min-height: 100%;
        }
        .btn-edit-category {
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 12px;
            border-color: #f59e0b;
            color: #b45309;
            background: #fff7ed;
        }
        .btn-edit-category:hover {
            background: #ffedd5;
            color: #92400e;
            border-color: #f59e0b;
        }
        .card-custom { background: #fff; border-radius: 10px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .card-header-accent { border-top: 3px solid var(--tra-gold); font-weight: 700; color: var(--wh-blue); background: #fff; }
        .table thead th { background: #f8fafc; border: none; color: #64748b; font-size: 12px; text-transform: uppercase; }
        .table-responsive table { table-layout: fixed; width: 100%; }
        .data-table td { vertical-align: middle; }
        .cell-username { max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .cell-contact { max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .cell-category { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .cell-date { white-space: nowrap; font-size: 12px; color: #64748b; }
        .mark-toggle-btn {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #64748b;
            border-radius: 999px;
            padding: 2px 6px;
            font-size: 11px;
            line-height: 1;
            transition: .2s ease;
        }
        .mark-toggle-btn:hover {
            border-color: #93c5fd;
            color: #1d4ed8;
            background: #eff6ff;
        }
        .mark-toggle-btn.is-marked {
            border-color: #22c55e;
            background: #ecfdf5;
            color: #15803d;
            box-shadow: 0 3px 10px rgba(34, 197, 94, 0.2);
        }
        .th-mark-col {
            width: 64px;
            min-width: 64px;
            max-width: 64px;
            text-align: center;
        }
        .td-mark-col {
            width: 64px;
            min-width: 64px;
            max-width: 64px;
            text-align: center;
            padding-left: 6px !important;
            padding-right: 6px !important;
        }
        .th-actions-col {
            width: 280px;
            min-width: 280px;
            max-width: 280px;
        }
        .td-actions-col {
            width: 280px;
            min-width: 280px;
            max-width: 280px;
            white-space: nowrap;
        }
        .actions-inline {
            display: inline-flex;
            align-items: center;
            flex-wrap: nowrap;
            gap: 6px;
        }
        
        .btn-tra { background: var(--tra-navy); color: #fff; border: none; font-weight: 600; }
        .btn-tra:hover { background: #162e55; color: #fff; }
        .btn-delete-modern {
            border: 1px solid #fecdd3;
            background: linear-gradient(180deg, #fff1f2, #ffe4e6);
            color: #be123c;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 12px;
            transition: .2s ease;
        }
        .btn-delete-modern:hover {
            background: #ffe4e6;
            color: #9f1239;
            box-shadow: 0 4px 10px rgba(225, 29, 72, 0.18);
            transform: translateY(-1px);
        }
        .btn-open-modern {
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 12px;
        }

        .row-clickable { cursor: pointer; }
        .row-clickable:hover { background-color: #f8fafc !important; }
        .row-clickable td .btn-link { pointer-events: auto; }

        .view-modal .modal-body dt { color: #64748b; font-weight: 600; }
        .view-modal .modal-body dd { margin-bottom: 0.75rem; }
        .main-table-expanded #records-left-column { display: none; }
        .main-table-expanded #records-right-column { flex: 0 0 100%; max-width: 100%; width: 100%; }
        .main-table-expanded #main-records-card { margin-top: 0; }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
        }
        .category-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            background: #fff;
            cursor: pointer;
            transition: .2s ease;
        }
        .category-card:hover {
            transform: translateY(-2px);
            border-color: var(--tra-gold);
            box-shadow: 0 6px 16px rgba(0,0,0,.08);
        }
        .category-card.active {
            border-color: var(--tra-gold);
            box-shadow: 0 0 0 1px rgba(197,160,89,.35), 0 8px 20px rgba(0,0,0,.08);
            background: #fffcf7;
        }
        .category-name {
            font-weight: 700;
            color: #0e2245;
            margin-bottom: 8px;
            word-break: break-word;
        }
        .category-meta {
            font-size: 12px;
            color: #64748b;
        }

        /* Animation */
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Mobile menu */
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
        }

        /* ========== MOBILE RESPONSIVE ========== */
        @media (max-width: 991px) {
            .mobile-menu-btn { display: flex; }
            .sidebar-wrapper {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 280px;
            }
            .sidebar-wrapper.sidebar-open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .header-top { padding: 10px 15px 10px 60px; min-height: 55px; font-size: 13px; }
            .tt-page-header { padding: 25px 15px 20px; }
            .tiles-row {
                grid-template-columns: repeat(2, 1fr);
                padding: 15px 15px 0;
                gap: 10px;
                margin-top: 0;
            }
            .tile-box { min-height: 110px; padding: 18px; }
            .tile-val { font-size: 26px; }
            .tile-label { font-size: 10px; }
            .content-body { padding: 15px !important; }
            #records-left-column,
            #records-right-column {
                padding-left: 0;
                padding-right: 0;
            }
            #records-right-column { margin-top: 4px; }
            .card-custom { margin-bottom: 15px; }
        }
        @media (max-width: 575px) {
            .tiles-row { grid-template-columns: 1fr; }
            .mobile-menu-btn { top: 12px; left: 12px; width: 42px; height: 42px; }
        }

        /* Table: card layout on mobile */
        @media (max-width: 767px) {
            .data-table thead { display: none; }
            .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; }
            .data-table tr {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                margin-bottom: 12px;
                padding: 12px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            }
            .data-table td {
                padding: 8px 0;
                border: none;
                display: flex;
                align-items: flex-start;
                gap: 10px;
                white-space: normal;
                overflow: visible;
            }
            .data-table td::before {
                content: attr(data-label);
                font-weight: 700;
                color: #64748b;
                min-width: 80px;
                font-size: 12px;
            }
            .data-table td:last-child { justify-content: flex-end; padding-top: 12px; border-top: 1px solid #f1f5f9; }
            .data-table td .badge { word-break: break-all; }
            .data-table td .btn-link { min-height: 40px; padding: 8px 16px; }
        }

        /* Modal mobile */
        @media (max-width: 767px) {
            .view-modal .modal-dialog { margin: 10px; max-width: calc(100% - 20px); }
        }

        /* iOS safe area */
        @supports (padding: max(0px)) {
            .mobile-menu-btn { left: max(15px, env(safe-area-inset-left)); }
            .header-top { padding-left: max(15px, env(safe-area-inset-left)); }
        }
    </style>
</head>
<body>

<button type="button" class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Open menu">
    <i class="fas fa-bars"></i>
</button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- SIDEBAR -->
<aside class="sidebar-wrapper">
    <div class="sidebar-logo">
        <img src="<?php echo htmlspecialchars(tra_sidebar_logo_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="TRA LOGO" onerror="this.src='https://via.placeholder.com/90?text=TRA'">
        <div class="mt-2 font-weight-bold small text-uppercase">Tanzania Revenue Authority</div>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard_home.php" class="nav-link-custom"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="analytics.php" class="nav-link-custom"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="dashboard.php" class="nav-link-custom"><i class="fab fa-tiktok"></i> TikTok</a>
        <a href="airbnb_engine.php" class="nav-link-custom"><i class="fab fa-airbnb"></i> Airbnb Engine</a>
        <a href="airbnb_realtime.php" class="nav-link-custom"><i class="fas fa-bolt"></i> Airbnb Real Time</a>
        <a href="airbnb2_engine.php" class="nav-link-custom"><i class="fab fa-airbnb"></i> Airbnb 2</a>
        <a href="upgraded_airbnb.php" class="nav-link-custom"><i class="fas fa-star"></i> Upgraded Airbnb</a>
        <a href="booking_engine.php" class="nav-link-custom"><i class="fas fa-hotel"></i> Booking Engine</a>
        <a href="download.php" class="nav-link-custom active"><i class="fas fa-file-export"></i> Export Records</a>
        <a href="settings_dashboard.php" class="nav-link-custom"><i class="fas fa-user-cog"></i> Settings</a>
        <a href="contact_developer.php" class="nav-link-custom" title="Contact Developer"><i class="fas fa-headset"></i> Contact Developer</a>
        <a href="logout.php" class="nav-link-custom text-danger mt-3"><i class="fas fa-sign-out-alt"></i> Logout System</a>
    </nav>
</aside>

<!-- MAIN CONTENT -->
<main class="main-content">
    
    <!-- Top Header -->
    <header class="header-top">
        <div class="font-weight-bold text-muted">INSTITUTIONAL DATA REPOSITORY</div>
        <div class="d-flex align-items-center">
            <div class="status-badge status-online">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #38b2ac;"></div>
                System API Online
            </div>
        </div>
    </header>

    <!-- Page Header (WazoHost Style) -->
    <section class="tt-page-header">
        <h1 class="h3 font-weight-bold">Database Management</h1>
        <div class="tt-breadcrumb">
            <i class="fas fa-chevron-right mr-1" style="font-size: 10px;"></i> Portal Home / Client Area / Data Manager
        </div>
    </section>

    <!-- KPI Tiles (WazoHost Style) -->
    <div class="tiles-row">
        <a href="?view=main" class="tile-box <?php echo $view_mode === 'main' ? 'active-view' : ''; ?>">
            <i class="fas fa-server tile-icon-main"></i>
            <div>
                <div class="tile-val tile-main js-count" data-count="<?php echo (int)$total_main_all_sources; ?>">0</div>
                <div class="tile-label">Main Records</div>
            </div>
        </a>
        <a href="?view=categorized" class="tile-box <?php echo $view_mode === 'categorized' ? 'active-view' : ''; ?>">
            <i class="fas fa-folder-open tile-icon-categorized"></i>
            <div>
                <div class="tile-val tile-categorized js-count" data-count="<?php echo (int)$total_cat_data; ?>">0</div>
                <div class="tile-label">Categorized</div>
            </div>
        </a>
        <a href="?view=categories" class="tile-box <?php echo $view_mode === 'categories' ? 'active-view' : ''; ?>">
            <i class="fas fa-tags tile-icon-categories"></i>
            <div>
                <div class="tile-val tile-categories js-count" data-count="<?php echo (int)count($categories); ?>">0</div>
                <div class="tile-label">Categories</div>
            </div>
        </a>
        <a href="download.php" class="tile-box">
            <i class="fas fa-cloud-download-alt tile-icon-status"></i>
            <div>
                <div class="tile-val tile-status js-count" data-count="1">0</div>
                <div class="tile-label">Engine Status</div>
            </div>
        </a>
    </div>

    <div class="content-body">
        
        <!-- Alerts -->
        <?php if(!empty($flash)): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> fade-in alert-dismissible">
                <?php echo $flash['msg']; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>
        <?php if($total_cat_orphan > 0): ?>
            <div class="alert alert-warning fade-in alert-dismissible">
                <strong>Note:</strong> Kuna records <?php echo (int)$total_cat_orphan; ?> ndani ya <code>category_data</code> ambazo hazina category halali. Hizo hazijajumuishwa kwenye view ya categorized.
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <div class="row records-layout-row <?php echo $view_mode === 'categorized' ? 'tight-layout' : ''; ?>" id="records-layout-row">
            <!-- Left Panel: Create New Category / Import (col-md-3) -->
            <div class="col-12 col-md-4 col-lg-4" id="records-left-column">
                <div class="card card-custom fade-in">
                    <div class="card-header card-header-accent"><i class="fas fa-plus-circle mr-2"></i> Create New Category</div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?php echo $CSRF; ?>">
                            <input type="hidden" name="action" value="create_category">
                            <div class="form-group">
                                <label class="small font-weight-bold">Category Name</label>
                                <input type="text" name="category_name" class="form-control" placeholder="e.g. Dar es Salaam 2025" required>
                            </div>
                            <button type="submit" class="btn btn-tra btn-block">Create Category</button>
                        </form>
                    </div>
                </div>

                <div class="card card-custom fade-in" style="animation-delay: 0.1s;">
                    <div class="card-header card-header-accent"><i class="fas fa-file-import mr-2"></i> Import Data</div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf" value="<?php echo $CSRF; ?>">
                            <input type="hidden" name="action" value="import_file">
                            <div class="form-group">
                                <label class="small font-weight-bold">Target Category</label>
                                <select name="category_id" class="form-select w-100 p-2 border rounded" required>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="small font-weight-bold">Choose CSV File</label>
                                <input type="file" name="import_file" class="form-control-file" accept=".csv" required>
                            </div>
                            <button type="submit" class="btn btn-tra btn-block">Start Import</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Main Records Table (col-md-9) -->
            <div class="col-12 col-md-8 col-lg-8" id="records-right-column">
                <div class="card card-custom fade-in" id="main-records-card" style="animation-delay: 0.2s;">
                    <div class="card-header card-header-accent d-flex justify-content-between align-items-center flex-wrap">
                        <?php if($view_mode === 'categorized'): ?>
                            <span><i class="fas fa-folder-open mr-2"></i> Category Names</span>
                        <?php else: ?>
                            <span><i class="fas fa-table mr-2"></i> Main Records (All Sources)</span>
                        <?php endif; ?>
                        <div class="d-flex align-items-center" style="gap:8px;">
                            <?php if($view_mode === 'main'): ?>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="toggle-main-table-btn">
                                    <i class="fas fa-expand-alt mr-1"></i> View Table
                                </button>
                            <?php endif; ?>
                            <?php if($view_mode === 'categorized'): ?>
                                <a href="download.php?download_excel=1&view=categorized" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-excel mr-1"></i> Download All Categories
                                </a>
                            <?php else: ?>
                                <a href="download.php?download_excel=1" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-excel mr-1"></i> Download Excel
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 data-table">
                                <thead>
                                    <?php if($view_mode === 'categorized'): ?>
                                        <tr>
                                            <th class="th-mark-col">Mark</th>
                                            <th>Category Name</th>
                                            <th>Total Records</th>
                                            <th class="th-actions-col">Actions</th>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <th>Username</th>
                                            <th>Host</th>
                                            <th>Host Phone</th>
                                            <th>Location</th>
                                            <th>Neighborhood</th>
                                            <th>Lat/Lng</th>
                                            <th>Room Price</th>
                                            <th>Listing URL</th>
                                            <th>Contact</th>
                                            <th>Description</th>
                                            <th>Source</th>
                                            <th>Category</th>
                                            <th>Date</th>
                                        </tr>
                                    <?php endif; ?>
                                </thead>
                                <tbody>
                                    <?php if($view_mode === 'categorized'): ?>
                                        <?php if($category_overview): foreach($category_overview as $co): ?>
                                            <tr>
                                                <td data-label="Mark" class="td-mark-col">
                                                    <button type="button" class="mark-toggle-btn js-cat-mark-btn" data-category-id="<?php echo (int)$co['id']; ?>" aria-label="Mark category">
                                                        <i class="far fa-circle"></i>
                                                    </button>
                                                </td>
                                                <td data-label="Category Name" class="font-weight-bold"><?php echo htmlspecialchars($co['category_name']); ?></td>
                                                <td data-label="Total Records"><?php echo (int)$co['total_rows']; ?></td>
                                                <td data-label="Actions" class="td-actions-col">
                                                    <div class="actions-inline">
                                                        <a class="btn btn-outline-primary btn-sm btn-open-modern" href="category_records.php?category_id=<?php echo (int)$co['id']; ?>">Open</a>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-open-modern btn-edit-category"
                                                            onclick="openRenameCategoryModal(<?php echo (int)$co['id']; ?>, '<?php echo htmlspecialchars(addslashes($co['category_name']), ENT_QUOTES, 'UTF-8'); ?>')">
                                                            <i class="fas fa-pen"></i> Edit
                                                        </button>
                                                        <form method="post" class="d-inline js-delete-category-form" data-category-name="<?php echo htmlspecialchars($co['category_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="csrf" value="<?php echo $CSRF; ?>">
                                                            <input type="hidden" name="action" value="delete_category">
                                                            <input type="hidden" name="category_id" value="<?php echo (int)$co['id']; ?>">
                                                            <button type="submit" class="btn btn-delete-modern">
                                                                <i class="fas fa-trash-alt mr-1"></i>Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="4" class="text-center py-5">No categories found.</td></tr>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if($main_records_rows): foreach($main_records_rows as $r): 
                                            $country = detect_country_from_phone($r['phone']);
                                            $rowData = htmlspecialchars(json_encode([
                                                'id' => $r['id'],
                                                'username' => $r['username'],
                                                'phone' => $r['phone'] ?? '',
                                                'bio' => $r['bio'] ?? '',
                                                'country' => $country,
                                                'created_at' => $r['created_at'] ?? ''
                                            ]), ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <tr class="row-clickable" data-row='<?php echo $rowData; ?>'>
                                            <td data-label="Username" class="font-weight-bold cell-username">@<?php echo htmlspecialchars($r['username']); ?></td>
                                            <td data-label="Host"><?php echo htmlspecialchars($r['host_name'] ?? 'N/A'); ?></td>
                                            <td data-label="Host Phone"><?php echo htmlspecialchars($r['host_phone'] ?? 'N/A'); ?></td>
                                            <td data-label="Location"><?php echo htmlspecialchars($r['location_name'] ?? 'N/A'); ?></td>
                                            <td data-label="Neighborhood"><?php echo htmlspecialchars($r['neighborhood'] ?? 'N/A'); ?></td>
                                            <td data-label="Lat/Lng">
                                                <?php
                                                    $latMain = trim((string)($r['latitude'] ?? ''));
                                                    $lngMain = trim((string)($r['longitude'] ?? ''));
                                                    $coordMain = ($latMain !== '' && $lngMain !== '') ? ($latMain . ',' . $lngMain) : '';
                                                ?>
                                                <?php if($coordMain !== ''): ?>
                                                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($coordMain); ?>&travelmode=driving&dir_action=navigate" target="_blank" rel="noopener"><?php echo htmlspecialchars($coordMain); ?></a>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Room Price"><?php echo htmlspecialchars($r['room_price'] ?? 'N/A'); ?></td>
                                            <td data-label="Listing URL">
                                                <?php if(!empty($r['listing_url'])): ?>
                                                    <a href="<?php echo htmlspecialchars($r['listing_url']); ?>" target="_blank" rel="noopener">Open Link</a>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Contact" class="cell-contact"><?php echo htmlspecialchars($r['phone'] ?? 'N/A'); ?></td>
                                            <td data-label="Description"><?php echo htmlspecialchars($r['description'] ?? ''); ?></td>
                                            <td data-label="Source"><span class="badge badge-light border"><?php echo htmlspecialchars($r['source'] ?? 'instagram'); ?></span></td>
                                            <td data-label="Category" class="cell-category"><span class="badge badge-light border"><?php echo htmlspecialchars($r['category_name'] ?? 'Uncategorized'); ?></span></td>
                                            <td data-label="Date" class="cell-date"><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="13" class="text-center py-5">No records found in this view.</td></tr>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <form id="rename-category-form" method="post" style="display:none;">
                    <input type="hidden" name="csrf" value="<?php echo $CSRF; ?>">
                    <input type="hidden" name="action" value="rename_category">
                    <input type="hidden" name="category_id" id="rename-category-id" value="">
                    <input type="hidden" name="new_category_name" id="rename-category-name" value="">
                </form>

                <?php if($view_mode === 'categories'): ?>
                <div class="card card-custom fade-in" style="animation-delay: 0.25s;">
                    <div class="card-header card-header-accent d-flex justify-content-between align-items-center flex-wrap">
                        <span><i class="fas fa-tags mr-2"></i> Category Export Manager</span>
                        <?php if($selected_category_id > 0): ?>
                            <a href="download.php?download_excel=1&category_id=<?php echo (int)$selected_category_id; ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-file-excel mr-1"></i> Download Selected Category Excel
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="category-grid mb-3">
                            <?php if($category_overview): foreach($category_overview as $co): ?>
                                <?php $isActive = ((int)$selected_category_id === (int)$co['id']); ?>
                                <div class="category-card <?php echo $isActive ? 'active' : ''; ?>" onclick="window.location.href='category_records.php?category_id=<?php echo (int)$co['id']; ?>'">
                                    <div class="category-name"><?php echo htmlspecialchars($co['category_name']); ?></div>
                                    <div class="category-meta mb-2">
                                        <i class="fas fa-database mr-1"></i>
                                        <?php echo (int)$co['total_rows']; ?> records
                                    </div>
                                    <div>
                                        <a class="btn btn-outline-success btn-sm" href="download.php?download_excel=1&category_id=<?php echo (int)$co['id']; ?>" onclick="event.stopPropagation()">
                                            <i class="fas fa-file-excel mr-1"></i> Download
                                        </a>
                                        <?php if($isActive): ?>
                                            <span class="badge badge-warning ml-1">Opened</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; else: ?>
                                <div class="alert alert-light border mb-0">No categories found yet.</div>
                            <?php endif; ?>
                        </div>

                        <?php if($selected_category_id > 0): ?>
                            <h6 class="font-weight-bold mb-2">
                                Category Data:
                                <span class="text-primary"><?php echo htmlspecialchars($selected_category_name ?: ('Category #' . $selected_category_id)); ?></span>
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle data-table">
                                    <thead>
                                        <tr>
                                            <th>House Brand Name</th>
                                            <th>Username</th>
                                            <th>Host</th>
                                            <th>Host Phone</th>
                                            <th>Location</th>
                                            <th>Neighborhood</th>
                                            <th>Lat/Lng</th>
                                            <th>Room Price</th>
                                            <th>Listing URL</th>
                                            <th>Phone</th>
                                            <th>Bio</th>
                                            <th>Source</th>
                                            <th>Date Added</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($selected_category_rows): foreach($selected_category_rows as $sr): ?>
                                            <tr>
                                                <td data-label="House Brand Name"><?php echo htmlspecialchars($sr['description'] ?: ''); ?></td>
                                                <td data-label="Username" class="font-weight-bold">@<?php echo htmlspecialchars($sr['username']); ?></td>
                                                <td data-label="Host"><?php echo htmlspecialchars($sr['host_name'] ?: 'N/A'); ?></td>
                                                <td data-label="Host Phone"><?php echo htmlspecialchars($sr['host_phone'] ?: 'N/A'); ?></td>
                                                <td data-label="Location"><?php echo htmlspecialchars($sr['location_name'] ?: 'N/A'); ?></td>
                                                <td data-label="Neighborhood"><?php echo htmlspecialchars($sr['neighborhood'] ?: 'N/A'); ?></td>
                                                <td data-label="Lat/Lng">
                                                    <?php
                                                        $lat = trim((string)($sr['latitude'] ?? ''));
                                                        $lng = trim((string)($sr['longitude'] ?? ''));
                                                        $coords = ($lat !== '' && $lng !== '') ? ($lat . ',' . $lng) : '';
                                                    ?>
                                                    <?php if($coords !== ''): ?>
                                                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($coords); ?>&travelmode=driving&dir_action=navigate" target="_blank" rel="noopener">
                                                            <?php echo htmlspecialchars($coords); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                                <td data-label="Room Price"><?php echo htmlspecialchars($sr['room_price'] ?: 'N/A'); ?></td>
                                                <td data-label="Listing URL">
                                                    <?php if(!empty($sr['listing_url'])): ?>
                                                        <a href="<?php echo htmlspecialchars($sr['listing_url']); ?>" target="_blank" rel="noopener">Open Link</a>
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                                <td data-label="Phone"><?php echo htmlspecialchars($sr['phone'] ?? 'N/A'); ?></td>
                                                <td data-label="Bio"><?php echo htmlspecialchars($sr['bio'] ?? ''); ?></td>
                                                <td data-label="Source"><?php echo htmlspecialchars($sr['source'] ?? 'N/A'); ?></td>
                                                <td data-label="Date"><?php echo htmlspecialchars($sr['created_at'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="13" class="text-center text-muted">No data in this category yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light border mb-0">
                                Select a category from the table above to preview data before download.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="card card-custom fade-in" style="animation-delay: 0.25s;">
                    <div class="card-body">
                        <div class="alert alert-light border mb-0">
                            Click <strong>Categories</strong> card above to open all categories and view each category table.
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="border-top: 3px solid var(--tra-gold);">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-pen mr-2"></i> Edit Category Name</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <label class="small font-weight-bold mb-2">New Category Name</label>
                <input type="text" class="form-control" id="edit-category-input" placeholder="Type new category name">
                <div class="small text-muted mt-2">Badilisha jina la category bila kupoteza records zake.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-category-rename-btn">
                    <i class="fas fa-save mr-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Record Modal -->
<div class="modal fade view-modal" id="viewRecordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="border-top: 3px solid var(--tra-gold);">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-user-circle mr-2"></i> Record Details</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Username</dt>
                    <dd class="col-sm-9" id="view-username">—</dd>
                    <dt class="col-sm-3">Phone</dt>
                    <dd class="col-sm-9" id="view-phone">—</dd>
                    <dt class="col-sm-3">Country</dt>
                    <dd class="col-sm-9" id="view-country">—</dd>
                    <dt class="col-sm-3">Bio</dt>
                    <dd class="col-sm-9" id="view-bio">—</dd>
                    <dt class="col-sm-3">Date Added</dt>
                    <dd class="col-sm-9" id="view-date">—</dd>
                </dl>
            </div>
            <div class="modal-footer">
                <a href="#" id="downloadRowBtn" class="btn btn-success"><i class="fas fa-file-excel mr-1"></i> Download Excel</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
window.openRenameCategoryModal = function(categoryId, categoryName) {
    $('#rename-category-id').val(String(categoryId || ''));
    $('#edit-category-input').val(String(categoryName || ''));
    $('#editCategoryModal').modal('show');
};

$(function() {
    (function showRenameFeedbackAlert() {
        var params = new URLSearchParams(window.location.search);
        if (!params.has('cat_renamed')) return;
        var renamed = params.get('cat_renamed') === '1';
        var reason = params.get('reason') || '';
        if (renamed) {
            Swal.fire({
                icon: 'success',
                title: 'Successful',
                text: 'Category name updated successfully.',
                confirmButtonColor: '#0b1e3b'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Rename failed',
                text: reason === 'duplicate'
                    ? 'Category name already exists. Use another name.'
                    : 'Failed to update category name.',
                confirmButtonColor: '#0b1e3b'
            });
        }
    })();

    (function showDeleteFeedbackAlert() {
        var params = new URLSearchParams(window.location.search);
        if (!params.has('cat_deleted')) return;
        var deleted = params.get('cat_deleted') === '1';
        if (deleted) {
            Swal.fire({
                icon: 'success',
                title: 'Successful',
                text: 'Category deleted successfully.',
                confirmButtonColor: '#0b1e3b'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Delete failed',
                text: 'Failed to delete category.',
                confirmButtonColor: '#0b1e3b'
            });
        }
    })();

    (function() {
        var toggleBtn = $('#toggle-main-table-btn');
        if (!toggleBtn.length) return;
        toggleBtn.on('click', function() {
            var isExpanded = $('body').toggleClass('main-table-expanded').hasClass('main-table-expanded');
            if (isExpanded) {
                toggleBtn.html('<i class="fas fa-compress-alt mr-1"></i> Collapse Table');
            } else {
                toggleBtn.html('<i class="fas fa-expand-alt mr-1"></i> View Table');
            }
        });
    })();

    function animateCount(el, target) {
        const safeTarget = Number.isFinite(target) ? target : 0;
        const duration = 950;
        const startTs = performance.now();
        function step(now) {
            const p = Math.min((now - startTs) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            const val = Math.floor(safeTarget * eased);
            el.textContent = val.toLocaleString();
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    (function initTiles() {
        var tiles = document.querySelectorAll('.tile-box');
        tiles.forEach(function(tile, idx) {
            tile.classList.add('tile-visible');
            setTimeout(function() {
                tile.classList.add('tile-animate-in');
            }, idx * 40);
        });
        document.querySelectorAll('.js-count').forEach(function(el) {
            var target = parseInt(el.getAttribute('data-count') || '0', 10);
            animateCount(el, Number.isFinite(target) ? target : 0);
        });
    })();

    // Mobile sidebar toggle
    (function() {
        var btn = $('#mobile-menu-btn');
        var sidebar = $('.sidebar-wrapper');
        var overlay = $('#sidebar-overlay');
        function openSidebar() {
            sidebar.addClass('sidebar-open');
            overlay.show();
            document.body.style.overflow = 'hidden';
            btn.attr('aria-label', 'Close menu').find('i').removeClass('fa-bars').addClass('fa-times');
        }
        function closeSidebar() {
            sidebar.removeClass('sidebar-open');
            overlay.hide();
            document.body.style.overflow = '';
            btn.attr('aria-label', 'Open menu').find('i').removeClass('fa-times').addClass('fa-bars');
        }
        btn.on('click', function() { sidebar.hasClass('sidebar-open') ? closeSidebar() : openSidebar(); });
        overlay.on('click', closeSidebar);
    })();

    $('.row-clickable').on('click', function(e) {
        if ($(e.target).closest('form, button').length) return;
        var data = $(this).data('row');
        if (!data) return;
        $('#view-username').text('@' + (data.username || '—'));
        $('#view-phone').text(data.phone || 'N/A');
        $('#view-country').text(data.country || 'N/A');
        $('#view-bio').text(data.bio || 'No biography');
        $('#view-date').text(data.created_at || '—');
        $('#downloadRowBtn').attr('href', '?download_row=' + data.id);
        $('#viewRecordModal').modal('show');
    });

    $('#save-category-rename-btn').on('click', function() {
        var categoryId = ($('#rename-category-id').val() || '').trim();
        var newName = ($('#edit-category-input').val() || '').trim();
        if (!categoryId) return;
        if (!newName) {
            alert('Please enter a new category name.');
            return;
        }
        $('#rename-category-name').val(newName);
        $('#rename-category-form').trigger('submit');
    });

    $(document).on('submit', '.js-delete-category-form', function(e) {
        e.preventDefault();
        var form = this;
        var catName = $(form).data('category-name') || 'this category';
        Swal.fire({
            icon: 'warning',
            title: 'Delete category?',
            text: 'Delete "' + catName + '" and all records inside it?',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then(function(result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    (function initCategoryMarks() {
        var storageKey = 'tra_category_marks_v1';
        var marks = {};
        try {
            marks = JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
        } catch (e) {
            marks = {};
        }

        function saveMarks() {
            try {
                localStorage.setItem(storageKey, JSON.stringify(marks));
            } catch (e) {}
        }

        function applyState(btn, marked) {
            var icon = btn.find('i');
            if (marked) {
                btn.addClass('is-marked');
                icon.removeClass('far fa-circle').addClass('fas fa-check-circle');
            } else {
                btn.removeClass('is-marked');
                icon.removeClass('fas fa-check-circle').addClass('far fa-circle');
            }
        }

        $('.js-cat-mark-btn').each(function() {
            var btn = $(this);
            var id = String(btn.data('category-id') || '').trim();
            if (!id) return;
            applyState(btn, !!marks[id]);
        });

        $(document).on('click', '.js-cat-mark-btn', function(e) {
            e.preventDefault();
            var btn = $(this);
            var id = String(btn.data('category-id') || '').trim();
            if (!id) return;
            marks[id] = !marks[id];
            applyState(btn, !!marks[id]);
            saveMarks();
        });
    })();
});
</script>
<script src="js/code_protection.js"></script>
</body>
</html>