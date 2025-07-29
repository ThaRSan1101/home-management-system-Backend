<?php
require_once __DIR__ . '/../class/User.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
    exit;
}
$user = new User();
$result = $user->requestProfileUpdateOtp($data, 'updateProviderProfile');
echo json_encode($result); 