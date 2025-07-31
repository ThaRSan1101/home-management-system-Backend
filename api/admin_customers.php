<?php
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require_once __DIR__ . '/../class/Admin.php';
require_once __DIR__ . '/auth.php'; // ✅ Require JWT validation

$user = require_auth(); // ✅ Validate JWT and get user data

// ✅ Optional: Restrict to admin only
if ($user['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied: Admins only.']);
    exit;
}

try {
    $admin = new Admin();
    $result = $admin->getCustomerDetails();
    if ($result['status'] === 'success') {
        echo json_encode([
            'status' => 'success',
            'data' => $result['data']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $result['message'] ?? 'Failed to fetch customers.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
