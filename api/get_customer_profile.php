<?php
require_once __DIR__ . '/auth_middleware.php';
require_once __DIR__ . '/../class/User.php';

// --- CORS HEADERS (allow localhost dev) ---
$allowed_origins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost',
    'http://127.0.0.1'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost:5173");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$user = require_auth();

$userId = $_GET['user_id'] ?? null;
if (!$userId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing user_id']);
    exit;
}

$userObj = new User();
$profile = $userObj->getUserById($userId);
if (!$profile || $profile['user_type'] !== 'customer') {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Customer not found']);
    exit;
}

// Map DB fields to frontend expected keys
$result = [
    'fullName' => $profile['name'] ?? '',
    'address' => $profile['address'] ?? '',
    'phone' => $profile['phone_number'] ?? '',
    'email' => $profile['email'] ?? '',
    'joined' => $profile['registered_date'] ?? '',
    'nic' => $profile['NIC'] ?? ''
];
echo json_encode($result);
