<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Clear cookie by setting expiration in the past with secure, httponly, samesite flags matching login.php
setcookie('token', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'domain' => '',      // keep same domain as login.php or leave empty
    'secure' => false,   // set true if your site is HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

echo json_encode(['status' => 'success', 'message' => 'Logged out']);
