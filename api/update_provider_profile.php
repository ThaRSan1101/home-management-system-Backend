<?php
/**
 * update_provider_profile.php
 *
 * API endpoint to verify OTP and authorize provider profile updates.
 *
 * Flow:
 * - Requires JWT authentication (provider or admin roles)
 * - Accepts POSTed JSON with 'otp' (verification code)
 * - Verifies OTP for profile update using User class
 * - Returns JSON response with status and message
 *
 * CORS headers are set for frontend integration with http://localhost:5173.
 *
 * Used by: Provider and admin flows for updating provider profiles (after OTP verification)
 */

require_once __DIR__ . '/../class/Provider.php';
require_once __DIR__ . '/../class/User.php';
require_once __DIR__ . '/auth.php';

// Set CORS and content headers for frontend integration
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Authenticate user via JWT (must be provider or admin)
$user = require_auth();  // This validates the token and returns user data

// Only allow providers or admins to update provider profile
if ($user['user_type'] !== 'provider' && $user['user_type'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Parse JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Use only the user_id from the JWT
$userId = $user['user_id'];

// OTP is required for verification
if (!$data || !isset($data['otp'])) {
    echo json_encode(['status' => 'error', 'message' => 'OTP is required.']);
    exit;
}

// Verify OTP for provider profile update
$userObj = new User();
$result = $userObj->verifyProfileUpdateOtp($userId, $data['otp'], 'updateProviderProfile');

// Output result as JSON
echo json_encode($result);
