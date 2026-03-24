<?php
/**
 * Upgraded Airbnb API – proxy for airbnb45.p.rapidapi.com (subscribed/paid).
 * Endpoints: test (verification), search (listings).
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/config.php';

function json_out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function call_airbnb45($path, $queryParams = [], $env) {
    $host = trim((string)($env['RAPIDAPI_HOST_AIRBNB45'] ?? 'airbnb45.p.rapidapi.com'));
    $key  = trim((string)($env['RAPIDAPI_KEY_AIRBNB45'] ?? $env['RAPIDAPI_KEY_AIRBNB'] ?? ''));
    if ($key === '') {
        return ['http_code' => 0, 'error' => 'Missing RAPIDAPI_KEY_AIRBNB45 in .env', 'raw' => '', 'json' => null];
    }

    $url = 'https://' . $host . $path;
    if (!empty($queryParams)) {
        $url .= (strpos($path, '?') !== false ? '&' : '?') . http_build_query($queryParams);
    }

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
            'x-rapidapi-host: ' . $host,
            'x-rapidapi-key: ' . $key
        ],
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = null;
    if ($response !== false && $response !== '') {
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $json = $decoded;
        }
    }

    return [
        'http_code' => $httpCode,
        'error' => $err ?: '',
        'raw' => (string)$response,
        'json' => $json
    ];
}

// Status/Test (for dashboard and UI)
$action = trim($_GET['action'] ?? '');
if ($action === 'status' || $action === 'test') {
    $res = call_airbnb45('/api/v1/test', [], $env);
    $ok = ($res['error'] === '' && $res['http_code'] >= 200 && $res['http_code'] < 300);
    json_out([
        'success' => $ok,
        'service' => 'upgraded_airbnb',
        'status' => $ok ? 'online' : 'offline',
        'http_code' => $res['http_code'],
        'message' => $ok ? 'Airbnb45 API connected.' : ($res['error'] ?: 'HTTP ' . $res['http_code'])
    ]);
}

// Search
if ($action !== 'search') {
    json_out(['success' => false, 'message' => 'Unknown action. Use action=status or action=search.'], 400);
}

$query = trim($_GET['query'] ?? $_GET['location'] ?? '');
$limit = (int)($_GET['limit'] ?? 50);
if ($limit < 1 || $limit > 500) {
    $limit = 50;
}

if ($query === '') {
    json_out(['success' => false, 'message' => 'Query or location is required.'], 422);
}

// Use only Airbnb45 searchLocation endpoint: GET .../api/v1/searchLocation?query=...
$attempts = [];
$records = [];
$pathOnly = '/api/v1/searchLocation';
$q = ['query' => $query];

$res = call_airbnb45($pathOnly, $q, $env);
$attempts[] = [
    'path' => $pathOnly,
    'params' => $q,
    'http_code' => $res['http_code'],
    'error' => $res['error'],
];

$list = null;
if ($res['error'] === '' && $res['http_code'] >= 200 && $res['http_code'] < 300) {
    if (is_array($res['json'])) {
        $list = $res['json'];
        if (isset($list['results']) && is_array($list['results'])) {
            $list = $list['results'];
        } elseif (isset($list['data']) && is_array($list['data'])) {
            $list = $list['data'];
        } elseif (isset($list['listings']) && is_array($list['listings'])) {
            $list = $list['listings'];
        } elseif (isset($list['rooms']) && is_array($list['rooms'])) {
            $list = $list['rooms'];
        } elseif (isset($list['search_results']) && is_array($list['search_results'])) {
            $list = $list['search_results'];
        } elseif (isset($list['sections']) && is_array($list['sections'])) {
            foreach ($list['sections'] as $sec) {
                if (isset($sec['listings']) && is_array($sec['listings'])) {
                    $list = $sec['listings'];
                    break;
                }
            }
        } elseif (!is_array($list) || isset($list['id']) || isset($list['listing_id'])) {
            $list = [$list];
        }
    } elseif (is_string($res['raw']) && trim($res['raw']) !== '') {
        // API returned plain text / CSV: split by newline, then each line by comma
        $lines = preg_split('/\r\n|\r|\n/', trim($res['raw']));
        $list = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $arr = array_map('trim', str_getcsv($line));
            if (count($arr) >= 12) {
                $list[] = $arr;
            }
        }
    }
}

if ($list !== null && is_array($list)) {
        foreach ($list as $item) {
            $rec = null;
            if (is_array($item)) {
                if (isset($item[10]) && isset($item[11]) && (is_numeric($item[10]) || is_numeric($item[11]))) {
                    $rec = parse_location_row($item);
                } else {
                    $rec = normalize_listing($item);
                }
            } elseif (is_string($item) && trim($item) !== '') {
                $arr = array_map('trim', str_getcsv($item));
                if (count($arr) >= 12 && (is_numeric($arr[10] ?? '') || is_numeric($arr[11] ?? ''))) {
                    $rec = parse_location_row($arr);
                }
            }
            if ($rec !== null) {
                $records[] = $rec;
                if (count($records) >= $limit) {
                    break;
                }
            }
        }
}

// If no results, return with message (no fallback to other endpoints)
if (empty($records)) {
    $testRes = call_airbnb45('/api/v1/test', [], $env);
    $apiOk = ($testRes['error'] === '' && $testRes['http_code'] >= 200 && $testRes['http_code'] < 300);
    json_out([
        'success' => true,
        'query' => $query,
        'count' => 0,
        'records' => [],
        'message' => $apiOk
            ? 'No listings found for this location. Try another search (e.g. city or area name).'
            : 'API error. Check RAPIDAPI_KEY_AIRBNB45 and subscription.',
        'debug' => ['attempts' => $attempts]
    ]);
}

json_out([
    'success' => true,
    'query' => $query,
    'count' => count($records),
    'records' => $records,
    'debug' => ['attempts' => $attempts]
]);

/**
 * Parse location row: Arusha, Tanzania, ChIJ..., administrative_area_level_1, political, 0, Arusha, 8, Tanzania, TZ, lat, lng, bound1, bound2, TZ
 * Indices: 0=name, 1=country, 2=place_id, 3=type, 4=type2, 5=?, 6=city, 7=?, 8=country2, 9=code, 10=lat, 11=lng, 12=bound_lat, 13=bound_lng, 14=code2
 */
function parse_location_row($arr) {
    if (!is_array($arr) || count($arr) < 12) {
        return null;
    }
    $get = function ($i, $def = '') use ($arr) {
        return isset($arr[$i]) && $arr[$i] !== '' && $arr[$i] !== null ? trim((string)$arr[$i]) : $def;
    };
    $lat = $get(10);
    $lng = $get(11);
    if ($lat === '' && $lng === '') {
        return null;
    }
    $name = $get(0);
    $city = $get(6) ?: $name;
    $username = 'loc_' . substr(md5($name . $city . $lat . $lng), 0, 12);
    return [
        'username' => $username,
        'listing_id' => $get(2),
        'name' => $name,
        'host_name' => '',
        'host_phone' => '',
        'phone' => '',
        'city' => $city,
        'region' => $get(3),
        'country' => $get(1) ?: $get(8),
        'location_name' => trim($get(0) . ', ' . $get(1)),
        'neighborhood' => '',
        'place_id' => $get(2),
        'type' => $get(3),
        'type2' => $get(4),
        'country_code' => $get(9) ?: $get(14),
        'lat' => $lat,
        'lng' => $lng,
        'latitude' => $lat,
        'longitude' => $lng,
        'lat2' => $get(12),
        'lng2' => $get(13),
        'bound_lat' => $get(12),
        'bound_lng' => $get(13),
        'room_price' => '',
        'price' => '',
        'description' => '',
        'url' => 'https://www.google.com/maps?q=' . urlencode($lat . ',' . $lng),
        'listing_url' => 'https://www.google.com/maps?q=' . urlencode($lat . ',' . $lng),
    ];
}

/** Convert value to string; if array, join with comma (handles Airbnb45 location/city as array). */
function to_display_str($v) {
    if ($v === null || $v === '') {
        return '';
    }
    if (is_array($v)) {
        $parts = [];
        foreach ($v as $x) {
            if (is_array($x)) {
                $parts[] = to_display_str($x);
            } elseif ($x !== null && $x !== '') {
                $parts[] = trim((string)$x);
            }
        }
        return implode(', ', $parts);
    }
    return trim((string)$v);
}

function normalize_listing($node) {
    if (!is_array($node)) {
        return null;
    }
    $id = to_display_str($node['listing_id'] ?? $node['listingId'] ?? $node['id'] ?? $node['room_id'] ?? '');
    $name = to_display_str($node['name'] ?? $node['title'] ?? $node['listing_name'] ?? $node['headline'] ?? '');
    $hostName = '';
    if (isset($node['host']) && is_array($node['host'])) {
        $hostName = to_display_str($node['host']['name'] ?? $node['host']['full_name'] ?? $node['host']['first_name'] ?? '');
    }
    $hostName = $hostName ?: to_display_str($node['host_name'] ?? $node['hostName'] ?? '');
    $hostPhone = to_display_str($node['host_phone'] ?? $node['hostPhone'] ?? $node['phone'] ?? '');
    if ($hostPhone === '' && isset($node['host']['phone'])) {
        $hostPhone = to_display_str($node['host']['phone']);
    }
    // Build specific location: neighborhood, city, region, country (no duplicates, in order)
    $addr = $node['address'] ?? [];
    $locParts = [];
    $neighborhood = to_display_str($node['neighborhood'] ?? $node['neighbourhood'] ?? $addr['neighborhood'] ?? $addr['neighbourhood'] ?? $addr['suburb'] ?? '');
    $city = to_display_str($node['city'] ?? $node['location'] ?? $node['location_name'] ?? $addr['city'] ?? $addr['town'] ?? $addr['municipality'] ?? '');
    $region = to_display_str($node['region'] ?? $node['state'] ?? $addr['state'] ?? $addr['region'] ?? $addr['county'] ?? '');
    $country = to_display_str($node['country'] ?? $addr['country'] ?? $addr['country_code'] ?? '');
    if ($city === '' && !empty($node['location'])) {
        $city = to_display_str($node['location']);
    }
    if ($city === '' && is_array($addr)) {
        $city = to_display_str($addr['city'] ?? $addr['location'] ?? $addr);
    }
    foreach ([$neighborhood, $city, $region, $country] as $p) {
        if ($p !== '' && !in_array($p, $locParts, true)) {
            $locParts[] = $p;
        }
    }
    $specificLocation = implode(', ', $locParts);
    if ($specificLocation === '' && ($node['location'] ?? null) !== null) {
        $specificLocation = to_display_str($node['location']);
    }
    if ($specificLocation === '') {
        $specificLocation = $city;
    }
    if ($city === '' && $specificLocation !== '') {
        $city = $specificLocation;
    }
    $lat = to_display_str($node['lat'] ?? $node['latitude'] ?? '');
    $lng = to_display_str($node['lng'] ?? $node['longitude'] ?? '');
    if ($lat === '' && is_array($node['coordinates'] ?? null)) {
        $co = $node['coordinates'];
        $lat = to_display_str($co['lat'] ?? $co['latitude'] ?? '');
        $lng = to_display_str($co['lng'] ?? $co['longitude'] ?? '');
    }
    $price = '';
    if (isset($node['price'])) {
        $p = $node['price'];
        $price = is_array($p) ? to_display_str($p['formatted'] ?? $p['amount'] ?? $p['total'] ?? '') : to_display_str($p);
        if ($price !== '' && is_numeric($price) && strpos($price, '$') === false) {
            $price = '$' . number_format((float)$price, 2);
        }
    }
    $price = $price ?: to_display_str($node['room_price'] ?? $node['price_rate'] ?? '');
    $description = to_display_str($node['description'] ?? $node['summary'] ?? $node['notes'] ?? $name);
    $url = to_display_str($node['url'] ?? $node['listing_url'] ?? $node['link'] ?? '');
    if ($url === '' && $id !== '') {
        $url = 'https://www.airbnb.com/rooms/' . preg_replace('/\D+/', '', $id);
    }
    $username = $id !== '' ? 'listing_' . $id : ('item_' . substr(md5($name . $city), 0, 12));
    return [
        'username' => $username,
        'listing_id' => $id,
        'name' => $name,
        'host_name' => $hostName,
        'host_phone' => $hostPhone,
        'phone' => $hostPhone,
        'city' => $city,
        'region' => $region,
        'country' => $country,
        'location_name' => $specificLocation,
        'neighborhood' => $neighborhood,
        'lat' => $lat,
        'lng' => $lng,
        'latitude' => $lat,
        'longitude' => $lng,
        'room_price' => $price,
        'price' => $price,
        'description' => $description,
        'url' => $url,
        'listing_url' => $url,
    ];
}
