<?php
/**
 * admin_customers.php
 *
 * API endpoint for retrieving all customer details for admin panel use.
 *
 * Flow:
 * - Requires JWT authentication (admin only)
 * - Calls Admin::getCustomerDetails() to fetch all customers
 * - Returns a JSON response with the data or error
 *
 * Used by the admin dashboard to display/manage customers securely.
 */

// Set CORS and content headers for frontend integration
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../class/Admin.php';
require_once __DIR__ . '/auth.php'; // Require JWT validation

// Require valid JWT and extract user info
$user = require_auth();

// Restrict access to admin users only
if ($user['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied: Admins only.']);
    exit;
}

try {
    // Instantiate Admin and fetch customer details
    $admin = new Admin();
    $result = $admin->getCustomerDetails();
    if ($result['status'] === 'success') {
        echo json_encode([
            'status' => 'success',
            'data' => $result['data']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $result['message'] ?? 'Failed to fetch customers.'
        ]);
    }
} catch (Exception $e) {
    // Handle unexpected errors gracefully
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
