<?php
require_once __DIR__ . '/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=Please Login First');
    exit();
}

$user_name = $_SESSION['user_name'] ?? 'Admin';
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$apiHost = 'easysendsms.p.rapidapi.com';
$apiUrl = 'https://easysendsms.p.rapidapi.com/bulksms';
$apiKey = $env['RAPIDAPI_KEY'] ??
    ($env['EASYSENDSMS_RAPIDAPI_KEY'] ??
    ($env['RAPIDAPI_KEY_BOOKING'] ??
    ($env['RAPIDAPI_KEY_AIRBNB'] ?? '')));
$smsUsername = trim((string)($env['EASYSENDSMS_USERNAME'] ?? ''));
$smsPassword = trim((string)($env['EASYSENDSMS_PASSWORD'] ?? ''));

$statusType = '';
$statusText = '';
$apiResponsePreview = '';
$apiDebugPreview = '';

function normalize_tz_phone($raw) {
    $digits = preg_replace('/\D+/', '', (string)$raw);
    if ($digits === '') return '';

    // Accept: +2557..., 2557..., 07..., 7...
    if (preg_match('/^255[67]\d{8}$/', $digits)) return $digits;
    if (preg_match('/^0[67]\d{8}$/', $digits)) return '255' . substr($digits, 1);
    if (preg_match('/^[67]\d{8}$/', $digits)) return '255' . $digits;

    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenOk = isset($_POST['csrf']) && hash_equals($csrf, (string)$_POST['csrf']);
    if (!$tokenOk) {
        $statusType = 'danger';
        $statusText = 'Session invalid. Please refresh the page and try again.';
    } else {
        $numbersInput = trim((string)($_POST['phone_numbers'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));
        $senderId = trim((string)($_POST['sender_id'] ?? 'TRA'));
        if ($senderId === '') $senderId = 'TRA';

        $tokens = preg_split('/[\s,;]+/', $numbersInput) ?: [];
        $cleanNumbers = [];
        $invalidNumbers = [];
        foreach ($tokens as $t) {
            $t = trim((string)$t);
            if ($t === '') continue;
            $n = normalize_tz_phone($t);
            if ($n !== '') $cleanNumbers[] = $n;
            else $invalidNumbers[] = $t;
        }
        $cleanNumbers = array_values(array_unique($cleanNumbers));

        if (!$apiKey) {
            $statusType = 'warning';
            $statusText = 'RapidAPI key is missing. Add RAPIDAPI_KEY to the .env file.';
        } elseif (!$cleanNumbers) {
            $statusType = 'warning';
            $statusText = 'Enter valid Tanzania phone numbers (example: 2557XXXXXXXX).';
        } elseif ($invalidNumbers) {
            $statusType = 'warning';
            $statusText = 'Kuna namba zisizo sahihi za Tanzania: ' . implode(', ', array_slice($invalidNumbers, 0, 6));
        } elseif ($message === '') {
            $statusType = 'warning';
            $statusText = 'Enter a message before sending.';
        } else {
            $payload = [
                // EasySendSMS send payload (multiple aliases for RapidAPI compatibility)
                'from' => $senderId,
                'to' => implode(',', $cleanNumbers),
                'text' => $message,
                'type' => '0', // 0 = plain text, 1 = unicode
                'sender' => $senderId,
                'sender_id' => $senderId,
                'numbers' => implode(',', $cleanNumbers),
                'recipients' => implode(',', $cleanNumbers),
                'message' => $message
            ];
            if ($smsUsername !== '' && $smsPassword !== '') {
                // Some /bulksms integrations still require account username/password.
                $payload['username'] = $smsUsername;
                $payload['password'] = $smsPassword;
            }

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => http_build_query($payload),
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded',
                    'x-rapidapi-host: ' . $apiHost,
                    'x-rapidapi-key: ' . $apiKey
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $headerSize = (int)curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $contentType = (string)curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
            curl_close($curl);

            if ($err) {
                $statusType = 'danger';
                $statusText = 'SMS send failed: ' . $err;
            } else {
                $raw = (string)$response;
                $headersRaw = substr($raw, 0, $headerSize);
                $bodyRaw = substr($raw, $headerSize);
                $responseText = trim($bodyRaw);
                $decoded = json_decode($responseText, true);
                $apiErrorCode = is_array($decoded) && isset($decoded['error']) ? (string)$decoded['error'] : '';
                $apiErrorDesc = is_array($decoded) && isset($decoded['description']) ? (string)$decoded['description'] : '';
                $apiStatus = is_array($decoded) && isset($decoded['status']) ? strtoupper((string)$decoded['status']) : '';
                $apiSuccess = is_array($decoded) && isset($decoded['success']) ? (bool)$decoded['success'] : null;
                $apiCode = is_array($decoded) && isset($decoded['code']) ? (int)$decoded['code'] : 0;
                $messageIds = is_array($decoded) && isset($decoded['messageIds']) && is_array($decoded['messageIds'])
                    ? $decoded['messageIds']
                    : [];
                $hasErrInIds = false;
                foreach ($messageIds as $mid) {
                    if (stripos((string)$mid, 'ERR:') !== false) {
                        $hasErrInIds = true;
                        break;
                    }
                }

                $jsonLooksSuccess = (
                    ($apiStatus !== '' && in_array($apiStatus, ['OK', 'SUCCESS', 'SENT', 'QUEUED'], true)) ||
                    ($apiSuccess === true) ||
                    ($apiCode >= 200 && $apiCode < 300) ||
                    (!empty($messageIds))
                );
                $jsonLooksFailed = (
                    $apiErrorCode !== '' ||
                    ($apiStatus !== '' && in_array($apiStatus, ['ERROR', 'FAILED', 'REJECTED'], true)) ||
                    ($apiSuccess === false)
                );

                $textLooksSuccess = false;
                $textLooksFailed = false;
                if (!is_array($decoded)) {
                    $low = strtolower($responseText);
                    $textLooksSuccess = (strpos($low, 'ok') !== false || strpos($low, 'success') !== false || strpos($low, 'queued') !== false || strpos($low, 'sent') !== false);
                    $textLooksFailed = (
                        strpos($low, 'error') !== false ||
                        strpos($low, 'invalid') !== false ||
                        strpos($low, 'failed') !== false ||
                        strpos($low, 'inactive') !== false ||
                        strpos($low, 'insufficient') !== false ||
                        strpos($low, 'not subscribed') !== false ||
                        strpos($low, 'unauthorized') !== false ||
                        strpos($low, 'forbidden') !== false ||
                        strpos($low, 'quota') !== false ||
                        strpos($low, 'rate limit') !== false
                    );
                }

                if ($responseText === '') {
                    $statusType = 'danger';
                    $statusText = 'SMS failed: API imerudisha HTTP ' . $httpCode . ' lakini body ni tupu. Inawezekana endpoint/subscription/params si sahihi.';
                } elseif ($responseText === '1002' || $apiErrorCode === '1002') {
                    $statusType = 'danger';
                    $statusText = 'SMS failed [1002]: Invalid username/password. Add EASYSENDSMS_USERNAME and EASYSENDSMS_PASSWORD to the .env file.';
                } elseif ($httpCode >= 200 && $httpCode < 300 && !$jsonLooksFailed && ($jsonLooksSuccess || $textLooksSuccess)) {
                    $statusType = $hasErrInIds ? 'warning' : 'success';
                    $statusText = $hasErrInIds
                        ? 'SMS request accepted, lakini baadhi ya namba zimekataa (angalia API Response).'
                        : 'Bulk SMS sent successfully.';
                } elseif ($httpCode >= 200 && $httpCode < 300 && !$textLooksFailed && $responseText !== '') {
                    $statusType = 'warning';
                    $preview = mb_substr(preg_replace('/\s+/', ' ', $responseText), 0, 180);
                    $statusText = 'Request accepted (HTTP 200), lakini status ya delivery haijaeleweka wazi. Preview: ' . $preview;
                } else {
                    $statusType = 'danger';
                    $statusText = 'SMS failed' .
                        ($apiErrorCode !== '' ? (' [Code ' . $apiErrorCode . ']') : '') .
                        ($apiErrorDesc !== '' ? (': ' . $apiErrorDesc) : (' (HTTP ' . $httpCode . ')'));
                }
                $apiResponsePreview = $responseText;
                $apiDebugPreview =
                    "HTTP: " . $httpCode . "\n" .
                    "Content-Type: " . ($contentType !== '' ? $contentType : 'N/A') . "\n" .
                    "Endpoint: " . $apiUrl . "\n" .
                    "Numbers Count: " . count($cleanNumbers) . "\n" .
                    "Has Username/Password: " . (($smsUsername !== '' && $smsPassword !== '') ? 'YES' : 'NO') . "\n" .
                    "Payload Keys: " . implode(', ', array_keys($payload)) . "\n\n" .
                    "Response Headers:\n" . trim($headersRaw);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <title>TRA DDA | Bulk SMS</title>
    <link rel="icon" href="dda.jpg" type="image/jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/responsive-layout.css" rel="stylesheet">
    <link href="assets/dashboard-common.css" rel="stylesheet">
    <style>
        :root { --sidebar-width:260px; --tra-navy:#0b1e3b; --tra-blue-gradient:linear-gradient(135deg,#0e2245 0%,#1c3d7a 100%); --accent-gold:#c5a059; --light-bg:#f4f7fa; }
        body { margin:0; font-family:'Open Sans',sans-serif; background:var(--light-bg); }
        .sidebar-wrapper { width:var(--sidebar-width); background:var(--tra-navy); height:100vh; position:fixed; left:0; top:0; z-index:1000; color:#fff; overflow-y:auto; scrollbar-width:none; }
        .sidebar-wrapper::-webkit-scrollbar { display:none; }
        .sidebar-wrapper { display:flex; flex-direction:column; }
        .sidebar-nav { flex:1; }
        .sidebar-logo-section { padding:25px; text-align:center; background:rgba(0,0,0,.2); }
        .sidebar-logo-section img { width:90px; height:90px; border-radius:12px; background:#fff; padding:5px; object-fit:contain; }
        .card-sidebar-info { background:rgba(255,255,255,.05); margin:15px; border-radius:8px; padding:15px; border-left:3px solid var(--accent-gold); }
        .sidebar-link { padding:12px 25px; color:#bdc3c7; display:flex; align-items:center; text-decoration:none!important; transition:.3s; font-size:14px; }
        .sidebar-link:hover,.sidebar-link.active { background:rgba(255,255,255,.1); color:#fff; }
        .sidebar-link i { margin-right:15px; width:20px; text-align:center; }
        .main-content { margin-left:var(--sidebar-width); min-height:100vh; }
        .tt-header { background:#fff; min-height:65px; display:flex; align-items:center; justify-content:space-between; padding:0 30px; box-shadow:0 1px 3px rgba(0,0,0,.1); position:sticky; top:0; z-index:999; }
        .tt-page-header { background:var(--tra-blue-gradient); color:#fff; text-align:center; padding:20px 16px 16px; }
        .tt-page-header .sms-logo { width:min(260px, 70%); max-height:64px; object-fit:contain; }
        .card-custom { background:#fff; margin:20px 30px; border-radius:10px; border:none; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        .card-header-accent { border-top:3px solid var(--accent-gold); font-weight:700; background:#fff; padding:14px 20px; }
        .sms-note { font-size:12px; color:#64748b; }
        .response-box { background:#0b1220; color:#e2e8f0; border-radius:8px; padding:12px; max-height:220px; overflow:auto; font-size:12px; white-space:pre-wrap; }
        .mobile-menu-btn{display:none}.sidebar-overlay{display:none}
        @media (max-width:991px){ .mobile-menu-btn{display:flex;position:fixed;top:15px;left:15px;z-index:1001;width:44px;height:44px;border-radius:8px;background:var(--tra-navy);color:#fff;border:none;align-items:center;justify-content:center}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999}
        .sidebar-wrapper{transform:translateX(-100%);transition:transform .3s ease;width:260px}.sidebar-wrapper.sidebar-open{transform:translateX(0)}
        .main-content{margin-left:0}.tt-header{padding:10px 15px 10px 60px}.card-custom{margin:15px} }
    </style>
</head>
<body>
<button type="button" class="mobile-menu-btn" id="mobile-menu-btn"><i class="fas fa-bars"></i></button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<aside class="sidebar-wrapper">
    <div class="sidebar-logo-section">
        <img src="<?php echo htmlspecialchars(tra_sidebar_logo_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="TRA LOGO" onerror="this.src='https://via.placeholder.com/90?text=TRA'">
        <div class="mt-2 font-weight-bold small text-uppercase">Tanzania Revenue Authority</div>
    </div>
    <div class="card-sidebar-info">
        <div class="small text-muted">Signed in as:</div>
        <div class="font-weight-bold"><?php echo htmlspecialchars($user_name); ?></div>
        <div class="small text-muted mt-1">Institutional Admin</div>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard_home.php" class="sidebar-link"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="analytics.php" class="sidebar-link"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="dashboard.php" class="sidebar-link"><i class="fab fa-tiktok"></i> TikTok</a>
        <a href="airbnb_engine.php" class="sidebar-link"><i class="fab fa-airbnb"></i> Airbnb Engine</a>
        <a href="airbnb_realtime.php" class="sidebar-link"><i class="fas fa-bolt"></i> Airbnb Real Time</a>
        <a href="airbnb2_engine.php" class="sidebar-link"><i class="fab fa-airbnb"></i> Airbnb 2</a>
        <a href="upgraded_airbnb.php" class="sidebar-link"><i class="fas fa-star"></i> Upgraded Airbnb</a>
        <a href="booking_engine.php" class="sidebar-link"><i class="fas fa-hotel"></i> Booking Engine</a>
        <a href="download.php" class="sidebar-link"><i class="fas fa-file-export"></i> Export Records</a>
        <a href="settings_dashboard.php" class="sidebar-link"><i class="fas fa-user-cog"></i> Settings</a>
        <a href="contact_developer.php" class="sidebar-link"><i class="fas fa-headset"></i> Contact Developer</a>
        <a href="logout.php" class="sidebar-link text-danger mt-3"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
    <header class="tt-header">
        <div class="font-weight-bold text-muted">INSTITUTIONAL BULK SMS CENTER</div>
        <div class="small text-muted">Secure messaging workflow</div>
    </header>

    <section class="tt-page-header">
        <img src="uploads/sms.png" alt="Bulk SMS" class="sms-logo" onerror="this.style.display='none'">
    </section>

    <div class="card card-custom">
        <div class="card-header-accent"><i class="fas fa-paper-plane mr-2"></i>Send Bulk SMS</div>
        <div class="card-body">
            <?php if ($statusType): ?>
                <div class="alert alert-<?php echo htmlspecialchars($statusType); ?> mb-3"><?php echo htmlspecialchars($statusText); ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <div class="form-group">
                    <label class="font-weight-bold">Phone Numbers</label>
                    <textarea name="phone_numbers" class="form-control" rows="4" placeholder="+2557xxxxxxx, +2556xxxxxxx&#10;Unaweza weka kwa comma/new line"><?php echo htmlspecialchars((string)($_POST['phone_numbers'] ?? '')); ?></textarea>
                    <div class="sms-note mt-1">Tumia namba za Tanzania tu (2557XXXXXXXX / 07XXXXXXXX). Tenganisha kwa comma, nafasi au new line.</div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Message</label>
                    <textarea name="message" class="form-control" rows="5" maxlength="1000" placeholder="Type your message here..."><?php echo htmlspecialchars((string)($_POST['message'] ?? '')); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Sender ID (optional)</label>
                    <input type="text" name="sender_id" class="form-control" placeholder="e.g. TRA" value="<?php echo htmlspecialchars((string)($_POST['sender_id'] ?? 'TRA')); ?>">
                </div>
                <button class="btn btn-primary px-4" type="submit"><i class="fas fa-paper-plane mr-1"></i> Send Bulk SMS</button>
            </form>

            <?php if ($apiResponsePreview !== ''): ?>
                <hr>
                <div class="font-weight-bold mb-2">API Response</div>
                <div class="response-box"><?php echo htmlspecialchars($apiResponsePreview); ?></div>
            <?php endif; ?>
            <?php if ($apiDebugPreview !== ''): ?>
                <hr>
                <div class="font-weight-bold mb-2">API Debug</div>
                <div class="response-box"><?php echo htmlspecialchars($apiDebugPreview); ?></div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
(function() {
    const btn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar-wrapper');
    const overlay = document.getElementById('sidebar-overlay');
    if (!btn || !sidebar || !overlay) return;
    function open(){ sidebar.classList.add('sidebar-open'); overlay.style.display='block'; }
    function close(){ sidebar.classList.remove('sidebar-open'); overlay.style.display='none'; }
    btn.addEventListener('click', () => sidebar.classList.contains('sidebar-open') ? close() : open());
    overlay.addEventListener('click', close);
})();
</script>
<script src="js/code_protection.js"></script>
</body>
</html>
