<?php
/**
 * verify_otp.php
 *
 * API endpoint to verify the OTP code for user registration or other OTP-based flows.
 *
 * Flow:
 * - Accepts POSTed JSON with OTP and user info
 * - Handles CORS and preflight OPTIONS requests
 * - Calls User::verifyOtp() to check OTP validity
 * - Returns JSON response with status and message
 *
 * CORS headers are set to allow frontend access from http://localhost:5173.
 *
 * Used by: Frontend registration and any OTP verification step.
 */

require_once __DIR__ . '/../class/User.php';

// Set CORS and content headers for frontend integration
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request for CORS (required for browser security)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse JSON input
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received.']);
    exit;
}

// Attempt OTP verification using User class
$userObj = new User();
$result = $userObj->verifyOtp($data);

// Output result as JSON
echo json_encode($result);
?>
