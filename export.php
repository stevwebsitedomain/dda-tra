<?php
require_once __DIR__ . '/config.php';
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    die("Connection failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

$format = $_GET['format'] ?? 'excel';
$search = trim($_GET['search'] ?? '');
$id = (int)($_GET['id'] ?? 0);
$categoryId = (int)($_GET['category_id'] ?? 0);

$data = [];
$filenameBase = 'instagram_data';

function is_valid_lat_lng($lat, $lng) {
    return is_numeric($lat) && is_numeric($lng)
        && (float)$lat >= -90 && (float)$lat <= 90
        && (float)$lng >= -180 && (float)$lng <= 180;
}

if ($categoryId > 0) {
    $catName = 'category_' . $categoryId;
    $catStmt = $conn->prepare("SELECT category_name FROM data_categories WHERE id = ? LIMIT 1");
    if ($catStmt) {
        $catStmt->bind_param("i", $categoryId);
        $catStmt->execute();
        $catRes = $catStmt->get_result();
        if ($catRow = $catRes->fetch_assoc()) {
            $catName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $catRow['category_name']);
        }
        $catStmt->close();
    }

    $sql = "
        SELECT id, category_id, username, phone, bio, source, created_at,
               listing_id, listing_url, host_name, host_phone, location_name,
               neighborhood, latitude, longitude, room_price, description
        FROM category_data
        WHERE category_id = ?
    ";
    $types = "i";
    $params = [$categoryId];

    if ($search !== '') {
        $sql .= " AND (username LIKE ? OR phone LIKE ? OR bio LIKE ?)";
        $like = '%' . $search . '%';
        $types .= "sss";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= " ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $data[] = $row;
        $stmt->close();
    }
    $filenameBase = 'category_' . $catName;
} else {
    $sql = "SELECT id, username, phone, bio, created_at FROM instagram_data WHERE 1=1";
    $types = "";
    $params = [];

    if ($id > 0) {
        $sql .= " AND id = ?";
        $types .= "i";
        $params[] = $id;
    }

    if ($search !== '') {
        $sql .= " AND (username LIKE ? OR phone LIKE ? OR bio LIKE ?)";
        $like = '%' . $search . '%';
        $types .= "sss";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= " ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $data[] = $row;
        $stmt->close();
    }
}

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filenameBase . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    if (!empty($data)) fputcsv($output, array_keys($data[0]));
    foreach ($data as $row) fputcsv($output, $row);
    fclose($output);
} elseif ($format === 'excel') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filenameBase . '.xls"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "<table border='1'><tr>";
    if (!empty($data)) {
        $isCategoryExport = ($categoryId > 0);
        if ($isCategoryExport) {
            $columns = [
                'description', 'username', 'host_name', 'host_phone', 'location_name', 'neighborhood',
                'lat_lng', 'room_price', 'listing_url', 'phone', 'bio', 'source', 'created_at', 'action_open'
            ];
            $labels = [
                'description' => 'House Brand Name',
                'username' => 'Username',
                'host_name' => 'Host',
                'host_phone' => 'Host Phone',
                'location_name' => 'Location',
                'neighborhood' => 'Neighborhood',
                'lat_lng' => 'Lat/Lng',
                'room_price' => 'Room Price',
                'listing_url' => 'Listing URL',
                'phone' => 'Phone',
                'bio' => 'Bio',
                'source' => 'Source',
                'created_at' => 'Date Added',
                'action_open' => 'Action'
            ];
        } else {
            $columns = array_keys($data[0]);
            $labels = array_combine($columns, $columns);
        }

        foreach ($columns as $col) echo "<th>" . htmlspecialchars((string)($labels[$col] ?? $col)) . "</th>";
        echo "</tr>";
        foreach ($data as $row) {
            $lat = trim((string)($row['latitude'] ?? ''));
            $lng = trim((string)($row['longitude'] ?? ''));
            $hasCoords = is_valid_lat_lng($lat, $lng);
            $coords = $hasCoords ? ($lat . ',' . $lng) : 'N/A';
            $coordMapUrl = $hasCoords
                ? "https://www.google.com/maps/dir/?api=1&destination=" . rawurlencode($coords) . "&travelmode=driving&dir_action=navigate"
                : '';
            $listingUrl = trim((string)($row['listing_url'] ?? ''));
            echo "<tr>";
            foreach ($columns as $col) {
                if ($col === 'lat_lng') {
                    echo $hasCoords
                        ? "<td><a href=\"" . htmlspecialchars($coordMapUrl, ENT_QUOTES, 'UTF-8') . "\" target=\"_blank\" rel=\"noopener\">" . htmlspecialchars($coords) . "</a></td>"
                        : "<td>N/A</td>";
                } elseif ($col === 'listing_url' && $listingUrl !== '') {
                    echo "<td><a href=\"" . htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') . "\" target=\"_blank\" rel=\"noopener\">Open Link</a></td>";
                } elseif ($col === 'action_open') {
                    echo $listingUrl !== ''
                        ? "<td><a href=\"" . htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') . "\" target=\"_blank\" rel=\"noopener\">Open</a></td>"
                        : "<td>N/A</td>";
                } else {
                    echo "<td>" . htmlspecialchars((string)($row[$col] ?? '')) . "</td>";
                }
            }
            echo "</tr>";
        }
    }
    echo "</table>";
} elseif ($format === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filenameBase . '.json"');
    echo json_encode($data);
}

$conn->close();
?>
