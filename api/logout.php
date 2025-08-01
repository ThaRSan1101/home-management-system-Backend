<?php
/**
 * logout.php
 *
 * API endpoint to log out the current user by clearing the JWT authentication cookie.
 *
 * Flow:
 * - Sets the 'token' cookie to an expired value, matching the path, domain, and security flags as in login.php
 * - Returns JSON response with logout status
 *
 * CORS headers included for frontend integration with http://localhost:5173.
 *
 * Used by: Frontend logout button/action.
 */

// Set CORS and content headers for frontend integration
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

// Output logout result as JSON
echo json_encode(['status' => 'success', 'message' => 'Logged out']);
