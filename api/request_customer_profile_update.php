<?php
/**
 * request_customer_profile_update.php
 *
 * API endpoint to request an OTP for customer profile update.
 *
 * Flow:
 * - Requires JWT authentication (customer)
 * - Accepts POSTed JSON with profile update data
 * - Calls User::requestProfileUpdateOtp() to send OTP for update verification
 * - Returns JSON response with status and message
 *
 * CORS headers and preflight OPTIONS handling included for frontend integration with http://localhost:5173.
 *
 * Used by: Customer flows to initiate profile update (triggers OTP email)
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

// Validate JWT token and get user data
$userData = require_auth();
if (!$userData) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Parse JSON input
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No data received.']);
    exit;
}

// Use only the user_id from the JWT for security
$data['user_id'] = $userData['user_id'];

// Request OTP for customer profile update
$user = new User();
$result = $user->requestProfileUpdateOtp($data, 'updateCustomerProfile');

// Output result as JSON
echo json_encode($result);
