<?php
require_once __DIR__ . '/../class/Provider.php';
require_once __DIR__ . '/auth_middleware.php'; // ✅ JWT middleware

if (isset($_SERVER['HTTP_ORIGIN']) && preg_match('/^http:\/\/localhost(:[0-9]+)?$/', $_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
} else {
    header('Access-Control-Allow-Origin: http://localhost:5173'); // fallback for dev
}
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
