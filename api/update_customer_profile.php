<?php
require_once __DIR__ . '/../class/User.php';
require_once __DIR__ . '/auth.php';

// CORS headers for your frontend (localhost:5173)
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verify JWT and get user info
$user = require_auth();
if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Use only the user_id from the JWT
$userId = $user['user_id'] ?? null;

if (!$data || !isset($data['otp'])) {
    echo json_encode(['status' => 'error', 'message' => 'OTP is required.']);
    exit;
}

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user.']);
    exit;
}

$userObj = new User();
$result = $userObj->verifyProfileUpdateOtp($userId, $data['otp'], 'updateCustomerProfile');

echo json_encode($result);
