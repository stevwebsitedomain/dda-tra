<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

$category_id = (int)($_GET['category_id'] ?? 0);
if ($category_id <= 0) {
    die('Invalid category.');
}

try {
    $conn = get_db_connection();
} catch (Exception $e) {
    die("DB Connection failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

function safe_text_view($value) {
    $text = is_string($value) ? $value : (string)$value;
    $text = str_replace("\0", '', $text);
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if ($converted !== false) $text = $converted;
    }
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
    return trim($text);
}

$category_name = '';
$nameStmt = $conn->prepare("SELECT category_name FROM data_categories WHERE id = ? LIMIT 1");
if ($nameStmt) {
    $nameStmt->bind_param("i", $category_id);
    $nameStmt->execute();
    $nameRes = $nameStmt->get_result();
    if ($r = $nameRes->fetch_assoc()) {
        $category_name = (string)$r['category_name'];
    }
    $nameStmt->close();
}

if ($category_name === '') {
    $conn->close();
    die('Category not found.');
}

$rows = [];
$stmt = $conn->prepare("
    SELECT username, phone, bio, source, created_at,
           host_name, host_phone, location_name, neighborhood,
           latitude, longitude, room_price, listing_url, description
    FROM category_data
    WHERE category_id = ?
    ORDER BY id DESC
");
if ($stmt) {
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['username'] = safe_text_view($row['username'] ?? '');
        $row['phone'] = safe_text_view($row['phone'] ?? '');
        $row['bio'] = safe_text_view($row['bio'] ?? '');
        $row['source'] = safe_text_view($row['source'] ?? 'instagram');
        $row['created_at'] = safe_text_view($row['created_at'] ?? '');
        $row['host_name'] = safe_text_view($row['host_name'] ?? '');
        $row['host_phone'] = safe_text_view($row['host_phone'] ?? '');
        $row['location_name'] = safe_text_view($row['location_name'] ?? '');
        $row['neighborhood'] = safe_text_view($row['neighborhood'] ?? '');
        $row['latitude'] = safe_text_view($row['latitude'] ?? '');
        $row['longitude'] = safe_text_view($row['longitude'] ?? '');
        $row['room_price'] = safe_text_view($row['room_price'] ?? '');
        $row['listing_url'] = safe_text_view($row['listing_url'] ?? '');
        $row['description'] = safe_text_view($row['description'] ?? '');
        if ($row['username'] === '') continue;
        $rows[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Records - <?php echo htmlspecialchars($category_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7fa; font-family: 'Open Sans', sans-serif; }
        .page-wrap { max-width: 1200px; margin: 24px auto; padding: 0 12px; }
        .card-clean { border: none; border-top: 3px solid #c5a059; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .table td, .table th { vertical-align: middle; }
        .cell-user { font-weight: 700; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cell-bio { max-width: 480px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .search-row { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.05); padding: 12px; margin-bottom: 12px; }
        .location-loading { font-size: 11px; color: #64748b; }
    </style>
</head>
<body>
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:8px;">
            <div>
                <h4 class="mb-1"><i class="fas fa-folder-open text-primary"></i> <?php echo htmlspecialchars($category_name); ?></h4>
                <div class="text-muted small">Total records: <?php echo count($rows); ?></div>
            </div>
            <div class="d-flex" style="gap:8px;">
                <a href="download.php?view=categorized" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back</a>
                <a href="download.php?download_excel=1&category_id=<?php echo (int)$category_id; ?>" class="btn btn-success btn-sm"><i class="fas fa-file-excel mr-1"></i> Download Excel</a>
            </div>
        </div>

        <div class="card card-clean">
            <div class="search-row">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" id="category-search-input" class="form-control" placeholder="Search username, host, location, neighborhood, source...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="category-records-table">
                        <thead>
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
                                <th>Bio</th>
                                <th>Source</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($rows): foreach ($rows as $row): ?>
                                <tr class="category-row-searchable">
                                    <td class="cell-user">@<?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['host_name'] !== '' ? $row['host_name'] : 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['host_phone'] !== '' ? $row['host_phone'] : 'N/A'); ?></td>
                                    <td
                                        class="geo-location-cell"
                                        data-lat="<?php echo htmlspecialchars($row['latitude']); ?>"
                                        data-lng="<?php echo htmlspecialchars($row['longitude']); ?>">
                                        <span class="geo-location-main"><?php echo htmlspecialchars($row['location_name'] !== '' ? $row['location_name'] : 'N/A'); ?></span>
                                        <div class="location-loading">Inatafuta location halisi...</div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['neighborhood'] !== '' ? $row['neighborhood'] : 'N/A'); ?></td>
                                    <td>
                                        <?php if($row['latitude'] !== '' && $row['longitude'] !== ''): ?>
                                            <?php $coord = $row['latitude'] . ',' . $row['longitude']; ?>
                                            <a href="https://www.google.com/maps/dir/?api=1&origin=<?php echo urlencode('My Location'); ?>&destination=<?php echo urlencode($coord); ?>&travelmode=driving" target="_blank" rel="noopener"><?php echo htmlspecialchars($coord); ?></a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['room_price'] !== '' ? $row['room_price'] : 'N/A'); ?></td>
                                    <td>
                                        <?php if($row['listing_url'] !== ''): ?>
                                            <a href="<?php echo htmlspecialchars($row['listing_url']); ?>" target="_blank" rel="noopener">Open Link</a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['phone'] !== '' ? $row['phone'] : 'N/A'); ?></td>
                                    <td class="cell-bio"><?php echo htmlspecialchars($row['description'] !== '' ? $row['description'] : '-'); ?></td>
                                    <td class="cell-bio"><?php echo htmlspecialchars($row['bio'] !== '' ? $row['bio'] : '-'); ?></td>
                                    <td><span class="badge badge-light border"><?php echo htmlspecialchars($row['source']); ?></span></td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($row['created_at']); ?></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="13" class="text-center py-4 text-muted">No records in this category.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<script src="js/code_protection.js"></script>
<script>
(function () {
    const input = document.getElementById('category-search-input');
    const table = document.getElementById('category-records-table');
    if (input && table) {
        input.addEventListener('input', function () {
            const q = String(input.value || '').trim().toLowerCase();
            const rows = table.querySelectorAll('tbody .category-row-searchable');
            rows.forEach((row) => {
                const text = (row.textContent || '').toLowerCase();
                row.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        });
    }

    const cache = new Map();
    const cells = Array.from(document.querySelectorAll('.geo-location-cell'));

    function pickPart(address, keys) {
        for (const key of keys) {
            const val = String((address && address[key]) || '').trim();
            if (val) return val;
        }
        return '';
    }

    function buildLocation(address) {
        const region = pickPart(address, ['state', 'region', 'province', 'state_district']);
        const district = pickPart(address, ['county', 'city_district', 'municipality', 'city', 'town']);
        const street = pickPart(address, ['suburb', 'neighbourhood', 'neighborhood', 'road', 'residential', 'hamlet', 'village']);
        const full = [region, district, street].filter(Boolean).join(', ');
        return { region, district, street, full };
    }

    async function reverse(lat, lng) {
        const key = `${lat},${lng}`;
        if (cache.has(key)) return cache.get(key);
        const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&accept-language=sw,en`;
        const res = await fetch(url, { method: 'GET' });
        if (!res.ok) throw new Error('geocode failed');
        const data = await res.json();
        const detail = buildLocation(data.address || {});
        cache.set(key, detail);
        return detail;
    }

    async function enrichCell(cell) {
        const lat = String(cell.getAttribute('data-lat') || '').trim();
        const lng = String(cell.getAttribute('data-lng') || '').trim();
        const mainEl = cell.querySelector('.geo-location-main');
        const subEl = cell.querySelector('.location-loading');
        if (!lat || !lng) {
            if (subEl) subEl.textContent = 'Hakuna Lat/Lng.';
            return;
        }
        try {
            const detail = await reverse(lat, lng);
            if (detail.full && mainEl) mainEl.textContent = detail.full;
            if (subEl) subEl.textContent = `Mkoa: ${detail.region || 'N/A'} | Wilaya: ${detail.district || 'N/A'} | Mtaa: ${detail.street || 'N/A'}`;
        } catch (e) {
            if (subEl) subEl.textContent = 'Imeshindwa kupata location halisi.';
        }
    }

    async function runBatches() {
        const batchSize = 3;
        for (let i = 0; i < cells.length; i += batchSize) {
            const batch = cells.slice(i, i + batchSize);
            await Promise.all(batch.map(enrichCell));
        }
    }
    runBatches();
})();
</script>
</body>
</html>

