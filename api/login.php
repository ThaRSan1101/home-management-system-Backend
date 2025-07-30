<?php
require_once __DIR__ . '/../class/User.php';
require_once __DIR__ . '/jwt_utils.php'; // Include JWT helper functions

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
    exit;
}

$userObj = new User();
$result = $userObj->login($email, $password);

if ($result['status'] === 'success') {
    // Prepare JWT payload
    $payload = [
        'user_id'   => $result['user_id'],
        'email'     => $result['email'],
        'user_type' => $result['user_type'],
        'iat'       => time(),              // Issued at
        'exp'       => time() + 86400       // Expiration time (1 day)
    ];

    // Generate JWT token
    $token = generate_jwt($payload);

    // Set JWT in HttpOnly cookie
    setcookie('token', $token, [
        'expires' => time() + 86400,  // 1 day
        'path' => '/',
        'domain' => '',               // Adjust if needed
        'secure' => false,            // true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // Respond success with user info (token is in cookie)
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'user_type' => $result['user_type'],
        'user_id' => $result['user_id'],
        'user_details' => isset($result['user_details']) ? $result['user_details'] : null
    ]);
} else {
    echo json_encode($result);
}
