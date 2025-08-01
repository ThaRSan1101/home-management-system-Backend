<?php
/**
 * admin_messages.php
 *
 * API endpoint for admin to view all Contact Us messages.
 *
 * Features:
 * - Requires JWT authentication (admin only)
 * - Supports GET requests for paginated message retrieval
 * - Uses Message class to interact with the message table
 * - Returns JSON responses for success, errors, and unsupported methods
 */

// Set CORS and content headers for frontend integration
header('Access-Control-Allow-Origin: http://localhost:5173'); // Allow requests from frontend
header('Access-Control-Allow-Credentials: true'); // Allow credentials (cookies)
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Allow required headers
header('Content-Type: application/json'); // Always respond with JSON

// Handle CORS preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Import authentication, DB, and Message class dependencies
require_once __DIR__ . '/auth.php';     // JWT validation helper
require_once __DIR__ . '/../api/db.php'; // DBConnector class
require_once __DIR__ . '/../class/Message.php'; // Message class for message operations

// Validate JWT from cookie and get user info
$user = require_auth();

// Restrict endpoint to admin users only
if ($user['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied: Admins only.']);
    exit;
}

// Connect to the database
$db = (new DBConnector())->connect();
// Instantiate Message class for message operations
$messageObj = new Message($db);

// Handle GET requests for paginated message retrieval
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get pagination parameters from query string, with defaults
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

    // Fetch messages using Message class
    $messages = $messageObj->getAllMessages($page, $limit);
    // Return messages as JSON
    echo json_encode(['status' => 'success', 'data' => $messages]);
    exit();
}

// Respond with 405 if method is not allowed
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
