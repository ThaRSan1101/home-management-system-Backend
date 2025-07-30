<?php
require_once __DIR__ . '/auth_middleware.php'; // JWT middleware that validates token and returns user info
require_once __DIR__ . '/../class/User.php';

// Allow only your frontend origin, and send cookies
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

// Validate and get logged-in user from JWT cookie
$userData = require_auth();  // throws error and exits if invalid or missing

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received.']);
    exit;
}
// Use only the user_id from the JWT
$data['user_id'] = $userData['user_id'];

$user = new User();
$result = $user->requestProfileUpdateOtp($data, 'updateProviderProfile');
echo json_encode($result);
