<?php
/**
 * admin_update_provider.php
 *
 * API endpoint for admin users to update a provider's profile information.
 *
 * Flow:
 * - Accepts POSTed JSON with updated provider fields (must include user_id)
 * - Enforces JWT authentication via cookies
 * - Allows only admin users (user_type === 'admin')
 * - Uses Provider class to update provider profile in DB
 * - Returns JSON response with status and message
 *
 * CORS headers and preflight OPTIONS handling included for frontend integration with http://localhost:5173.
 *
 * Used by: Admin panel frontend to manage provider accounts.
 */

require_once __DIR__ . '/../class/Provider.php';
require_once __DIR__ . '/auth.php'; // JWT middleware

// Set CORS and content headers
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Authenticate user via JWT in cookies only
$user = require_auth();

// Allow only admin users to access this endpoint
if ($user['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

// Parse and validate input
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing data.']);
    exit;
}

// Update provider profile using Provider class
$provider = new Provider();
$result = $provider->updateProfile($data);

// Output result as JSON
if ($result && isset($result['status']) && $result['status'] === 'success') {
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => isset($result['message']) ? $result['message'] : 'Failed to update provider.'
    ]);
}
