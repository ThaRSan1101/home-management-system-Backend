<?php
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

require_once __DIR__ . '/../vendor/autoload.php';

const SECRET_KEY = 'your_super_secret_key_here'; // Change this to a long random string
const TOKEN_EXPIRATION = 60 * 60 * 24; // 1 day

function generate_jwt($payload) {
    $payload['exp'] = time() + TOKEN_EXPIRATION;
    return JWT::encode($payload, SECRET_KEY, 'HS256');
}

function validate_jwt($token) {
    try {
        return (array) JWT::decode($token, new Key(SECRET_KEY, 'HS256'));
    } catch (Exception $e) {
        return null;
    }
}
