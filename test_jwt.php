<?php
require_once __DIR__ . '/php-jwt/php-jwt-main/src/JWT.php';
require_once __DIR__ . '/php-jwt/php-jwt-main/src/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$key = 'f8d3c2e1b4a7d6e5f9c8b7a6e3d2c1f0a9b8c7d6e5f4a3b2c1d0e9f8a7b6c5d4';
$token = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
} else {
    echo "<form>Paste JWT token: <input name='token' style='width:500px'><input type='submit'></form>";
    exit;
}

try {
    $decoded = JWT::decode($token, new Key($key, 'HS256'));
    echo '<pre>';
    print_r($decoded);
    echo '</pre>';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
} 