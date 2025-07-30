<?php
require_once __DIR__ . '/../api/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../api/PHPMailer/SMTP.php';
require_once __DIR__ . '/../api/PHPMailer/Exception.php';
require_once __DIR__ . '/../class/Admin.php';
require_once __DIR__ . '/auth_middleware.php'; // Add auth middleware
// require_once __DIR__ . '/_headers.php';         // Centralized headers

// --- CORS and credentials headers (match other endpoints) ---
if (isset($_SERVER['HTTP_ORIGIN']) && preg_match('/^http:\/\/localhost(:[0-9]+)?$/', $_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$user = require_auth(); // Validate JWT cookie
if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated (JWT missing or invalid).']);
    exit;
}

// Only allow admin
if (!isset($user['user_type']) || $user['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Only admin can add providers. User type: ' . ($user['user_type'] ?? 'unknown')]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received. Raw input: ' . file_get_contents('php://input')]);
    exit;
}

$admin = new Admin();
$result = $admin->addProvider($data);

if (!isset($result['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unknown error adding provider.', 'debug' => $result]);
    exit;
}

if ($result['status'] !== 'success') {
    echo json_encode(['status' => 'error', 'message' => $result['message'] ?? 'Add provider failed.', 'debug' => $result]);
    exit;
}

echo json_encode($result);
