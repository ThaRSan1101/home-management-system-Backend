<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../class/User.php';

// --- CORS HEADERS (allow localhost dev) ---
$allowed_origins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost',
    'http://127.0.0.1'
];
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$user = require_auth();

$userObj = new User();
$profile = $userObj->getUserById($user['user_id']);
if (!$profile || $profile['user_type'] !== 'provider') {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Provider not found']);
    exit;
}

// Map DB fields to frontend expected keys
$data = [
    'fullName' => $profile['name'] ?? '',
    'address' => $profile['address'] ?? '',
    'phone' => $profile['phone_number'] ?? '',
    'email' => $profile['email'] ?? '',
    'joined' => $profile['registered_date'] ?? '',
    'nic' => $profile['NIC'] ?? ''
];
echo json_encode([
    'status' => 'success',
    'data' => $data
]);
