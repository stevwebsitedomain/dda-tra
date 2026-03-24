<?php
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'status')) {
    $curlAvailable = function_exists('curl_init');
    echo json_encode([
        "success" => true,
        "service" => "tiktok_search",
        "status" => $curlAvailable ? "online" : "degraded",
        "curl_available" => $curlAvailable
    ]);
    exit;
}

function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function array_get($array, $path, $default = null) {
    $current = $array;
    foreach ($path as $key) {
        if (!is_array($current) || !array_key_exists($key, $current)) {
            return $default;
        }
        $current = $current[$key];
    }
    return $current;
}

function first_existing_path($array, $paths, $default = null) {
    foreach ($paths as $path) {
        $value = array_get($array, $path, null);
        if ($value !== null && $value !== '') {
            return $value;
        }
    }
    return $default;
}

function detect_phone_number($text) {
    if (!is_string($text) || $text === '') {
        return '';
    }

    // Pick first potential phone-like string from text.
    if (preg_match('/(?:\+?\d[\d\-\s\(\)]{7,}\d)/u', $text, $matches)) {
        $raw = trim($matches[0]);
        // Normalize extra spaces and symbols for cleaner display.
        $normalized = preg_replace('/\s+/', ' ', $raw);
        $digits = preg_replace('/\D+/', '', $normalized);
        if (strlen((string)$digits) >= 8) {
            return $normalized;
        }
    }

    return '';
}

function extract_phone_from_item($item) {
    if (!is_array($item)) return '';

    // Common places where number can appear in TikTok payloads.
    $phoneCandidates = [
        first_existing_path($item, [['desc']], ''),
        first_existing_path($item, [['title']], ''),
        first_existing_path($item, [['text']], ''),
        first_existing_path($item, [['caption']], ''),
        first_existing_path($item, [['author', 'signature']], ''),
        first_existing_path($item, [['authorInfo', 'signature']], ''),
        first_existing_path($item, [['author', 'bioLink', 'link']], ''),
        first_existing_path($item, [['authorInfo', 'bioLink', 'link']], ''),
        first_existing_path($item, [['author', 'bio_url']], ''),
        first_existing_path($item, [['authorInfo', 'bio_url']], ''),
    ];

    foreach ($phoneCandidates as $candidate) {
        $phone = detect_phone_number((string)$candidate);
        if ($phone !== '') return $phone;
    }

    // Last fallback: scan textExtra array values.
    $textExtra = first_existing_path($item, [['textExtra']], []);
    if (is_array($textExtra)) {
        foreach ($textExtra as $node) {
            if (!is_array($node)) continue;
            foreach ($node as $value) {
                if (!is_scalar($value)) continue;
                $phone = detect_phone_number((string)$value);
                if ($phone !== '') return $phone;
            }
        }
    }

    return '';
}

function rapidapi_get_json($url, $rapidHost, $rapidKey) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "x-rapidapi-host: {$rapidHost}",
            "x-rapidapi-key: {$rapidKey}"
        ],
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode((string)$response, true);
    if (!is_array($decoded)) {
        $decoded = null;
    }

    return [
        "http_code" => (int)$httpCode,
        "error" => (string)$err,
        "raw" => (string)$response,
        "json" => $decoded
    ];
}

function extract_item_list($payload) {
    if (!is_array($payload)) {
        return [];
    }
    $items = first_existing_path($payload, [
        ['itemList'],
        ['data', 'itemList'],
        ['aweme_list'],
        ['data', 'aweme_list']
    ], []);
    return is_array($items) ? $items : [];
}

function extract_search_items_fallback($payload) {
    if (!is_array($payload)) {
        return [];
    }

    $candidates = first_existing_path($payload, [
        ['item_list'],
        ['data', 'item_list'],
        ['data'],
        ['items'],
        ['result'],
        ['results']
    ], []);

    if (!is_array($candidates)) {
        return [];
    }

    $out = [];
    foreach ($candidates as $node) {
        if (!is_array($node)) continue;
        // Many search endpoints wrap video inside item/aweme_info.
        if (isset($node['item']) && is_array($node['item'])) {
            $out[] = $node['item'];
            continue;
        }
        if (isset($node['aweme_info']) && is_array($node['aweme_info'])) {
            $out[] = $node['aweme_info'];
            continue;
        }
        $out[] = $node;
    }
    return $out;
}

function find_first_challenge_id($payload) {
    if (!is_array($payload)) {
        return '';
    }

    $candidatePaths = [
        ['challengeInfo', 'challenge', 'id'],
        ['challengeInfo', 'challenge', 'cid'],
        ['data', 'challengeInfo', 'challenge', 'id'],
        ['data', 'challengeInfo', 'challenge', 'cid'],
        ['challenge', 'id'],
        ['challenge', 'cid'],
        ['data', 'challenge', 'id'],
        ['data', 'challenge', 'cid'],
        ['challenge_list', 0, 'cid'],
        ['challenge_list', 0, 'id'],
        ['data', 'challenge_list', 0, 'cid'],
        ['data', 'challenge_list', 0, 'id'],
        ['sug_list', 0, 'challenge_info', 'cid'],
        ['sug_list', 0, 'challenge_info', 'id'],
        ['data', 'sug_list', 0, 'challenge_info', 'cid'],
        ['data', 'sug_list', 0, 'challenge_info', 'id']
    ];

    return (string)first_existing_path($payload, $candidatePaths, '');
}

function payload_has_api_error($payload) {
    if (!is_array($payload)) {
        return true;
    }

    $statusCodes = [
        first_existing_path($payload, [['statusCode']], null),
        first_existing_path($payload, [['status_code']], null),
        first_existing_path($payload, [['data', 'statusCode']], null),
        first_existing_path($payload, [['data', 'status_code']], null)
    ];

    foreach ($statusCodes as $code) {
        if ($code === null || $code === '') {
            continue;
        }
        if ((string)$code !== '0') {
            return true;
        }
    }

    $msg = strtolower((string)first_existing_path($payload, [['message'], ['msg'], ['error'], ['status_msg'], ['data', 'status_msg']], ''));
    if ($msg !== '' && (
        strpos($msg, 'does not exist') !== false ||
        strpos($msg, 'invalid') !== false ||
        strpos($msg, 'required') !== false ||
        strpos($msg, 'forbidden') !== false ||
        strpos($msg, 'unauthorized') !== false
    )) {
        return true;
    }

    return false;
}

function extract_cursor_value($payload) {
    $cursor = first_existing_path($payload, [
        ['cursor'],
        ['nextCursor'],
        ['next_cursor'],
        ['data', 'cursor'],
        ['data', 'nextCursor'],
        ['data', 'next_cursor']
    ], '');
    return (string)$cursor;
}

function extract_has_more_flag($payload) {
    $raw = first_existing_path($payload, [
        ['hasMore'],
        ['has_more'],
        ['data', 'hasMore'],
        ['data', 'has_more']
    ], null);
    if ($raw === null) {
        return false;
    }
    if (is_bool($raw)) {
        return $raw;
    }
    if (is_numeric($raw)) {
        return ((int)$raw) > 0;
    }
    $v = strtolower((string)$raw);
    return in_array($v, ['true', '1', 'yes'], true);
}

function set_url_cursor($url, $cursor) {
    $cursor = rawurlencode((string)$cursor);
    if (strpos($url, 'cursor=') !== false) {
        return preg_replace('/([?&])cursor=[^&]*/', '$1cursor=' . $cursor, $url);
    }
    return $url . (strpos($url, '?') !== false ? '&' : '?') . 'cursor=' . $cursor;
}

function item_unique_key($item) {
    if (!is_array($item)) {
        return '';
    }
    $id = (string)first_existing_path($item, [
        ['id'],
        ['aweme_id'],
        ['awemeId'],
        ['item_id'],
        ['video', 'id'],
        ['awemeInfo', 'awemeId']
    ], '');
    if ($id !== '') {
        return 'id:' . $id;
    }

    $author = (string)first_existing_path($item, [
        ['author', 'uniqueId'],
        ['author', 'unique_id'],
        ['authorInfo', 'uniqueId'],
        ['user', 'unique_id'],
        ['user', 'uniqueId']
    ], '');
    $desc = (string)first_existing_path($item, [['desc'], ['title'], ['text'], ['caption']], '');
    return 'sig:' . md5($author . '|' . $desc);
}

$hashtag = trim($_GET['hashtag'] ?? $_POST['hashtag'] ?? '');
$count = (int)($_GET['count'] ?? $_POST['count'] ?? 50);
$limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 100);
$cursor = (int)($_GET['cursor'] ?? $_POST['cursor'] ?? 0);

if ($hashtag === '') {
    json_response([
        "success" => false,
        "message" => "hashtag is required."
    ], 400);
}

$hashtag = ltrim($hashtag, '#');

if ($count < 1 || $count > 100) {
    $count = 50;
}
if ($limit < 20 || $limit > 500) {
    $limit = 100;
}

$hashtag = preg_replace('/[^a-zA-Z0-9_]/', '', $hashtag);
if ($hashtag === '') {
    json_response([
        "success" => false,
        "message" => "Invalid hashtag format."
    ], 422);
}

if (!function_exists('curl_init')) {
    json_response([
        "success" => false,
        "message" => "cURL is not enabled on this PHP server."
    ], 500);
}

$rapidHost = "tiktok-api23.p.rapidapi.com";
$rapidKey = "fd397681d2mshb56198e459d62f6p1068b7jsn2b13d9e93e59";

// Step 1: try direct challenge posts by hashtag name
$decoded = null;
$items = [];
$attempts = [];
$successfulUrl = '';

$directPostUrls = [
    "https://{$rapidHost}/api/challenge/posts?challengeName=" . rawurlencode($hashtag) . "&count={$count}&cursor={$cursor}"
];

foreach ($directPostUrls as $url) {
    $resp = rapidapi_get_json($url, $rapidHost, $rapidKey);
    $msg = is_array($resp['json']) ? first_existing_path($resp['json'], [['message'], ['msg'], ['error']], '') : '';
    $attempts[] = [
        "step" => "direct_posts",
        "url" => $url,
        "http_code" => $resp['http_code'],
        "error" => $resp['error'],
        "message" => $msg
    ];

    if ($resp['error'] !== '' || $resp['http_code'] >= 400 || !is_array($resp['json'])) {
        continue;
    }

    if (payload_has_api_error($resp['json'])) {
        continue;
    }

    $candidateItems = extract_item_list($resp['json']);
    $decoded = $resp['json'];
    $items = $candidateItems;
    $successfulUrl = $url;
    break;
}

// Step 2: if direct posts fail, resolve challenge ID first, then get posts by ID
if (!is_array($decoded) || empty($items)) {
    $challengeId = '';
    $infoUrls = [
        "https://{$rapidHost}/api/challenge/info?challengeName=" . rawurlencode($hashtag)
    ];

    foreach ($infoUrls as $url) {
        $resp = rapidapi_get_json($url, $rapidHost, $rapidKey);
        $msg = is_array($resp['json']) ? first_existing_path($resp['json'], [['message'], ['msg'], ['error']], '') : '';
        $attempts[] = [
            "step" => "challenge_info",
            "url" => $url,
            "http_code" => $resp['http_code'],
            "error" => $resp['error'],
            "message" => $msg
        ];

        if ($resp['error'] !== '' || $resp['http_code'] >= 400 || !is_array($resp['json'])) {
            continue;
        }

        $challengeId = find_first_challenge_id($resp['json']);

        if ($challengeId !== '') {
            break;
        }
    }

    if ($challengeId !== '') {
        $postByIdUrls = [
            "https://{$rapidHost}/api/challenge/posts?challengeID=" . rawurlencode($challengeId) . "&count={$count}&cursor={$cursor}"
        ];

        foreach ($postByIdUrls as $url) {
            $resp = rapidapi_get_json($url, $rapidHost, $rapidKey);
            $msg = is_array($resp['json']) ? first_existing_path($resp['json'], [['message'], ['msg'], ['error']], '') : '';
            $attempts[] = [
                "step" => "posts_by_challenge_id",
                "url" => $url,
                "http_code" => $resp['http_code'],
                "error" => $resp['error'],
                "message" => $msg
            ];

            if ($resp['error'] !== '' || $resp['http_code'] >= 400 || !is_array($resp['json'])) {
                continue;
            }

            if (payload_has_api_error($resp['json'])) {
                continue;
            }

            $candidateItems = extract_item_list($resp['json']);
            $decoded = $resp['json'];
            $items = $candidateItems;
            $successfulUrl = $url;
            break;
        }
    }
}

// Step 3: challenge/hashtag search fallback to resolve challenge ID
if ((!is_array($decoded) || empty($items))) {
    $challengeId = '';
    $searchUrls = [
        "https://{$rapidHost}/api/challenge/search?keyword=" . rawurlencode($hashtag) . "&count=10&cursor=0",
        "https://{$rapidHost}/api/search/hashtag?keyword=" . rawurlencode($hashtag) . "&count=10&cursor=0"
    ];

    foreach ($searchUrls as $url) {
        $resp = rapidapi_get_json($url, $rapidHost, $rapidKey);
        $msg = is_array($resp['json']) ? first_existing_path($resp['json'], [['message'], ['msg'], ['error']], '') : '';
        $attempts[] = [
            "step" => "challenge_search_fallback",
            "url" => $url,
            "http_code" => $resp['http_code'],
            "error" => $resp['error'],
            "message" => $msg
        ];

        if ($resp['error'] !== '' || $resp['http_code'] >= 400 || !is_array($resp['json'])) {
            continue;
        }
        if (payload_has_api_error($resp['json'])) {
            continue;
        }

        $challengeId = find_first_challenge_id($resp['json']);
        if ($challengeId !== '') {
            break;
        }
    }

    if ($challengeId !== '') {
        $postByIdUrls = [
            "https://{$rapidHost}/api/challenge/posts?challengeID=" . rawurlencode($challengeId) . "&count={$count}&cursor={$cursor}"
        ];

        foreach ($postByIdUrls as $url) {
            $resp = rapidapi_get_json($url, $rapidHost, $rapidKey);
            $msg = is_array($resp['json']) ? first_existing_path($resp['json'], [['message'], ['msg'], ['error']], '') : '';
            $attempts[] = [
                "step" => "posts_by_search_challenge_id",
                "url" => $url,
                "http_code" => $resp['http_code'],
                "error" => $resp['error'],
                "message" => $msg
            ];

            if ($resp['error'] !== '' || $resp['http_code'] >= 400 || !is_array($resp['json'])) {
                continue;
            }
            if (payload_has_api_error($resp['json'])) {
                continue;
            }

            $candidateItems = extract_item_list($resp['json']);
            $decoded = $resp['json'];
            $items = $candidateItems;
            $successfulUrl = $url;
            break;
        }
    }
}

// Step 4: keyword search fallback (works even when challenge endpoints vary)
if ((!is_array($decoded) || empty($items))) {
    $keywordUrls = [
        "https://{$rapidHost}/api/search/general?keyword=" . rawurlencode('#' . $hashtag) . "&count={$count}&cursor={$cursor}",
        "https://{$rapidHost}/api/search/general?keyword=" . rawurlencode($hashtag) . "&count={$count}&cursor={$cursor}",
        "https://{$rapidHost}/api/search/video?keyword=" . rawurlencode($hashtag) . "&count={$count}&cursor={$cursor}"
    ];

    foreach ($keywordUrls as $url) {
        $resp = rapidapi_get_json($url, $rapidHost, $rapidKey);
        $msg = is_array($resp['json']) ? first_existing_path($resp['json'], [['message'], ['msg'], ['error']], '') : '';
        $attempts[] = [
            "step" => "keyword_search_fallback",
            "url" => $url,
            "http_code" => $resp['http_code'],
            "error" => $resp['error'],
            "message" => $msg
        ];

        if ($resp['error'] !== '' || $resp['http_code'] >= 400 || !is_array($resp['json'])) {
            continue;
        }
        if (payload_has_api_error($resp['json'])) {
            continue;
        }

        $candidateItems = extract_item_list($resp['json']);
        if (empty($candidateItems)) {
            $candidateItems = extract_search_items_fallback($resp['json']);
        }

        $decoded = $resp['json'];
        $items = is_array($candidateItems) ? $candidateItems : [];
        $successfulUrl = $url;
        break;
    }
}

if (!is_array($decoded)) {
    $lastAttempt = end($attempts);
    $lastMessage = is_array($lastAttempt) ? ($lastAttempt['message'] ?? '') : '';
    $lastCode = is_array($lastAttempt) ? (int)($lastAttempt['http_code'] ?? 502) : 502;
    $friendly = $lastMessage !== '' ? $lastMessage : "RapidAPI request failed for hashtag/challenge endpoint.";

    json_response([
        "success" => false,
        "message" => $friendly,
        "http_code" => $lastCode,
        "debug" => [
            "hashtag" => $hashtag,
            "attempts" => $attempts
        ]
    ], $lastCode >= 400 ? $lastCode : 502);
}

if (!is_array($items)) {
    $items = [];
}

// Pull multiple pages until we reach requested limit.
if ($successfulUrl !== '' && count($items) > 0 && $limit > count($items)) {
    $allItems = $items;
    $seen = [];
    foreach ($allItems as $existing) {
        $seen[item_unique_key($existing)] = true;
    }

    $nextCursor = extract_cursor_value($decoded);
    $hasMore = extract_has_more_flag($decoded);
    $pageCalls = 0;

    $maxPageCalls = (int)max(1, min(4, ceil($limit / max(1, $count))));
    while ($hasMore && $nextCursor !== '' && count($allItems) < $limit && $pageCalls < $maxPageCalls) {
        $pageCalls++;
        $pageUrl = set_url_cursor($successfulUrl, $nextCursor);
        $resp = rapidapi_get_json($pageUrl, $rapidHost, $rapidKey);
        $msg = is_array($resp['json']) ? first_existing_path($resp['json'], [['message'], ['msg'], ['error']], '') : '';
        $attempts[] = [
            "step" => "pagination",
            "url" => $pageUrl,
            "http_code" => $resp['http_code'],
            "error" => $resp['error'],
            "message" => $msg
        ];

        if ($resp['error'] !== '' || $resp['http_code'] >= 400 || !is_array($resp['json'])) {
            break;
        }
        if (payload_has_api_error($resp['json'])) {
            break;
        }

        $pageItems = extract_item_list($resp['json']);
        if (empty($pageItems)) {
            $pageItems = extract_search_items_fallback($resp['json']);
        }
        if (!is_array($pageItems) || count($pageItems) === 0) {
            break;
        }

        foreach ($pageItems as $pi) {
            $k = item_unique_key($pi);
            if ($k !== '' && isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $allItems[] = $pi;
            if (count($allItems) >= $limit) {
                break;
            }
        }

        $nextCursor = extract_cursor_value($resp['json']);
        $hasMore = extract_has_more_flag($resp['json']);
    }

    $items = array_slice($allItems, 0, $limit);
}

$records = [];
foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }

    $username = first_existing_path($item, [
        ['author', 'uniqueId'],
        ['author', 'unique_id'],
        ['author', 'nickname'],
        ['authorInfo', 'uniqueId'],
        ['authorInfo', 'unique_id'],
        ['user_info', 'unique_id'],
        ['user', 'unique_id'],
        ['user', 'uniqueId']
    ], 'unknown');

    $description = first_existing_path($item, [
        ['desc'],
        ['title'],
        ['text'],
        ['caption']
    ], '');

    $phone = extract_phone_from_item($item);

    $records[] = [
        "username" => (string)$username,
        "phone" => (string)$phone,
        "bio" => (string)$description,
        "profile_url" => (string)($username && $username !== 'unknown' ? "https://www.tiktok.com/@" . $username : '')
    ];
}

json_response([
    "success" => true,
    "hashtag" => $hashtag,
    "count" => count($records),
    "records" => $records,
    "debug" => [
        "attempts" => $attempts,
        "items_found" => is_array($items) ? count($items) : 0
    ]
], 200);

