<?php
require_once __DIR__ . '/../class/Provider.php';
require_once __DIR__ . '/../class/User.php';
require_once __DIR__ . '/auth_middleware.php';  // <-- JWT auth middleware

if (isset($_SERVER['HTTP_ORIGIN']) && preg_match('/^http:\/\/localhost(:[0-9]+)?$/', $_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$user = require_auth();  // This validates the token and returns user data

// Only allow providers or admins to update provider profile
if ($user['user_type'] !== 'provider' && $user['user_type'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Use only the user_id from the JWT
$userId = $user['user_id'];

// OTP is required for verification
if (!$data || !isset($data['otp'])) {
    echo json_encode(['status' => 'error', 'message' => 'OTP is required.']);
    exit;
}

$userObj = new User();
$result = $userObj->verifyProfileUpdateOtp($userId, $data['otp'], 'updateProviderProfile');
echo json_encode($result);
