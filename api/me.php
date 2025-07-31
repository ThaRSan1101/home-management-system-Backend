<?php
require_once __DIR__ . '/auth.php';

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$payload = require_auth();

require_once __DIR__ . '/../class/User.php';
$userObj = new User();
$user = $userObj->getUserById($payload['user_id']);

if (!$user) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

// Return only safe fields
$response = [
    'status' => 'success',
    'user_id' => $user['user_id'],
    'email' => $user['email'],
    'user_type' => $user['user_type'],
    'name' => $user['name']
];

echo json_encode($response);
