<?php
require 'db.php';
require_once __DIR__ . '/php-jwt/php-jwt-main/src/JWT.php';
require_once __DIR__ . '/php-jwt/php-jwt-main/src/Key.php';
use Firebase\JWT\JWT;

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

$stmt = $conn->prepare("SELECT user_id, name, email, password, user_type, disable_status FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    exit;
}

if ($user['disable_status']) {
    echo json_encode(['status' => 'error', 'message' => 'Your account has been disabled. Please contact support.']);
    exit;
}

$key = 'f8d3c2e1b4a7d6e5f9c8b7a6e3d2c1f0a9b8c7d6e5f4a3b2c1d0e9f8a7b6c5d4';
$payload = [
    'user_id' => $user['user_id'],
    'email' => $user['email'],
    'user_type' => $user['user_type'],
    'exp' => time() + (60 * 60 * 24)
];
$jwt = JWT::encode($payload, $key, 'HS256');

$cookieOptions = [
    'expires' => time() + (60 * 60 * 24),
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
];
setcookie('access_token', $jwt, $cookieOptions);

echo json_encode([
    'status' => 'success',
    'message' => 'Login successful.',
    'user_type' => $user['user_type'],
    'name' => $user['name'],
    'email' => $user['email'],
    'user_id' => $user['user_id']
]);
?> 