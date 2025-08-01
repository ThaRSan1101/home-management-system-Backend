<?php
/**
 * admin_update_customer.php
 *
 * API endpoint for updating a customer's profile by an admin.
 *
 * Flow:
 * - Requires JWT authentication (admin only)
 * - Accepts JSON body with customer data (must include user_id)
 * - Calls Customer::updateProfile() to update the customer in the database
 * - Returns a JSON response with the result
 *
 * Used by the admin panel to edit customer details securely.
 */
require_once __DIR__ . '/../class/Customer.php';
require_once __DIR__ . '/auth.php'; // Require authentication middleware

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

// Require valid JWT and extract user info
$user = require_auth();

// Restrict access to admin users only
if ($user['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Admins only.']);
    exit;
}

// Parse and validate JSON request body
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing data.']);
    exit;
}

// Instantiate Customer and update profile using provided data
$customer = new Customer();
$result = $customer->updateProfile($data);

// Return result as JSON
echo json_encode($result);
