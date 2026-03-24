<?php
header('Content-Type: application/json; charset=UTF-8');
@ini_set('max_execution_time', '120');

function respond_json($payload, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function get_path($node, $path, $default = null) {
    $cursor = $node;
    foreach ($path as $segment) {
        if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
            return $default;
        }
        $cursor = $cursor[$segment];
    }
    return $cursor;
}

function first_value($node, $paths, $default = '') {
    foreach ($paths as $path) {
        $value = get_path($node, $path, null);
        if ($value !== null && $value !== '') {
            return $value;
        }
    }
    return $default;
}

function to_float_or_null($value) {
    if (is_numeric($value)) {
        return (float)$value;
    }
    if (is_string($value) && preg_match('/-?\d+(?:\.\d+)?/', $value, $m)) {
        return (float)$m[0];
    }
    return null;
}

function normalize_price($value) {
    if (is_numeric($value)) {
        return '$' . number_format((float)$value, 2);
    }
    if (is_string($value)) {
        return trim($value);
    }
    if (is_array($value)) {
        $formatted = first_value($value, [
            ['formatted'],
            ['display'],
            ['amountFormatted'],
            ['priceString'],
            ['label'],
            ['price']
        ], '');
        if ($formatted !== '') {
            return (string)$formatted;
        }
    }
    return '';
}

function looks_like_listing($node) {
    if (!is_array($node)) {
        return false;
    }
    $signals = 0;
    $keys = ['id', 'listingId', 'listing_id', 'name', 'title', 'host', 'price', 'address', 'location', 'url'];
    foreach ($keys as $k) {
        if (array_key_exists($k, $node)) {
            $signals++;
        }
    }
    return $signals >= 2;
}

function normalize_record($node, $fallbackLocation = '') {
    if (!is_array($node)) {
        return null;
    }
    if (isset($node['listing']) && is_array($node['listing'])) {
        $node = $node['listing'];
    }
    if (isset($node['room']) && is_array($node['room'])) {
        $node = $node['room'];
    }

    $listingId = (string)first_value($node, [
        ['listingId'],
        ['listing_id'],
        ['id'],
        ['roomId']
    ], '');
    $title = (string)first_value($node, [
        ['name'],
        ['title'],
        ['headline']
    ], '');
    $address = (string)first_value($node, [
        ['address'],
        ['fullAddress'],
        ['publicAddress'],
        ['location', 'address'],
        ['location', 'addressLine1']
    ], '');
    $city = (string)first_value($node, [
        ['city'],
        ['location', 'city'],
        ['location', 'cityName'],
        ['address', 'city']
    ], '');
    if ($city === '' && $fallbackLocation !== '') {
        $city = $fallbackLocation;
    }
    $neighborhood = (string)first_value($node, [
        ['neighborhood'],
        ['location', 'neighborhood'],
        ['address', 'district']
    ], '');

    $lat = to_float_or_null(first_value($node, [
        ['lat'],
        ['latitude'],
        ['location', 'lat'],
        ['location', 'latitude'],
        ['coordinates', 'lat'],
        ['coordinates', 'latitude']
    ], null));
    $lng = to_float_or_null(first_value($node, [
        ['lng'],
        ['lon'],
        ['longitude'],
        ['location', 'lng'],
        ['location', 'longitude'],
        ['coordinates', 'lng'],
        ['coordinates', 'longitude']
    ], null));

    $price = normalize_price(first_value($node, [
        ['price'],
        ['nightlyPrice'],
        ['rate'],
        ['pricing'],
        ['pricing', 'price'],
        ['priceDetails']
    ], ''));
    $stars = first_value($node, [
        ['stars'],
        ['starRating'],
        ['rating'],
        ['reviewScore']
    ], '');
    $reviews = first_value($node, [
        ['reviewsCount'],
        ['reviewCount'],
        ['reviews', 'count'],
        ['reviews']
    ], '');
    $availability = first_value($node, [
        ['availability'],
        ['availabilityStatus'],
        ['isAvailable']
    ], '');
    $guestCapacity = first_value($node, [
        ['guests'],
        ['guestCapacity'],
        ['maxGuests'],
        ['capacity', 'guests']
    ], '');
    $bedrooms = first_value($node, [
        ['bedrooms'],
        ['bedroomCount'],
        ['capacity', 'bedrooms']
    ], '');
    $beds = first_value($node, [
        ['beds'],
        ['bedCount'],
        ['capacity', 'beds']
    ], '');
    $bathrooms = first_value($node, [
        ['bathrooms'],
        ['bathroomCount'],
        ['capacity', 'bathrooms']
    ], '');
    $url = (string)first_value($node, [
        ['url'],
        ['listingUrl'],
        ['roomUrl'],
        ['shareUrl']
    ], '');
    $description = (string)first_value($node, [
        ['description'],
        ['summary'],
        ['headline']
    ], '');

    $hostName = (string)first_value($node, [
        ['host', 'name'],
        ['host', 'fullName'],
        ['hostName'],
        ['owner', 'name']
    ], '');
    $hostPhone = (string)first_value($node, [
        ['host', 'phone'],
        ['hostPhone'],
        ['contact', 'phone']
    ], '');

    $imagesValue = first_value($node, [
        ['images'],
        ['photos'],
        ['pictures']
    ], []);
    $imagesCount = is_array($imagesValue) ? count($imagesValue) : 0;

    if ($title === '' && $listingId === '' && $url === '') {
        return null;
    }

    if ($title === '') {
        $title = $listingId !== '' ? ('Airbnb Listing #' . $listingId) : 'Airbnb Listing';
    }
    if ($listingId === '' && preg_match('~/rooms/(\d+)~', $url, $m)) {
        $listingId = (string)$m[1];
    }

    $username = $listingId !== '' ? ('airbnb2_' . $listingId) : ('airbnb2_' . substr(md5($title . '|' . $city), 0, 10));
    $bio = trim(($description !== '' ? $description : $title) .
        ($hostName !== '' ? ' | Host: ' . $hostName : '') .
        ($hostPhone !== '' ? ' | Host Phone: ' . $hostPhone : '') .
        ($city !== '' ? ' | City: ' . $city : '') .
        ($price !== '' ? ' | Price: ' . $price : '') .
        ($guestCapacity !== '' ? ' | Guests: ' . $guestCapacity : '') .
        ($bedrooms !== '' ? ' | Bedrooms: ' . $bedrooms : '') .
        ($beds !== '' ? ' | Beds: ' . $beds : '') .
        ($bathrooms !== '' ? ' | Bathrooms: ' . $bathrooms : '') .
        ($stars !== '' ? ' | Stars: ' . $stars : '') .
        ($reviews !== '' ? ' | Reviews: ' . $reviews : '')
    );

    return [
        'listing_id' => $listingId,
        'name' => $title,
        'host_name' => $hostName !== '' ? $hostName : 'N/A',
        'host_phone' => $hostPhone,
        'address' => $address,
        'city' => $city,
        'neighborhood' => $neighborhood,
        'lat' => $lat,
        'lng' => $lng,
        'room_price' => $price,
        'stars' => $stars,
        'reviews_count' => $reviews,
        'availability' => is_bool($availability) ? ($availability ? 'Available' : 'Unavailable') : (string)$availability,
        'images_count' => $imagesCount,
        'guest_capacity' => $guestCapacity,
        'bedrooms' => $bedrooms,
        'beds' => $beds,
        'bathrooms' => $bathrooms,
        'description' => $description,
        'url' => $url,
        // Compatible with save_to_db.php payload
        'username' => $username,
        'phone' => $hostPhone,
        'bio' => $bio,
        'location_name' => $city,
        'latitude' => $lat !== null ? (string)$lat : '',
        'longitude' => $lng !== null ? (string)$lng : '',
        'source' => 'airbnb2'
    ];
}

function collect_listing_nodes($node, &$bucket, $depth = 0) {
    if ($depth > 10 || !is_array($node)) {
        return;
    }
    if (looks_like_listing($node)) {
        $bucket[] = $node;
    }
    foreach ($node as $child) {
        if (is_array($child)) {
            collect_listing_nodes($child, $bucket, $depth + 1);
        }
    }
}

function call_airbnb2_api($payload, $key) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://airbnb-scraper.p.rapidapi.com/ping',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-rapidapi-host: airbnb-scraper.p.rapidapi.com',
            'x-rapidapi-key: ' . $key
        ],
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => (int)$httpCode,
        'error' => (string)$error,
        'raw' => (string)$response,
        'json' => json_decode((string)$response, true)
    ];
}

function call_get_json($url, $headers = []) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => $headers
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [
        'http_code' => (int)$httpCode,
        'error' => (string)$error,
        'raw' => (string)$response,
        'json' => json_decode((string)$response, true)
    ];
}

function fallback_airbnb13_records($query, $limit, $rapidApiKey) {
    $rapidHost = 'airbnb13.p.rapidapi.com';
    $url = "https://{$rapidHost}/search-location?location=" . rawurlencode($query) .
        "&checkin=2026-03-01&checkout=2026-03-05&adults=1&children=0&infants=0&pets=0&page=1&currency=USD";
    $resp = call_get_json($url, [
        "x-rapidapi-host: {$rapidHost}",
        "x-rapidapi-key: {$rapidApiKey}"
    ]);
    if ($resp['error'] !== '' || $resp['http_code'] >= 400 || !is_array($resp['json'])) {
        return [];
    }

    $payload = $resp['json'];
    $candidateNodes = [];
    foreach ([['results'], ['data', 'results'], ['listings'], ['data', 'listings'], ['items'], ['data', 'items']] as $path) {
        $node = get_path($payload, $path, null);
        if (!is_array($node)) {
            continue;
        }
        foreach ($node as $item) {
            if (is_array($item)) {
                $candidateNodes[] = $item;
            }
        }
    }
    if (empty($candidateNodes)) {
        collect_listing_nodes($payload, $candidateNodes);
    }

    $records = [];
    $seen = [];
    foreach ($candidateNodes as $node) {
        $normalized = normalize_record($node, $query);
        if ($normalized === null) {
            continue;
        }
        $key = $normalized['listing_id'] !== '' ? $normalized['listing_id'] : md5(($normalized['name'] ?? '') . '|' . ($normalized['city'] ?? ''));
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $records[] = $normalized;
        if (count($records) >= $limit) {
            break;
        }
    }
    return $records;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'status')) {
    respond_json([
        'success' => true,
        'service' => 'airbnb2_search',
        'status' => function_exists('curl_init') ? 'online' : 'degraded'
    ]);
}

if (!function_exists('curl_init')) {
    respond_json(['success' => false, 'message' => 'cURL extension is not enabled.'], 500);
}

$query = trim($_GET['query'] ?? $_POST['query'] ?? $_GET['locationQuery'] ?? $_POST['locationQuery'] ?? '');
if ($query === '') {
    $query = 'Sacramento, California';
}
$maxListings = (int)($_GET['limit'] ?? $_POST['limit'] ?? $_GET['maxListings'] ?? $_POST['maxListings'] ?? 20);
if ($maxListings < 1 || $maxListings > 300) {
    $maxListings = 20;
}
$maxReviews = (int)($_GET['maxReviews'] ?? $_POST['maxReviews'] ?? 10);
if ($maxReviews < 0 || $maxReviews > 200) {
    $maxReviews = 10;
}
$currency = trim($_GET['currency'] ?? $_POST['currency'] ?? 'USD');
if ($currency === '') {
    $currency = 'USD';
}
$checkIn = trim($_GET['checkIn'] ?? $_POST['checkIn'] ?? date('Y-m-d', strtotime('+14 days')));
$checkOut = trim($_GET['checkOut'] ?? $_POST['checkOut'] ?? date('Y-m-d', strtotime('+16 days')));

$rapidApiKey = 'fd397681d2mshb56198e459d62f6p1068b7jsn2b13d9e93e59';
$apiPayload = [
    'locationQuery' => $query,
    'maxListings' => $maxListings,
    'includeReviews' => null,
    'maxReviews' => $maxReviews,
    'currency' => $currency,
    'checkIn' => $checkIn,
    'checkOut' => $checkOut
];

$apiResponse = call_airbnb2_api($apiPayload, $rapidApiKey);
$records = [];
$provider = 'airbnb-scraper.ping';
$warning = '';

if ($apiResponse['error'] === '' && $apiResponse['http_code'] < 400 && is_array($apiResponse['json'])) {
    $payload = $apiResponse['json'];
    $candidateNodes = [];
    foreach ([
        ['data'],
        ['results'],
        ['listings'],
        ['data', 'results'],
        ['data', 'listings'],
        ['properties'],
        ['items']
    ] as $path) {
        $node = get_path($payload, $path, null);
        if (is_array($node)) {
            collect_listing_nodes($node, $candidateNodes);
        }
    }
    if (empty($candidateNodes)) {
        collect_listing_nodes($payload, $candidateNodes);
    }

    $seen = [];
    foreach ($candidateNodes as $node) {
        $normalized = normalize_record($node, $query);
        if ($normalized === null) {
            continue;
        }
        $key = $normalized['listing_id'] !== '' ? $normalized['listing_id'] : md5(($normalized['name'] ?? '') . '|' . ($normalized['address'] ?? '') . '|' . ($normalized['city'] ?? ''));
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $records[] = $normalized;
        if (count($records) >= $maxListings) {
            break;
        }
    }
}

if (empty($records)) {
    $provider = 'airbnb13.search-location (fallback)';
    $warning = 'Primary endpoint returned no data or temporary upstream error; fallback provider used.';
    $records = fallback_airbnb13_records($query, $maxListings, $rapidApiKey);
}

if (empty($records)) {
    respond_json([
        'success' => false,
        'message' => 'No Airbnb records found. Upstream provider unavailable or returned empty data.',
        'upstream_http' => $apiResponse['http_code'],
        'upstream_error' => $apiResponse['error'],
        'upstream_raw' => substr((string)$apiResponse['raw'], 0, 800)
    ], 502);
}

$uniqueHosts = [];
$ratingsTotal = 0.0;
$ratingsCount = 0;
$withReviews = 0;
$withCoordinates = 0;
foreach ($records as $r) {
    $host = trim((string)($r['host_name'] ?? ''));
    if ($host !== '' && strtoupper($host) !== 'N/A') {
        $uniqueHosts[strtolower($host)] = true;
    }
    if (isset($r['stars']) && is_numeric($r['stars'])) {
        $ratingsTotal += (float)$r['stars'];
        $ratingsCount++;
    }
    if (isset($r['reviews_count']) && is_numeric($r['reviews_count']) && (int)$r['reviews_count'] > 0) {
        $withReviews++;
    }
    if (($r['lat'] ?? null) !== null && ($r['lng'] ?? null) !== null) {
        $withCoordinates++;
    }
}

$stats = [
    'total_listings' => count($records),
    'unique_hosts' => count($uniqueHosts),
    'avg_stars' => $ratingsCount > 0 ? round($ratingsTotal / $ratingsCount, 2) : 0,
    'with_reviews' => $withReviews,
    'with_coordinates' => $withCoordinates
];

respond_json([
    'success' => true,
    'query' => $query,
    'count' => count($records),
    'provider' => $provider,
    'warning' => $warning,
    'stats' => $stats,
    'records' => $records
], 200);
?>
