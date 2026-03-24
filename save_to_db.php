<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/config.php';

function json_response($payload, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit();
}

function sanitize_text($value) {
    return trim((string)$value);
}

function has_column($conn, $table, $column) {
    $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    $columnSafe = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$column);
    if ($tableSafe === '' || $columnSafe === '') {
        return false;
    }
    $res = $conn->query("SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$columnSafe}'");
    return ($res && $res->num_rows > 0);
}

function ensure_column_exists($conn, $table, $column, $ddlSql) {
    if (!has_column($conn, $table, $column)) {
        $conn->query($ddlSql);
    }
}

function ensure_tables($conn) {
    // Categories table
    $conn->query("
        CREATE TABLE IF NOT EXISTS data_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(191) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Categorized data table
    $conn->query("
        CREATE TABLE IF NOT EXISTS category_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            username VARCHAR(255) NOT NULL,
            phone TEXT NULL,
            bio TEXT NULL,
            source VARCHAR(50) NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uniq_category_username (category_id, username),
            INDEX idx_category_id (category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Backward compatibility for old schema versions.
    ensure_column_exists(
        $conn,
        'category_data',
        'source',
        "ALTER TABLE category_data ADD COLUMN source VARCHAR(50) NULL AFTER bio"
    );
    ensure_column_exists(
        $conn,
        'category_data',
        'listing_id',
        "ALTER TABLE category_data ADD COLUMN listing_id VARCHAR(100) NULL AFTER source"
    );
    ensure_column_exists(
        $conn,
        'category_data',
        'listing_url',
        "ALTER TABLE category_data ADD COLUMN listing_url TEXT NULL AFTER listing_id"
    );
    ensure_column_exists(
        $conn,
        'category_data',
        'host_name',
        "ALTER TABLE category_data ADD COLUMN host_name VARCHAR(255) NULL AFTER listing_url"
    );
    ensure_column_exists(
        $conn,
        'category_data',
        'host_phone',
        "ALTER TABLE category_data ADD COLUMN host_phone VARCHAR(120) NULL AFTER host_name"
    );
    ensure_column_exists(
        $conn,
        'category_data',
        'location_name',
        "ALTER TABLE category_data ADD COLUMN location_name VARCHAR(255) NULL AFTER host_phone"
    );
    ensure_column_exists(
        $conn,
        'category_data',
        'neighborhood',
        "ALTER TABLE category_data ADD COLUMN neighborhood VARCHAR(255) NULL AFTER location_name"
    );
    ensure_column_exists(
        $conn,
        'category_data',
        'latitude',
        "ALTER TABLE category_data ADD COLUMN latitude VARCHAR(50) NULL AFTER neighborhood"
    );
    ensure_column_exists(
        $conn,
        'category_data',
        'longitude',
        "ALTER TABLE category_data ADD COLUMN longitude VARCHAR(50) NULL AFTER latitude"
    );
    ensure_column_exists(
        $conn,
        'category_data',
        'room_price',
        "ALTER TABLE category_data ADD COLUMN room_price VARCHAR(120) NULL AFTER longitude"
    );
    ensure_column_exists(
        $conn,
        'category_data',
        'description',
        "ALTER TABLE category_data ADD COLUMN description TEXT NULL AFTER room_price"
    );
}

function get_or_create_category_id($conn, $categoryName) {
    $name = sanitize_text($categoryName);
    if ($name === '') {
        return 0;
    }

    $find = $conn->prepare("SELECT id FROM data_categories WHERE category_name = ? LIMIT 1");
    if ($find) {
        $find->bind_param("s", $name);
        $find->execute();
        $res = $find->get_result();
        if ($row = $res->fetch_assoc()) {
            $find->close();
            return (int)$row['id'];
        }
        $find->close();
    }

    $insert = $conn->prepare("INSERT INTO data_categories (category_name) VALUES (?)");
    if (!$insert) {
        return 0;
    }

    $insert->bind_param("s", $name);
    $ok = $insert->execute();
    $insert->close();
    if (!$ok) {
        // If duplicate race condition happens, fetch again.
        $retry = $conn->prepare("SELECT id FROM data_categories WHERE category_name = ? LIMIT 1");
        if ($retry) {
            $retry->bind_param("s", $name);
            $retry->execute();
            $res = $retry->get_result();
            $id = ($row = $res->fetch_assoc()) ? (int)$row['id'] : 0;
            $retry->close();
            return $id;
        }
        return 0;
    }

    return (int)$conn->insert_id;
}

// Connect DB
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    json_response(['success' => false, 'error' => 'Connection failed: ' . $e->getMessage()], 500);
}

ensure_tables($conn);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    $payload = [];
}

$action = sanitize_text($_GET['action'] ?? $payload['action'] ?? '');

// List categories for dashboard modal
if ($method === 'GET' && $action === 'list_categories') {
    $categories = [];
    $res = $conn->query("SELECT id, category_name FROM data_categories ORDER BY category_name ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    json_response(['success' => true, 'categories' => $categories]);
}

// Airbnb summary stats for Airbnb Engine dashboard cards
if ($method === 'GET' && $action === 'airbnb_stats') {
    $stats = [
        'airbnb_categories' => 0,
        'airbnb_records' => 0
    ];

    $countCategoriesSql = "
        SELECT COUNT(DISTINCT category_id) AS total_categories
        FROM category_data
        WHERE source = 'airbnb'
    ";
    $countRecordsSql = "
        SELECT COUNT(*) AS total_records
        FROM category_data
        WHERE source = 'airbnb'
    ";

    $catRes = $conn->query($countCategoriesSql);
    if ($catRes && ($row = $catRes->fetch_assoc())) {
        $stats['airbnb_categories'] = (int)($row['total_categories'] ?? 0);
    }

    $recordRes = $conn->query($countRecordsSql);
    if ($recordRes && ($row = $recordRes->fetch_assoc())) {
        $stats['airbnb_records'] = (int)($row['total_records'] ?? 0);
    }

    json_response([
        'success' => true,
        'stats' => $stats
    ]);
}

if ($method === 'GET' && $action === 'list_airbnb_categories') {
    $rows = [];
    $sql = "
        SELECT c.id, c.category_name, COUNT(cd.id) AS total_rows
        FROM data_categories c
        INNER JOIN category_data cd ON cd.category_id = c.id
        WHERE cd.source = 'airbnb'
        GROUP BY c.id, c.category_name
        ORDER BY c.category_name ASC
    ";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    json_response(['success' => true, 'categories' => $rows]);
}

if ($method === 'GET' && $action === 'list_airbnb_records') {
    $limit = (int)($_GET['limit'] ?? 50);
    if ($limit < 1 || $limit > 200) {
        $limit = 50;
    }
    $rows = [];
    $sql = "
        SELECT cd.id, cd.username, cd.phone, cd.bio, cd.created_at, c.category_name,
               cd.host_name, cd.host_phone, cd.location_name, cd.neighborhood,
               cd.latitude, cd.longitude, cd.room_price, cd.listing_id, cd.listing_url
        FROM category_data cd
        LEFT JOIN data_categories c ON c.id = cd.category_id
        WHERE cd.source = 'airbnb'
        ORDER BY cd.id DESC
        LIMIT {$limit}
    ";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    json_response(['success' => true, 'records' => $rows]);
}

// Create a category directly from save modal
if ($method === 'POST' && $action === 'create_category') {
    $categoryName = sanitize_text($payload['category_name'] ?? '');
    if ($categoryName === '') {
        json_response(['success' => false, 'error' => 'Category name is required.'], 422);
    }

    $existingId = 0;
    $find = $conn->prepare("SELECT id FROM data_categories WHERE category_name = ? LIMIT 1");
    if ($find) {
        $find->bind_param("s", $categoryName);
        $find->execute();
        $res = $find->get_result();
        if ($row = $res->fetch_assoc()) {
            $existingId = (int)$row['id'];
        }
        $find->close();
    }

    if ($existingId > 0) {
        json_response([
            'success' => true,
            'created' => false,
            'category_id' => $existingId,
            'category_name' => $categoryName
        ]);
    }

    $categoryId = get_or_create_category_id($conn, $categoryName);
    if ($categoryId <= 0) {
        json_response(['success' => false, 'error' => 'Failed to create category.'], 500);
    }

    json_response([
        'success' => true,
        'created' => true,
        'category_id' => $categoryId,
        'category_name' => $categoryName
    ]);
}

// Save batch data to category and main table
if ($method === 'POST' && $action === 'save_batch_category') {
    $categoryName = sanitize_text($payload['category_name'] ?? '');
    $records = $payload['records'] ?? [];
    if ($categoryName === '') {
        json_response(['success' => false, 'error' => 'Category name is required.'], 422);
    }
    if (!is_array($records) || count($records) === 0) {
        json_response(['success' => false, 'error' => 'No records to save.'], 422);
    }

    $categoryId = get_or_create_category_id($conn, $categoryName);
    if ($categoryId <= 0) {
        json_response(['success' => false, 'error' => 'Failed to create/read category.'], 500);
    }

    $createdAt = date('Y-m-d H:i:s');
    $savedToCategory = 0;
    $savedToMain = 0;
    $duplicates = 0;
    $skipped = 0;

    $checkMain = $conn->prepare("SELECT id FROM instagram_data WHERE username = ? LIMIT 1");
    $insertMain = $conn->prepare("INSERT INTO instagram_data (username, phone, bio, created_at) VALUES (?, ?, ?, ?)");
    $updateMain = $conn->prepare("UPDATE instagram_data SET phone = ?, bio = ? WHERE username = ?");
    $checkCat = $conn->prepare("SELECT id FROM category_data WHERE category_id = ? AND username = ? LIMIT 1");
    $insertCat = $conn->prepare("
        INSERT INTO category_data (
            category_id, username, phone, bio, source, created_at,
            listing_id, listing_url, host_name, host_phone, location_name,
            neighborhood, latitude, longitude, room_price, description
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$checkMain || !$insertMain || !$updateMain || !$checkCat || !$insertCat) {
        json_response(['success' => false, 'error' => 'Database prepare failed: ' . $conn->error], 500);
    }

    foreach ($records as $r) {
        if (!is_array($r)) {
            $skipped++;
            continue;
        }

        $username = sanitize_text($r['username'] ?? '');
        if ($username === '') {
            $skipped++;
            continue;
        }

        $phone = sanitize_text($r['phone'] ?? '');
        $bio = sanitize_text($r['bio'] ?? '');
        $source = sanitize_text($r['source'] ?? 'tiktok');
        $listingId = sanitize_text($r['listing_id'] ?? '');
        $listingUrl = sanitize_text($r['listing_url'] ?? $r['url'] ?? '');
        $hostName = sanitize_text($r['host_name'] ?? '');
        $hostPhone = sanitize_text($r['host_phone'] ?? '');
        $locationName = sanitize_text($r['location_name'] ?? $r['city'] ?? '');
        $neighborhood = sanitize_text($r['neighborhood'] ?? '');
        $latitude = sanitize_text($r['latitude'] ?? $r['lat'] ?? '');
        $longitude = sanitize_text($r['longitude'] ?? $r['lng'] ?? '');
        $roomPrice = sanitize_text($r['room_price'] ?? $r['price'] ?? '');
        $description = sanitize_text($r['description'] ?? '');

        // Main table: insert or update
        $checkMain->bind_param("s", $username);
        $checkMain->execute();
        $mainRes = $checkMain->get_result();
        if ($mainRes && $mainRes->num_rows > 0) {
            $updateMain->bind_param("sss", $phone, $bio, $username);
            if ($updateMain->execute()) {
                $savedToMain++;
            }
        } else {
            $insertMain->bind_param("ssss", $username, $phone, $bio, $createdAt);
            if ($insertMain->execute()) {
                $savedToMain++;
            }
        }

        // Category table: avoid duplicates per category+username
        $checkCat->bind_param("is", $categoryId, $username);
        $checkCat->execute();
        $catRes = $checkCat->get_result();
        if ($catRes && $catRes->num_rows > 0) {
            $duplicates++;
            continue;
        }

        $insertCat->bind_param(
            "isssssssssssssss",
            $categoryId,
            $username,
            $phone,
            $bio,
            $source,
            $createdAt,
            $listingId,
            $listingUrl,
            $hostName,
            $hostPhone,
            $locationName,
            $neighborhood,
            $latitude,
            $longitude,
            $roomPrice,
            $description
        );
        if ($insertCat->execute()) {
            $savedToCategory++;
        } else {
            $skipped++;
        }
    }

    $checkMain->close();
    $insertMain->close();
    $updateMain->close();
    $checkCat->close();
    $insertCat->close();

    json_response([
        'success' => true,
        'category_id' => $categoryId,
        'category_name' => $categoryName,
        'saved_main' => $savedToMain,
        'saved_category' => $savedToCategory,
        'duplicates' => $duplicates,
        'skipped' => $skipped
    ]);
}

// Backward-compatible single record save
$data = $payload;
if (!$data || !isset($data['username'])) {
    json_response(['success' => false, 'error' => 'Invalid data: username required'], 422);
}

$username = sanitize_text($data['username']);
$phone = sanitize_text($data['phone'] ?? '');
$bio = sanitize_text($data['bio'] ?? '');
$createdAt = date('Y-m-d H:i:s');

if ($username === '') {
    json_response(['success' => false, 'error' => 'Invalid data: username required'], 422);
}

$checkStmt = $conn->prepare("SELECT id FROM instagram_data WHERE username = ?");
$checkStmt->bind_param("s", $username);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->close();
    $updateStmt = $conn->prepare("UPDATE instagram_data SET phone = ?, bio = ? WHERE username = ?");
    if (!$updateStmt) {
        json_response(['success' => false, 'error' => 'Prepare failed: ' . $conn->error], 500);
    }
    $updateStmt->bind_param("sss", $phone, $bio, $username);
    $ok = $updateStmt->execute();
    $updateStmt->close();
    json_response(['success' => $ok, 'updated' => $ok ? 1 : 0, 'error' => $ok ? null : $conn->error], $ok ? 200 : 500);
}
$checkStmt->close();

$stmt = $conn->prepare("INSERT INTO instagram_data (username, phone, bio, created_at) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    json_response(['success' => false, 'error' => 'Prepare failed: ' . $conn->error], 500);
}
$stmt->bind_param("ssss", $username, $phone, $bio, $createdAt);
$ok = $stmt->execute();
$stmt->close();

json_response(['success' => $ok, 'error' => $ok ? null : $conn->error], $ok ? 200 : 500);
?>
