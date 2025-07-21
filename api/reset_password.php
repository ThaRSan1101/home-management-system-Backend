<?php
require_once 'db.php';
require_once __DIR__ . '/../class/User.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? strtolower(trim($data['email'])) : '';
$otp = isset($data['otp']) ? trim($data['otp']) : '';
$newPassword = isset($data['newPassword']) ? trim($data['newPassword']) : '';
if (!$email || !$otp || !$newPassword) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}
$conn = getDbConnection();
$userObj = new User($conn);
$result = $userObj->resetPassword($email, $otp, $newPassword);
echo json_encode($result);
