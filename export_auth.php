<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$password = (string)($payload['password'] ?? '');
if (hash_equals('steven2026', $password)) {
    $_SESSION['export_records_unlocked'] = true;
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(403);
echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
