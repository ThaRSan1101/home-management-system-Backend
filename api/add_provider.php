<?php
/**
 * add_provider.php
 *
 * API endpoint for admin users to add a new provider account to the system.
 *
 * Flow:
 * - Accepts POSTed JSON with provider registration fields
 * - Enforces JWT authentication via cookies
 * - Allows only admin users (user_type === 'admin')
 * - Uses Admin class to create provider in DB
 * - Optionally sends notification email via PHPMailer
 * - Returns JSON response with status and message
 *
 * CORS headers and preflight OPTIONS handling included for frontend integration with http://localhost:5173.
 *
 * Used by: Admin panel frontend to add new providers.
 */

require_once __DIR__ . '/../api/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../api/PHPMailer/SMTP.php';
require_once __DIR__ . '/../api/PHPMailer/Exception.php';
require_once __DIR__ . '/../class/Admin.php';
require_once __DIR__ . '/auth.php'; // Add auth middleware

// Set CORS and credentials headers
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Content-Type: application/json');

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Authenticate user via JWT cookie
$user = require_auth();
if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated (JWT missing or invalid).']);
    exit;
}

// Allow only admin users
if (!isset($user['user_type']) || $user['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Only admin can add providers. User type: ' . ($user['user_type'] ?? 'unknown')]);
    exit;
}

// Parse and validate input
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received. Raw input: ' . file_get_contents('php://input')]);
    exit;
}

// Add provider using Admin class
$admin = new Admin();
$result = $admin->addProvider($data);

// Output result as JSON
if (!isset($result['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unknown error adding provider.', 'debug' => $result]);
    exit;
}

if ($result['status'] !== 'success') {
    echo json_encode(['status' => 'error', 'message' => $result['message'] ?? 'Add provider failed.', 'debug' => $result]);
    exit;
}

echo json_encode($result);
