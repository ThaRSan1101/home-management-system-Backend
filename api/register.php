<?php
/**
 * register.php
 *
 * API endpoint to handle new user registration.
 *
 * Flow:
 * - Accepts POSTed JSON with registration data (name, email, password, etc.)
 * - Calls User::register() to validate, create user, and send OTP email
 * - Returns JSON response with status and message
 *
 * CORS headers included for frontend integration with http://localhost:5173.
 *
 * PHPMailer is included for sending OTP emails during registration.
 *
 * Used by: Frontend registration/signup forms.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

// Validate input
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received.']);
    exit;
}

// Attempt registration using User class
$userObj = new User();
$result = $userObj->register($data);

// Output result as JSON
echo json_encode($result);
