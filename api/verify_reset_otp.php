<?php
/**
 * verify_reset_otp.php
 *
 * API endpoint to verify the OTP code for password reset.
 *
 * Flow:
 * - Accepts POSTed JSON with 'email' and 'code' (OTP)
 * - Validates input parameters
 * - Calls User::verifyResetOtp() to check OTP validity
 * - Returns JSON response with status and message
 *
 * CORS headers are set to allow frontend access from http://localhost:5173.
 *
 * Used by: Frontend password reset flow after user enters OTP code.
 */

require_once __DIR__ . '/../class/User.php';

// Set CORS and content headers for frontend integration
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Parse JSON input
$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? strtolower(trim($data['email'])) : '';
$otp = isset($data['code']) ? trim($data['code']) : '';

// Validate required fields
if (!$email || !$otp) {
    echo json_encode(['status' => 'error', 'message' => 'Email and OTP are required.']);
    exit;
}

// Attempt OTP verification using User class
$userObj = new User();
$result = $userObj->verifyResetOtp($email, $otp);

// Output result as JSON
echo json_encode($result);
