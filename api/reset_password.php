<?php
/**
 * reset_password.php
 *
 * API endpoint to reset a user's password after OTP verification.
 *
 * Flow:
 * - Accepts POSTed JSON with 'email', 'otp', and 'newPassword'
 * - Validates input parameters
 * - Calls User::resetPassword() to update the password if OTP is valid
 * - Returns JSON response with status and message
 *
 * CORS headers and preflight OPTIONS handling included for frontend integration with http://localhost:5173.
 *
 * Used by: Frontend password reset flow after user enters OTP and new password.
 */

require_once __DIR__ . '/../class/User.php';

// Set CORS and content headers for frontend integration
header("Access-Control-Allow-Origin: http://localhost:5173"); // restrict to your frontend origin
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse JSON input
$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? strtolower(trim($data['email'])) : '';
$otp = isset($data['otp']) ? trim($data['otp']) : '';
$newPassword = isset($data['newPassword']) ? trim($data['newPassword']) : '';

// Validate required fields
if (!$email || !$otp || !$newPassword) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

// Attempt password reset using User class
$userObj = new User();
$result = $userObj->resetPassword($email, $otp, $newPassword);

// Output result as JSON
echo json_encode($result);
