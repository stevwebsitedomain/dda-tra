<?php
/**
 * Unauthorized access reporter: on right-click / view source / devtools,
 * logs to file and sends email to developer (email works when server has mail configured).
 */
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = isset($input['action']) ? preg_replace('/[^a-z_]/', '', (string)$input['action']) : 'unknown';
$page = isset($input['page']) ? substr(strip_tags((string)$input['page']), 0, 256) : '';

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ip = trim(explode(',', $ip)[0]);
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$time = date('Y-m-d H:i:s');

$locationText = 'Unknown';
$lat = $lon = null;
$mapLink = '';

if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
    $geo = @file_get_contents('http://ip-api.com/json/' . urlencode($ip) . '?fields=status,country,regionName,city,lat,lon,isp');
    if ($geo) {
        $data = json_decode($geo, true);
        if (!empty($data['lat']) && !empty($data['lon'])) {
            $lat = (float)$data['lat'];
            $lon = (float)$data['lon'];
            $parts = array_filter([$data['city'] ?? '', $data['regionName'] ?? '', $data['country'] ?? '']);
            $locationText = implode(', ', $parts);
            $mapLink = 'https://www.google.com/maps?q=' . $lat . ',' . $lon;
        } elseif (!empty($data['country'])) {
            $parts = array_filter([$data['city'] ?? '', $data['regionName'] ?? '', $data['country'] ?? '']);
            $locationText = implode(', ', $parts) ?: $data['country'];
        }
    }
}

$actionLabels = [
    'right_click' => 'Right-click / Inspect attempted',
    'view_source' => 'View Source (Ctrl+U) attempted',
    'devtools' => 'Developer tools suspected',
    'inspect' => 'Inspect element attempted',
];
$actionLabel = $actionLabels[$action] ?? $action;

// ----- Kumbukumbu kwenye faili (daima inafanya kazi) -----
$logFile = __DIR__ . '/unauthorized_access_log.txt';
$logLine = sprintf(
    "[%s] action=%s | page=%s | IP=%s | location=%s | UA=%s\n",
    $time,
    $action,
    $page,
    $ip,
    $locationText,
    substr(str_replace(["\r", "\n"], ' ', $userAgent), 0, 120)
);
@file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

// ----- Tuma barua pepe (muonekano mzuri + logo TRA) -----
$to = 'stevenabalwambo@gmail.com';
$logoUrl = 'https://www.tanzaniainvest.com/wp-content/uploads/2016/03/tanzania-TRA-tax-revenues-2016-january.gif';
$ipDisplay = $ip;
if ($ip === '::1' || $ip === '127.0.0.1' || $ip === '') {
    $ipDisplay = $ip ? $ip . ' (Local device – localhost)' : 'Not detected';
}
if ($locationText === 'Unknown' && ($ip === '::1' || $ip === '127.0.0.1')) {
    $locationText = 'Local device / localhost';
}

$body = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:0;font-family:\'Segoe UI\',Tahoma,Arial,sans-serif;background:#f1f5f9;">';
$body .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px;">';
$body .= '<tr><td align="center">';
$body .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;">';
$body .= '<tr><td style="background:#fff;padding:28px 24px;text-align:center;border-bottom:1px solid #e2e8f0;">';
$body .= '<img src="' . htmlspecialchars($logoUrl) . '" alt="Tanzania Revenue Authority" width="220" style="display:block;margin:0 auto;max-width:220px;height:auto;">';
$body .= '</td></tr>';
$body .= '<tr><td style="padding:24px 28px;">';
$body .= '<h1 style="margin:0 0 20px;font-size:20px;color:#0b1e3b;font-weight:700;">⚠ Unauthorized access attempt</h1>';
$body .= '<p style="margin:0 0 24px;color:#64748b;font-size:14px;line-height:1.5;">Someone tried to view or inspect your application code. Details below.</p>';
$body .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">';
$body .= '<tr><td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><span style="color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Action</span><br><span style="color:#0b1e3b;font-weight:600;">' . htmlspecialchars($actionLabel) . '</span></td></tr>';
$body .= '<tr><td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><span style="color:#64748b;font-size:12px;text-transform:uppercase;">Page</span><br><span style="color:#0b1e3b;word-break:break-all;">' . htmlspecialchars($page) . '</span></td></tr>';
$body .= '<tr><td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><span style="color:#64748b;font-size:12px;text-transform:uppercase;">Time</span><br><span style="color:#0b1e3b;">' . htmlspecialchars($time) . '</span></td></tr>';
$body .= '<tr><td style="padding:18px 20px;border-bottom:1px solid #e2e8f0;background:#eff6ff;"><span style="color:#1e40af;font-size:12px;text-transform:uppercase;letter-spacing:.5px;font-weight:700;">Device IP address</span><br><span style="color:#0b1e3b;font-size:16px;font-weight:700;font-family:Consolas,monospace;letter-spacing:.5px;">' . htmlspecialchars($ipDisplay) . '</span>' . ($ip ? '<br><span style="color:#64748b;font-size:11px;">Raw: ' . htmlspecialchars($ip) . '</span>' : '') . '</td></tr>';
$body .= '<tr><td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><span style="color:#64748b;font-size:12px;text-transform:uppercase;">Location</span><br><span style="color:#0b1e3b;">' . htmlspecialchars($locationText) . '</span></td></tr>';
if ($mapLink) {
    $body .= '<tr><td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><a href="' . htmlspecialchars($mapLink) . '" style="color:#1c3d7a;font-weight:600;">View on map</a></td></tr>';
}
$body .= '<tr><td style="padding:16px 20px;"><span style="color:#64748b;font-size:12px;text-transform:uppercase;">User-Agent</span><br><span style="color:#475569;font-size:13px;word-break:break-all;">' . htmlspecialchars($userAgent) . '</span></td></tr>';
$body .= '</table>';
$body .= '<p style="margin:24px 0 0;color:#94a3b8;font-size:12px;">This is an automated alert from your TRA DDA application.</p>';
$body .= '</td></tr></table></td></tr></table></body></html>';

$subject = 'TRA App: Unauthorized code access attempt - ' . $actionLabel;
$subject = trim(str_replace(["\r", "\n"], ' ', $subject));
$fromEmail = 'stevenabalwambo@gmail.com';
$fromName = 'TRA Alert';

$sent = false;

// Load .env for SMTP (optional)
$envFile = __DIR__ . '/.env';
$env = [];
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $p = strpos($line, '=');
        if ($p !== false) {
            $k = trim(substr($line, 0, $p));
            $v = trim(substr($line, $p + 1));
            if (preg_match('/^["\'].*["\']$/', $v)) $v = substr($v, 1, -1);
            $env[$k] = $v;
        }
    }
}

$smtpHost = trim($env['MAIL_SMTP_HOST'] ?? '');
$smtpPort = (int)($env['MAIL_SMTP_PORT'] ?? 587);
$smtpUser = trim($env['MAIL_SMTP_USER'] ?? '');
$smtpPass = trim($env['MAIL_SMTP_PASS'] ?? '');

if ($smtpHost !== '' && $smtpUser !== '' && $smtpPass !== '') {
    $sent = send_mail_smtp($smtpHost, $smtpPort, $smtpUser, $smtpPass, $fromEmail, $fromName, $to, $subject, $body);
}
if (!$sent) {
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: $fromName <$fromEmail>\r\n";
    $sent = @mail($to, $subject, $body, $headers);
}
@file_put_contents(__DIR__ . '/unauthorized_access_log.txt', sprintf("[%s] email_sent=%s\n", date('Y-m-d H:i:s'), $sent ? 'yes' : 'no'), FILE_APPEND | LOCK_EX);

echo json_encode(['ok' => true, 'sent' => $sent]);

/**
 * Send email via SMTP. Gmail: use App Password. Port 465 = SSL, 587 = STARTTLS.
 */
function send_mail_smtp($host, $port, $user, $pass, $fromEmail, $fromName, $to, $subject, $bodyHtml) {
    $logFile = __DIR__ . '/mail_debug.log';
    $log = function($step, $ok) use ($logFile) {
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " $step=" . ($ok ? 'ok' : 'FAIL') . "\n", FILE_APPEND | LOCK_EX);
    };
    $errno = 0;
    $errstr = '';
    $ssl = ($port === 465);
    $addr = ($ssl ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $sock = @stream_socket_client($addr, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$sock) {
        $log('connect', false);
        return false;
    }
    $log('connect', true);
    stream_set_timeout($sock, 15);

    $smtp_read = function($sock) {
        $line = '';
        while (($ch = @fgets($sock)) !== false) {
            $line .= $ch;
            if (strlen($ch) >= 2 && $ch[strlen($ch) - 2] === "\r") break;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        return $line;
    };
    $smtp_read_multiline = function($sock) use ($smtp_read) {
        $last = '';
        do {
            $last = $smtp_read($sock);
        } while (strlen($last) >= 4 && $last[3] === '-');
        return $last;
    };
    $smtp_cmd = function($sock, $cmd, $multiline = false) use ($smtp_read, $smtp_read_multiline, $log) {
        if ($cmd !== '') @fwrite($sock, $cmd . "\r\n");
        $line = $multiline ? $smtp_read_multiline($sock) : $smtp_read($sock);
        return (int)substr($line, 0, 3);
    };

    if ($smtp_cmd($sock, '') !== 220) { @fclose($sock); $log('220', false); return false; }
    $ehlo = 'EHLO localhost';
    if ($smtp_cmd($sock, $ehlo, true) !== 250) { @fclose($sock); $log('ehlo1', false); return false; }
    if (!$ssl && $port === 587) {
        if ($smtp_cmd($sock, 'STARTTLS') !== 220) { @fclose($sock); $log('starttls', false); return false; }
        $tls = defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT : STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (!@stream_socket_enable_crypto($sock, true, $tls)) { @fclose($sock); $log('tls', false); return false; }
        if ($smtp_cmd($sock, $ehlo, true) !== 250) { @fclose($sock); $log('ehlo2', false); return false; }
    }
    if ($smtp_cmd($sock, 'AUTH LOGIN') !== 334) { @fclose($sock); $log('auth1', false); return false; }
    if ($smtp_cmd($sock, base64_encode($user)) !== 334) { @fclose($sock); $log('auth2', false); return false; }
    if ($smtp_cmd($sock, base64_encode($pass)) !== 235) { @fclose($sock); $log('auth3', false); return false; }
    $log('auth', true);
    if ($smtp_cmd($sock, 'MAIL FROM:<' . $fromEmail . '>') !== 250) { @fclose($sock); $log('mailfrom', false); return false; }
    if ($smtp_cmd($sock, 'RCPT TO:<' . $to . '>') !== 250) { @fclose($sock); $log('rcpt', false); return false; }
    if ($smtp_cmd($sock, 'DATA') !== 354) { @fclose($sock); $log('data1', false); return false; }
    $bodyEscaped = preg_replace('/^\./m', '..', $bodyHtml);
    $msg = "From: " . $fromName . " <" . $fromEmail . ">\r\nTo: " . $to . "\r\nSubject: " . $subject . "\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n" . $bodyEscaped . "\r\n.";
    @fwrite($sock, $msg . "\r\n");
    @fflush($sock);
    $code = $smtp_cmd($sock, '');
    if ($code !== 250) { @fclose($sock); $log('data2', false); return false; }
    $log('sent', true);
    $smtp_cmd($sock, 'QUIT');
    @fclose($sock);
    return true;
}
