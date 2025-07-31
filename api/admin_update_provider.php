<?php
require_once __DIR__ . '/../class/Provider.php';
require_once __DIR__ . '/auth.php'; // ✅ JWT middleware

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ✅ Authenticate user via JWT in cookies only (do not use Authorization header)
$user = require_auth();

// ✅ Optional: check for admin-only access
if ($user['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

// ✅ Process the input
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing data.']);
    exit;
}

$provider = new Provider();
$result = $provider->updateProfile($data);

if ($result && isset($result['status']) && $result['status'] === 'success') {
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => isset($result['message']) ? $result['message'] : 'Failed to update provider.'
    ]);
}
