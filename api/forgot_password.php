<?php
/**
 * forgot_password.php
 *
 * API endpoint to initiate password reset by sending an OTP to the user's email.
 *
 * Flow:
 * - Accepts POSTed JSON with 'email'
 * - Calls User::forgotPassword() to generate OTP and send email
 * - Returns JSON response with status and message
 *
 * CORS headers included for frontend integration with http://localhost:5173.
 *
 * PHPMailer is included for sending OTP emails for password reset.
 *
 * Used by: Frontend "Forgot Password" forms.
 */

require_once __DIR__ . '/../class/User.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

// Set CORS and content headers for frontend integration
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Parse JSON input
$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? strtolower(trim($data['email'])) : '';

// Validate required field
if (!$email) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
    exit;
}

// Attempt to send OTP for password reset using User class
$userObj = new User();
$result = $userObj->forgotPassword($email);

// Output result as JSON
echo json_encode($result);
