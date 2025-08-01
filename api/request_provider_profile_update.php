<?php
/**
 * request_provider_profile_update.php
 *
 * API endpoint to request an OTP for provider profile update.
 *
 * Flow:
 * - Requires JWT authentication (provider or admin)
 * - Accepts POSTed JSON with profile update data
 * - Calls User::requestProfileUpdateOtp() to send OTP for update verification
 * - Returns JSON response with status and message
 *
 * CORS headers and preflight OPTIONS handling included for frontend integration with http://localhost:5173.
 *
 * Used by: Provider/admin flows to initiate profile update (triggers OTP email)
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../class/User.php';

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

// Validate and get logged-in user from JWT cookie
$userData = require_auth();  // throws error and exits if invalid or missing

// Parse JSON input
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received.']);
    exit;
}
// Use only the user_id from the JWT for security
$data['user_id'] = $userData['user_id'];

// Request OTP for provider profile update
$user = new User();
$result = $user->requestProfileUpdateOtp($data, 'updateProviderProfile');

// Output result as JSON
echo json_encode($result);
