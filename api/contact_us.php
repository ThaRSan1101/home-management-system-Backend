<?php
/**
 * contact_us.php
 *
 * API endpoint to handle contact form submissions from users and guests.
 *
 * Flow:
 * - Accepts POSTed JSON with name, email, phone_number, subject, message
 * - Validates required fields and email format
 * - Saves message to database using Message class
 * - Returns JSON response with status and message
 *
 * CORS headers and preflight OPTIONS handling included for frontend integration with http://localhost:5173.
 *
 * Auth is optional: both logged-in and guest users may submit messages.
 *
 * Used by: Frontend Contact Us form.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173'); // Restrict to your frontend domain
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/auth.php';
// Optionally get user info if logged in, but do not require auth
// $user = require_auth(); // Allow guests to send messages

require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../class/Message.php';

// Connect to DB and initialize Message class
$db = (new DBConnector())->connect();
$messageObj = new Message($db);

// Parse JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Extract and trim input fields
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone_number'] ?? '');
$subject = trim($data['subject'] ?? '');
$message = trim($data['message'] ?? '');

// Basic validation
if (!$name || !$email || !$subject || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Required fields missing.']);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format.']);
    exit();
}

// Attempt to save message and handle errors
try {
    $saved = $messageObj->saveMessage($name, $email, $phone, $subject, $message);
    if ($saved) {
        echo json_encode(['success' => true, 'message' => 'Message submitted successfully.']);
    } else {
        $errorInfo = $messageObj->getLastPdoError();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save message.', 'pdo_error' => $errorInfo]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error.', 'details' => $e->getMessage()]);
}
