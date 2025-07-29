<?php
require_once __DIR__ . '/../class/Provider.php';
require_once __DIR__ . '/../class/User.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
// Accepts: { user_id, otp }
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['user_id']) || !isset($data['otp'])) {
    echo json_encode(['status' => 'error', 'message' => 'User ID and OTP are required.']);
    exit;
}
$user = new User();
$result = $user->verifyProfileUpdateOtp($data['user_id'], $data['otp'], 'updateProviderProfile');
echo json_encode($result); 