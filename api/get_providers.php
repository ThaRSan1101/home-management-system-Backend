<?php
require_once __DIR__ . '/auth.php';  // JWT validation

header('Access-Control-Allow-Origin: http://localhost:5173');
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
