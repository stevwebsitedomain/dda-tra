<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/config.php';

function respond($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function first_value($arr, $paths, $default = '') {
    foreach ($paths as $path) {
        $node = $arr;
        $ok = true;
        foreach ($path as $seg) {
            if (!is_array($node) || !array_key_exists($seg, $node)) {
                $ok = false;
                break;
            }
            $node = $node[$seg];
        }
        if ($ok && $node !== null && $node !== '') {
            return $node;
        }
    }
    return $default;
}

function build_room_url_from_id($id) {
    $digits = preg_replace('/\D+/', '', (string)$id);
    if ($digits === '') return '';
    return 'https://www.airbnb.com/rooms/' . $digits;
}

function normalize_room_url($value) {
    $q = trim((string)$value);
    if ($q === '') return '';
    if (preg_match('~^https?://~i', $q) && stripos($q, 'airbnb.') !== false) {
        if (preg_match('~/rooms/(\d+)~', $q, $m)) {
            return build_room_url_from_id($m[1]);
        }
        return '';
    }
    if (preg_match('/^\d+$/', $q)) {
        return build_room_url_from_id($q);
    }
    return '';
}

function parse_query_to_room_urls($queryRaw) {
    $parts = preg_split('/[\s,;\n\r\t]+/', (string)$queryRaw);
    $urls = [];
    $seen = [];
    foreach ($parts as $part) {
        $u = normalize_room_url($part);
        if ($u === '') continue;
        if (isset($seen[$u])) continue;
        $seen[$u] = true;
        $urls[] = $u;
    }
    return $urls;
}

function extract_candidates($payload) {
    if (!is_array($payload)) return [];
    $paths = [
        ['results'],
        ['data', 'results'],
        ['search_results'],
        ['data', 'search_results'],
        ['listings'],
        ['data', 'listings'],
        ['items'],
        ['data', 'items']
    ];
    $out = [];
    foreach ($paths as $path) {
        $node = first_value($payload, [$path], null);
        if (is_array($node)) {
            foreach ($node as $item) {
                if (is_array($item)) $out[] = $item;
            }
        }
    }
    if (!empty($out)) return $out;
    if (isset($payload[0]) && is_array($payload[0])) return $payload;
    if (is_array($payload) && (isset($payload['id']) || isset($payload['listing_id']) || isset($payload['name']))) return [$payload];
    return [];
}

function normalize_location_record($node) {
    if (!is_array($node)) return null;
    $listingId = (string)first_value($node, [['listing_id'], ['listingId'], ['id'], ['room_id']], '');
    $name = (string)first_value($node, [['name'], ['title'], ['headline']], '');
    $url = (string)first_value($node, [['url'], ['listing_url'], ['room_url']], '');
    if ($url === '' && $listingId !== '') {
        $url = build_room_url_from_id($listingId);
    }
    if ($name === '' && $listingId === '') return null;

    return [
        'listing_id' => $listingId,
        'name' => $name !== '' ? $name : 'N/A',
        'host_name' => (string)first_value($node, [['host', 'name'], ['host_name']], 'N/A'),
        'host_phone' => (string)first_value($node, [['host', 'phone'], ['host_phone'], ['phone']], 'N/A'),
        'location_name' => (string)first_value($node, [['city'], ['location', 'city'], ['location_name']], ''),
        'neighborhood' => (string)first_value($node, [['neighborhood'], ['location', 'neighborhood']], ''),
        'lat' => first_value($node, [['lat'], ['latitude'], ['location', 'lat']], ''),
        'lng' => first_value($node, [['lng'], ['longitude'], ['location', 'lng']], ''),
        'room_price' => (string)first_value($node, [['price'], ['room_price']], ''),
        'description' => (string)first_value($node, [['description'], ['summary']], ''),
        'url' => $url,
        'source' => 'airbnb_realtime'
    ];
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
        ]
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [
        'http_code' => $http,
        'error' => (string)$err,
        'json' => json_decode((string)$raw, true),
        'raw' => (string)$raw
    ];
}

if (($_GET['action'] ?? '') === 'status') {
    respond(['success' => true, 'service' => 'airbnb_realtime_search', 'status' => function_exists('curl_init') ? 'online' : 'degraded']);
}

if (!function_exists('curl_init')) {
    respond(['success' => false, 'message' => 'cURL is not enabled on this server.'], 500);
}

$queryRaw = trim((string)($_GET['query'] ?? $_POST['query'] ?? ''));
$limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 100);
if ($limit < 100 || $limit > 10000) $limit = 100;

if ($queryRaw === '') {
    respond(['success' => false, 'message' => 'query is required. Paste Airbnb room URLs or room IDs.'], 422);
}

$roomUrls = parse_query_to_room_urls($queryRaw);

$envHost = trim((string)($env['RAPIDAPI_HOST_AIRBNB_REALTIME'] ?? 'real-time-airbnb-host-profile-property-data-api.p.rapidapi.com'));
$envKey = trim((string)($env['RAPIDAPI_KEY_AIRBNB'] ?? ($env['RAPIDAPI_KEY'] ?? '')));
if ($envKey === '') {
    respond(['success' => false, 'message' => 'Missing RAPIDAPI_KEY_AIRBNB in .env'], 500);
}

$hardCap = 400; // Prevent very long blocking requests on one search.
$debug = [];
$records = [];

if (!empty($roomUrls)) {
    $target = min($limit, count($roomUrls), $hardCap);
    for ($i = 0; $i < $target; $i++) {
        $roomUrl = $roomUrls[$i];
        $url = "https://{$envHost}/airbnb/room-detail/?room_url=" . rawurlencode($roomUrl);
        $resp = api_get_json($url, $envHost, $envKey);
        $debug[] = [
            'mode' => 'room_detail',
            'room_url' => $roomUrl,
            'url' => $url,
            'http_code' => $resp['http_code'],
            'error' => $resp['error']
        ];
        if ($resp['error'] !== '' || $resp['http_code'] >= 400 || !is_array($resp['json'])) {
            continue;
        }
        $payload = is_array($resp['json']['data'] ?? null) ? $resp['json']['data'] : $resp['json'];
        $row = normalize_location_record($payload);
        if (!$row) continue;
        if ($row['url'] === '') $row['url'] = $roomUrl;
        if ($row['listing_id'] === '' && preg_match('~/rooms/(\d+)~', $roomUrl, $m)) {
            $row['listing_id'] = (string)$m[1];
        }
        $records[] = $row;
    }
} else {
    $airbnb13Host = trim((string)($env['RAPIDAPI_HOST_AIRBNB'] ?? 'airbnb13.p.rapidapi.com'));
    $searchPath = trim((string)($env['AIRBNB13_REALTIME_SEARCH_PATH'] ?? '/search-geo'));
    $searchParam = trim((string)($env['AIRBNB13_REALTIME_SEARCH_PARAM'] ?? 'location'));
    if ($searchPath === '' || $searchPath[0] !== '/') $searchPath = '/search-geo';
    if ($searchParam === '') $searchParam = 'location';

    $candidates = [];
    $queryEnc = rawurlencode($queryRaw);
    $candidates[] = "https://{$airbnb13Host}{$searchPath}?{$searchParam}={$queryEnc}";
    $candidates[] = "https://{$airbnb13Host}{$searchPath}?location={$queryEnc}";
    $candidates[] = "https://{$airbnb13Host}{$searchPath}?query={$queryEnc}";
    $candidates[] = "https://{$airbnb13Host}/search-location?location={$queryEnc}&checkin=2026-03-01&checkout=2026-03-05&adults=1&page=1&currency=USD";

    foreach ($candidates as $url) {
        $resp = api_get_json($url, $airbnb13Host, $envKey);
        $debug[] = [
            'mode' => 'location_search',
            'url' => $url,
            'http_code' => $resp['http_code'],
            'error' => $resp['error']
        ];
        if ($resp['error'] !== '' || $resp['http_code'] >= 400 || !is_array($resp['json'])) {
            continue;
        }
        $payload = is_array($resp['json']['data'] ?? null) ? $resp['json']['data'] : $resp['json'];
        $nodes = extract_candidates($payload);
        if (empty($nodes)) continue;
        foreach ($nodes as $node) {
            $r = normalize_location_record($node);
            if ($r) $records[] = $r;
            if (count($records) >= min($limit, $hardCap)) break;
        }
        if (!empty($records)) break;
    }
}

if (empty($records)) {
    respond([
        'success' => false,
        'message' => 'No Airbnb records returned. Use room URL/ID or location text and verify API quota.',
        'debug' => ['attempts' => $debug]
    ], 404);
}

respond([
    'success' => true,
    'count' => count($records),
    'processed' => $target,
    'requested_limit' => $limit,
    'records' => $records,
    'debug' => ['attempts' => $debug]
], 200);

