<?php
require_once __DIR__ . '/../class/User.php';

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? strtolower(trim($data['email'])) : '';
$otp = isset($data['code']) ? trim($data['code']) : '';

if (!$email || !$otp) {
    echo json_encode(['status' => 'error', 'message' => 'Email and OTP are required.']);
    exit;
}

$userObj = new User();
$result = $userObj->verifyResetOtp($email, $otp);

echo json_encode($result);
