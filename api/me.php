<?php
/**
 * me.php
 *
 * API endpoint to fetch information about the currently authenticated user.
 *
 * Flow:
 * - Requires GET request and JWT authentication
 * - Fetches user details from the database using the user_id from the JWT
 * - Returns JSON response with safe user fields (no sensitive info)
 *
 * CORS headers included for frontend integration with http://localhost:5173.
 *
 * Used by: Frontend to display current user's profile or session info.
 */

require_once __DIR__ . '/auth.php';

// Set CORS and content headers for frontend integration
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// Authenticate user and get JWT payload
$payload = require_auth();

require_once __DIR__ . '/../class/User.php';
$userObj = new User();
$user = $userObj->getUserById($payload['user_id']);

if (!$user) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

// Return only safe fields (no sensitive info)
$response = [
    'status' => 'success',
    'user_id' => $user['user_id'],
    'email' => $user['email'],
    'user_type' => $user['user_type'],
    'name' => $user['name']
];

echo json_encode($response);
