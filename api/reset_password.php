<?php
require_once __DIR__ . '/../class/User.php';

header("Access-Control-Allow-Origin: http://localhost:5173"); // restrict to your frontend origin
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request for CORS (if needed)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? strtolower(trim($data['email'])) : '';
$otp = isset($data['otp']) ? trim($data['otp']) : '';
$newPassword = isset($data['newPassword']) ? trim($data['newPassword']) : '';

if (!$email || !$otp || !$newPassword) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

$userObj = new User();
$result = $userObj->resetPassword($email, $otp, $newPassword);
echo json_encode($result);
