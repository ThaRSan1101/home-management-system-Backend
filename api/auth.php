<?php
// Combined JWT utility and authentication middleware
require_once __DIR__ . '/../vendor/autoload.php'; // Ensure firebase/php-jwt is loaded
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

define('JWT_SECRET', 'YOUR_SECRET_KEY_HERE'); // Set your secret key
if (!defined('TOKEN_EXPIRATION')) {
    define('TOKEN_EXPIRATION', 3600); // 1 hour
}

function generate_jwt($payload) {
    $issuedAt = time();
    $expirationTime = $issuedAt + TOKEN_EXPIRATION;
    $payload['iat'] = $issuedAt;
    $payload['exp'] = $expirationTime;
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

function validate_jwt($jwt) {
    try {
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        return false;
    }
}

function require_auth() {
    if (!isset($_COOKIE['token'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    $payload = validate_jwt($_COOKIE['token']);
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token']);
        exit;
    }
    return $payload;
}
