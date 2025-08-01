<?php
/**
 * switch_user.php
 *
 * API endpoint for admin to switch into another user account (impersonation).
 *
 * Flow:
 * - Requires JWT authentication as admin
 * - Accepts POSTed JSON with 'user_id' and 'user_type' of the target account
 * - Validates and fetches target user
 * - Issues new JWT and sets cookie for switched account
 * - Returns JSON response with status
 *
 * CORS headers and preflight OPTIONS handling included for frontend integration with http://localhost:5173.
 *
 * SECURITY NOTE: Only admin users can use this endpoint. Impersonation should be logged and monitored in production.
 */

// --- CORS HEADERS (allow localhost dev) ---
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../class/User.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Authenticate admin via JWT
$admin = require_auth();
if (!isset($admin['user_type']) || $admin['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Only admin can switch accounts.']);
    exit;
}

// Parse JSON input for target user
$data = json_decode(file_get_contents('php://input'), true);
$targetUserId = $data['user_id'] ?? null;
$targetUserType = $data['user_type'] ?? null;

if (!$targetUserId || !$targetUserType) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing user_id or user_type.']);
    exit;
}

// Fetch target user and validate type
$userObj = new User();
$targetUser = $userObj->getUserById($targetUserId);

if (!$targetUser || $targetUser['user_type'] !== $targetUserType) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'User not found or type mismatch.']);
    exit;
}

// Issue new JWT for target user and set cookie
$payload = [
    'user_id' => $targetUser['user_id'],
    'email' => $targetUser['email'],
    'user_type' => $targetUser['user_type'],
    'name' => $targetUser['name'],
    'iat' => time(),
    'exp' => time() + TOKEN_EXPIRATION
];
$token = generate_jwt($payload);
setcookie('token', $token, [
    'expires' => time() + TOKEN_EXPIRATION,
    'path' => '/',
    'secure' => false, // Set to true if using HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Output success response
echo json_encode(['status' => 'success']);
