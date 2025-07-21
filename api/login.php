<?php
require_once __DIR__ . '/../class/User.php';
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

$userObj = new User();
$result = $userObj->login($email, $password);

if ($result['status'] === 'success') {
    $jwt = $result['jwt'];
    $cookieOptions = [
        'expires' => time() + (60 * 60 * 24),
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    setcookie('access_token', $jwt, $cookieOptions);
}
echo json_encode($result);
?> 