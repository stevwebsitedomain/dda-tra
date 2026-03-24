<?php
// config.php - InfinityFree friendly .env loader

function load_env_file($path) {
    if (!file_exists($path)) return [];

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $out = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;

        $pos = strpos($line, '=');
        if ($pos === false) continue;

        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));

        // remove quotes if present
        if ((strlen($val) >= 2 && $val[0] === '"' && substr($val, -1) === '"') ||
            (strlen($val) >= 2 && $val[0] === "'" && substr($val, -1) === "'")) {
            $val = substr($val, 1, -1);
        }

        $out[$key] = $val;
    }

    return $out;
}

$env = load_env_file(__DIR__ . '/.env');
$API_BASE_URL = $env['API_BASE_URL'] ?? '';

// Database config (defaults to local XAMPP setup used by index.php)
$DB_HOST = $env['DB_HOST'] ?? 'localhost';
$DB_USER = $env['DB_USER'] ?? 'root';
$DB_PASS = $env['DB_PASS'] ?? '';
$DB_NAME = $env['DB_NAME'] ?? 'tra-infinity-free-data';

/**
 * Returns a mysqli connection with safe fallback hosts.
 * Falls back to 127.0.0.1 if localhost DNS/socket has issues.
 */
function get_db_connection() {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;

    $candidates = [];
    $primaryHost = trim((string)$DB_HOST);
    if ($primaryHost !== '') {
        $candidates[] = $primaryHost;
    }
    if (!in_array('localhost', $candidates, true)) {
        $candidates[] = 'localhost';
    }
    if (!in_array('127.0.0.1', $candidates, true)) {
        $candidates[] = '127.0.0.1';
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $errors = [];

    foreach ($candidates as $host) {
        $conn = @new mysqli($host, $DB_USER, $DB_PASS, $DB_NAME);
        if (!$conn->connect_error) {
            $conn->set_charset('utf8mb4');
            return $conn;
        }
        $errors[] = $host . ': ' . $conn->connect_error;
    }

    throw new Exception('DB Connection failed. Tried: ' . implode(' | ', $errors));
}

/**
 * Logo ya sidebar: faili inayoitwa image kwenye folder uploads (image.png, .jpg, .jpeg, .webp).
 */
function tra_sidebar_logo_url() {
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }
    $dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    foreach (['image.png', 'image.jpg', 'image.jpeg', 'image.webp'] as $name) {
        if (is_file($dir . $name)) {
            $resolved = 'uploads/' . $name;
            return $resolved;
        }
    }
    $resolved = 'uploads/image.png';
    return $resolved;
}
