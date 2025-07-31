<?php
require_once __DIR__ . '/../class/Customer.php';
require_once __DIR__ . '/auth.php'; // ✅ Add auth middleware

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ✅ Require valid token and get user info
$user = require_auth();

// ✅ Admin-only check
if ($user['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Admins only.']);
    exit;
}

// ✅ Validate request body
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing data.']);
    exit;
}

// ✅ Update customer profile
$customer = new Customer();
$result = $customer->updateProfile($data);

echo json_encode($result);


