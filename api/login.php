<?php
/**
 * login.php
 *
 * API endpoint to handle user login and JWT authentication.
 *
 * Flow:
 * - Accepts POSTed JSON with 'email' and 'password'
 * - Calls User::login() to validate credentials
 * - On success, issues JWT, sets cookie, and returns user info
 * - On failure, returns error message
 *
 * CORS headers included for frontend integration with http://localhost:5173.
 *
 * Used by: Frontend login forms.
 */

require_once __DIR__ . '/../class/User.php';
require_once __DIR__ . '/auth.php'; // Central JWT logic

// Set CORS and content headers for frontend integration
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Parse JSON input
$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

// Validate input
if (!$email || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
    exit;
}

// Attempt login using User class
$userObj = new User();
$result = $userObj->login($email, $password);

if ($result['status'] === 'success') {
    // Prepare JWT payload
    $payload = [
        'user_id'   => $result['user_id'],
        'email'     => $result['email'],
        'user_type' => $result['user_type']
    ];
    // Generate JWT and set cookie
    $token = generate_jwt($payload);
    setcookie('token', $token, [
        'expires' => time() + TOKEN_EXPIRATION,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    // Output success response with user info
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'user_type' => $result['user_type'],
        'user_id' => $result['user_id'],
        'user_details' => isset($result['user_details']) ? $result['user_details'] : null
    ]);
} else {
    // Output error response
    echo json_encode($result);
}
