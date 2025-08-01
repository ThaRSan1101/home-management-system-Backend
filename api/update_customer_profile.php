<?php
/**
 * update_customer_profile.php
 *
 * API endpoint to verify OTP and authorize customer profile updates.
 *
 * Flow:
 * - Requires JWT authentication
 * - Accepts POSTed JSON with 'otp' (verification code)
 * - Verifies OTP for profile update using User class
 * - Returns JSON response with status and message
 *
 * CORS headers are set for frontend integration with http://localhost:5173.
 *
 * Used by: Customer flows for updating profile (after OTP verification)
 */

require_once __DIR__ . '/../class/User.php';
require_once __DIR__ . '/auth.php';

// Set CORS and content headers for frontend integration
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Content-Type: application/json');

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verify JWT and get user info
$user = require_auth();
if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Parse JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Use only the user_id from the JWT
$userId = $user['user_id'] ?? null;

// OTP is required for verification
if (!$data || !isset($data['otp'])) {
    echo json_encode(['status' => 'error', 'message' => 'OTP is required.']);
    exit;
}

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user.']);
    exit;
}

// Verify OTP for customer profile update
$userObj = new User();
$result = $userObj->verifyProfileUpdateOtp($userId, $data['otp'], 'updateCustomerProfile');

// Output result as JSON
echo json_encode($result);
