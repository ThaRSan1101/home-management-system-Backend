<?php
require_once __DIR__ . '/auth_middleware.php';  // JWT validation

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

// Validate the JWT token from cookie and get user data
$user = require_auth();  // If invalid, this will send 401 and exit

require_once __DIR__ . '/../class/Admin.php';

$admin = new Admin();
$providers = $admin->getAllProviders();

echo json_encode(['status' => 'success', 'providers' => $providers]);
