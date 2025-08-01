<?php
/**
 * auth.php
 *
 * Central JWT authentication utility and middleware for the Home Management System backend.
 *
 * - Provides functions for generating, validating, and enforcing JWT-based authentication.
 * - Used by all API endpoints requiring user authentication.
 *
 * SECURITY NOTE: Replace 'YOUR_SECRET_KEY_HERE' with a secure, environment-based secret in production.
 *
 * Used by: All backend PHP APIs that require authentication and session management.
 */

// Combined JWT utility and authentication middleware
require_once __DIR__ . '/../vendor/autoload.php'; // Ensure firebase/php-jwt is loaded
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

define('JWT_SECRET', 'YOUR_SECRET_KEY_HERE'); // Set your secret key
if (!defined('TOKEN_EXPIRATION')) {
    define('TOKEN_EXPIRATION', 3600); // 1 hour
}

/**
 * Generates a JWT for the provided payload.
 *
 * @param array $payload User/session data to encode
 * @return string Encoded JWT token
 *
 * The token includes issued-at (iat) and expiration (exp) claims.
 */
function generate_jwt($payload) {
    $issuedAt = time();
    $expirationTime = $issuedAt + TOKEN_EXPIRATION;
    $payload['iat'] = $issuedAt;
    $payload['exp'] = $expirationTime;
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

/**
 * Validates and decodes a JWT.
 *
 * @param string $jwt The JWT string to validate
 * @return array|false Decoded payload as array if valid, false if invalid/expired
 */
function validate_jwt($jwt) {
    try {
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Enforces authentication by requiring a valid JWT cookie.
 *
 * @return array Decoded JWT payload (user/session info)
 * @exits with 401 error if token is missing or invalid
 */
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
