<?php
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'status')) {
    echo json_encode([
        'success' => true,
        'service' => 'booking_search',
        'status' => function_exists('curl_init') ? 'online' : 'degraded'
    ]);
    exit;
}

// --- GET: Location to Lat Long (Tanzania only when multiple) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'locationToLatLong')) {
    $rapidHost = 'booking-com15.p.rapidapi.com';
    $rapidKey = getenv('RAPIDAPI_KEY_BOOKING') ?: 'fd397681d2mshb56198e459d62f6p1068b7jsn2b13d9e93e59';
    $query = trim((string)($_GET['query'] ?? ''));
    if ($query === '') {
        respond_json(['success' => false, 'message' => 'Query is required.'], 400);
    }
    $queryTz = $query;
    if (stripos($query, 'tanzania') === false) {
        $queryTz = $query . ', Tanzania';
    }
    $url = 'https://' . $rapidHost . '/api/v1/meta/locationToLatLong?query=' . rawurlencode($queryTz);
    $response = api_get_json($url, $rapidHost, $rapidKey);
    if ($response['error'] !== '') {
        respond_json(['success' => false, 'message' => 'API connection failed.', 'debug' => ['error' => $response['error']]], 502);
    }
    $json = $response['json'];
    if ($response['http_code'] >= 400) {
        respond_json([
            'success' => false,
            'message' => is_array($json) ? (string)($json['message'] ?? 'API error') : 'API error',
            'http_code' => $response['http_code']
        ], 502);
    }
    $out = $json;
    $list = isset($json['data']) && is_array($json['data']) ? $json['data'] : (is_array($json) ? $json : []);
    if (is_array($list) && count($list) > 0) {
        $tzOnly = [];
        foreach ($list as $item) {
            if (!is_array($item)) { $tzOnly[] = $item; continue; }
            $country = isset($item['country']) ? trim((string)$item['country']) : '';
            $name = isset($item['name']) ? (string)$item['name'] : (isset($item['label']) ? (string)$item['label'] : '');
            if (stripos($country, 'tanzania') !== false || stripos($name, 'tanzania') !== false || $country === '' || $name === '') {
                $tzOnly[] = $item;
            }
        }
        if (count($tzOnly) > 0) {
            $out = isset($json['data']) ? array_merge($json, ['data' => $tzOnly]) : $tzOnly;
        }
    }
    respond_json(['success' => true, 'data' => $out], 200);
}

// --- GET: Search Hotel Destination (Tanzania only, more results) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'searchDestination')) {
    $rapidHost = 'booking-com15.p.rapidapi.com';
    $rapidKey = getenv('RAPIDAPI_KEY_BOOKING') ?: 'fd397681d2mshb56198e459d62f6p1068b7jsn2b13d9e93e59';
    $query = trim((string)($_GET['query'] ?? ''));
    if ($query === '') {
        respond_json(['success' => false, 'message' => 'Query is required.'], 400);
    }
    // Tanzania only: bias query; fetch main + extra mikoa for more data
    $queryTz = $query;
    if (stripos($query, 'tanzania') === false) {
        $queryTz = $query . ' Tanzania';
    }
    $url = 'https://' . $rapidHost . '/api/v1/hotels/searchDestination?query=' . rawurlencode($queryTz);
    $response = api_get_json($url, $rapidHost, $rapidKey);
    if ($response['error'] !== '') {
        respond_json(['success' => false, 'message' => 'API connection failed.', 'debug' => ['error' => $response['error']]], 502);
    }
    $json = $response['json'];
    if ($response['http_code'] >= 400) {
        respond_json([
            'success' => false,
            'message' => is_array($json) ? (string)($json['message'] ?? 'API error') : 'API error',
            'http_code' => $response['http_code']
        ], 502);
    }
    $list = [];
    if (isset($json['data']) && is_array($json['data'])) {
        $list = $json['data'];
    } elseif (is_array($json)) {
        $list = $json;
    }
    $seen = [];
    $tanzaniaOnly = [];
    foreach ($list as $item) {
        if (!is_array($item)) continue;
        $country = isset($item['country']) ? trim((string)$item['country']) : '';
        $label = isset($item['label']) ? (string)$item['label'] : (isset($item['name']) ? (string)$item['name'] : '');
        if (stripos($country, 'tanzania') !== false || stripos($label, 'tanzania') !== false) {
            $key = $label . '|' . ($item['latitude'] ?? '') . '|' . ($item['longitude'] ?? '');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $tanzaniaOnly[] = $item;
            }
        }
    }
    if (count($tanzaniaOnly) === 0 && count($list) > 0) {
        $tanzaniaOnly = $list;
    }
    // When query is generic (Tanzania / mikoa), fetch more regions for more results
    $isGeneric = in_array(strtolower($query), ['tanzania', 'mikoa', 'mkoa', 'tz', ''], true)
        || (strlen($query) <= 3 && stripos($queryTz, 'tanzania') !== false);
    if ($isGeneric && count($tanzaniaOnly) < 50) {
        $extraQueries = ['Dar es Salaam Tanzania', 'Zanzibar Tanzania', 'Mwanza Tanzania', 'Arusha Tanzania', 'Dodoma Tanzania'];
        foreach ($extraQueries as $eq) {
            if (count($tanzaniaOnly) >= 80) break;
            $u = 'https://' . $rapidHost . '/api/v1/hotels/searchDestination?query=' . rawurlencode($eq);
            $r = api_get_json($u, $rapidHost, $rapidKey);
            if ($r['error'] !== '' || $r['http_code'] >= 400) continue;
            $j = $r['json'];
            $extraList = isset($j['data']) && is_array($j['data']) ? $j['data'] : (is_array($j) ? $j : []);
            foreach ($extraList as $item) {
                if (!is_array($item)) continue;
                $country = isset($item['country']) ? trim((string)$item['country']) : '';
                $label = isset($item['label']) ? (string)$item['label'] : (isset($item['name']) ? (string)$item['name'] : '');
                if (stripos($country, 'tanzania') === false && stripos($label, 'tanzania') === false) continue;
                $key = $label . '|' . ($item['latitude'] ?? '') . '|' . ($item['longitude'] ?? '');
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $tanzaniaOnly[] = $item;
                }
            }
        }
    }
    respond_json(['success' => true, 'data' => $tanzaniaOnly], 200);
}

function respond_json($payload, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function api_get_json($url, $host, $key) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            "x-rapidapi-host: {$host}",
            "x-rapidapi-key: {$key}"
        ],
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => (int)$httpCode,
        'error' => (string)$error,
        'json' => json_decode((string)$response, true),
        'raw' => (string)$response
    ];
}

function array_path_get($arr, $path, $default = null) {
    $node = $arr;
    foreach ($path as $segment) {
        if (!is_array($node) || !array_key_exists($segment, $node)) {
            return $default;
        }
        $node = $node[$segment];
    }
    return $node;
}

function first_value($arr, $paths, $default = '') {
    foreach ($paths as $path) {
        $v = array_path_get($arr, $path, null);
        if ($v !== null && $v !== '') {
            return $v;
        }
    }
    return $default;
}

function normalize_price($value) {
    if (is_numeric($value)) {
        return '$' . number_format((float)$value, 2);
    }
    if (is_string($value)) {
        $trimmed = trim($value);
        return $trimmed;
    }
    if (is_array($value)) {
        $formatted = first_value($value, [
            ['formatted'],
            ['display'],
            ['text'],
            ['currencyFormat']
        ], '');
        if ($formatted !== '') {
            return (string)$formatted;
        }
        $amount = first_value($value, [
            ['amount'],
            ['value'],
            ['price'],
            ['total']
        ], '');
        if ($amount !== '') {
            return is_numeric($amount) ? ('$' . number_format((float)$amount, 2)) : (string)$amount;
        }
    }
    return '';
}

function looks_like_booking_item($node) {
    if (!is_array($node)) {
        return false;
    }

    $markers = 0;
    $possibleKeys = [
        'supplier', 'vendor', 'company', 'provider', 'host',
        'price', 'pricing', 'total_price', 'totalPrice', 'priceBreakdown',
        'vehicle', 'car', 'pick_up', 'drop_off', 'pickup', 'dropoff', 'location'
    ];
    foreach ($possibleKeys as $k) {
        if (array_key_exists($k, $node)) {
            $markers++;
        }
    }
    return $markers >= 2;
}

function extract_candidates_recursive($payload, &$collector, $maxNodes = 1500) {
    if (count($collector) >= $maxNodes) {
        return;
    }
    if (!is_array($payload)) {
        return;
    }
    if (looks_like_booking_item($payload)) {
        $collector[] = $payload;
    }
    foreach ($payload as $v) {
        if (is_array($v)) {
            extract_candidates_recursive($v, $collector, $maxNodes);
        }
        if (count($collector) >= $maxNodes) {
            return;
        }
    }
}

function normalize_booking_item($node, $fallbackLocation = '') {
    if (!is_array($node)) {
        return null;
    }

    $hostName = (string)first_value($node, [
        ['host', 'name'],
        ['supplier', 'name'],
        ['vendor', 'name'],
        ['company', 'name'],
        ['provider', 'name'],
        ['merchant', 'name'],
        ['brand'],
        ['partner_name']
    ], '');
    if ($hostName === '') {
        $hostName = 'Booking Partner';
    }

    $priceRaw = first_value($node, [
        ['price'],
        ['total_price'],
        ['totalPrice'],
        ['pricing', 'amount'],
        ['pricing', 'total'],
        ['priceBreakdown', 'total'],
        ['priceBreakdown', 'base'],
        ['payment', 'amount'],
        ['quote', 'price']
    ], '');
    $price = normalize_price($priceRaw);
    if ($price === '') {
        $price = 'N/A';
    }

    $vehicleName = (string)first_value($node, [
        ['vehicle', 'name'],
        ['vehicle', 'model'],
        ['car', 'name'],
        ['car', 'model'],
        ['name'],
        ['title']
    ], '');

    $location = (string)first_value($node, [
        ['pick_up', 'name'],
        ['pick_up_location', 'name'],
        ['pickup', 'name'],
        ['pickupLocation', 'name'],
        ['drop_off', 'name'],
        ['drop_off_location', 'name'],
        ['location', 'name'],
        ['location'],
        ['city'],
        ['address']
    ], '');
    if ($location === '') {
        $location = $fallbackLocation !== '' ? $fallbackLocation : 'N/A';
    }

    $description = (string)first_value($node, [
        ['description'],
        ['summary'],
        ['details'],
        ['vehicle', 'description'],
        ['car', 'description'],
        ['policies', 'description'],
        ['terms']
    ], '');
    if ($description === '') {
        $description = trim(($vehicleName !== '' ? $vehicleName : 'Car Rental Option') . ' by ' . $hostName);
    }

    $listingName = $vehicleName !== '' ? $vehicleName : (string)first_value($node, [['name'], ['title']], 'Car Rental');
    $deeplink = (string)first_value($node, [
        ['deep_link'],
        ['deeplink'],
        ['booking_url'],
        ['url']
    ], 'https://www.booking.com/');

    if ($hostName === '' && $price === 'N/A' && $description === '' && $location === 'N/A') {
        return null;
    }

    return [
        'name' => $listingName,
        'host_name' => $hostName,
        'price' => $price,
        'description' => $description,
        'location' => $location,
        'url' => $deeplink
    ];
}

if (!function_exists('curl_init')) {
    respond_json([
        'success' => false,
        'message' => 'cURL is not enabled on this server.'
    ], 500);
}

$queryRaw = trim($_GET['query'] ?? $_POST['query'] ?? '');
$limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 30);

if ($queryRaw === '') {
    respond_json([
        'success' => false,
        'message' => 'query is required.'
    ], 400);
}

if ($limit < 3 || $limit > 10000) {
    $limit = 500;
}

// Booking car rentals endpoint shared by user.
$rapidHost = 'booking-com15.p.rapidapi.com';
$rapidKey = 'fd397681d2mshb56198e459d62f6p1068b7jsn2b13d9e93e59';
$locationCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $queryRaw), 0, 2));
if (strlen($locationCode) !== 2) {
    $locationCode = 'US';
}
$pickupDate = date('Y-m-d', strtotime('+2 days'));
$dropoffDate = date('Y-m-d', strtotime('+5 days'));

// Endpoint as provided by user (Booking.com cars search)
$url = "https://{$rapidHost}/api/v1/cars/searchCarRentals"
    . "?pick_up_latitude=40.6397018432617"
    . "&pick_up_longitude=-73.7791976928711"
    . "&drop_off_latitude=40.6397018432617"
    . "&drop_off_longitude=-73.7791976928711"
    . "&pick_up_time=10%3A00"
    . "&drop_off_time=10%3A00"
    . "&driver_age=30"
    . "&currency_code=USD"
    . "&location=" . rawurlencode($locationCode)
    . "&pick_up_date=" . rawurlencode($pickupDate)
    . "&drop_off_date=" . rawurlencode($dropoffDate);

$response = api_get_json($url, $rapidHost, $rapidKey);
if ($response['error'] !== '') {
    respond_json([
        'success' => false,
        'message' => 'Booking API connection failed.',
        'debug' => ['error' => $response['error']]
    ], 502);
}
if ($response['http_code'] >= 400 || !is_array($response['json'])) {
    $apiMessage = '';
    if (is_array($response['json'])) {
        $apiMessage = (string)first_value($response['json'], [
            ['message'],
            ['error'],
            ['errors', 0, 'message']
        ], '');
    }
    if ($apiMessage === '') {
        $apiMessage = 'Booking API returned an error.';
    }

    if ((int)$response['http_code'] === 403 && stripos($apiMessage, 'not subscribed') !== false) {
        $apiMessage = 'RapidAPI key is not subscribed to booking-com15 API plan.';
    }

    respond_json([
        'success' => false,
        'message' => $apiMessage,
        'debug' => [
            'http_code' => $response['http_code'],
            'raw' => substr($response['raw'], 0, 500)
        ]
    ], 502);
}

$payload = $response['json'];
$apiStatus = $payload['status'] ?? null;
$apiMessage = is_array($payload['message'] ?? null)
    ? json_encode($payload['message'])
    : (string)($payload['message'] ?? '');

if ($apiStatus === false) {
    respond_json([
        'success' => false,
        'message' => $apiMessage !== '' ? $apiMessage : 'Booking API returned status=false.',
        'debug' => [
            'http_code' => $response['http_code'],
            'location' => $locationCode,
            'endpoint' => $url
        ]
    ], 502);
}

$candidates = [];

$commonPaths = [
    ['data', 'search_results'],
    ['data', 'results'],
    ['data', 'cars'],
    ['data', 'available_cars'],
    ['results'],
    ['cars'],
    ['data']
];
foreach ($commonPaths as $path) {
    $items = array_path_get($payload, $path, null);
    if (is_array($items)) {
        foreach ($items as $item) {
            if (is_array($item)) {
                $candidates[] = $item;
            }
        }
    }
}

extract_candidates_recursive($payload, $candidates);

$records = [];
$seen = [];
foreach ($candidates as $candidate) {
    $normalized = normalize_booking_item($candidate, $queryRaw);
    if ($normalized === null) {
        continue;
    }
    $key = md5(
        strtolower($normalized['host_name'] . '|' . $normalized['price'] . '|' . $normalized['location'] . '|' . $normalized['name'])
    );
    if (isset($seen[$key])) {
        continue;
    }
    $seen[$key] = true;
    $records[] = $normalized;
    if (count($records) >= $limit) {
        break;
    }
}

if (empty($records)) {
    respond_json([
        'success' => false,
        'message' => 'No Booking.com records found for this search.',
        'debug' => [
            'http_code' => $response['http_code'],
            'records_detected' => count($candidates)
        ]
    ], 404);
}

respond_json([
    'success' => true,
    'query' => $queryRaw,
    'location_code' => $locationCode,
    'count' => count($records),
    'records' => $records
], 200);

