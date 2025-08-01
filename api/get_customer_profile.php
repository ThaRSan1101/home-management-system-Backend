<?php
/**
 * get_customer_profile.php
 *
 * API endpoint to fetch a customer's profile information by user_id.
 *
 * Flow:
 * - Requires JWT authentication (any logged-in user)
 * - Accepts GET request with 'user_id' as a query parameter
 * - Fetches customer profile from the database
 * - Maps DB fields to frontend-expected keys
 * - Returns JSON response with status and data
 *
 * CORS headers and preflight OPTIONS handling included for frontend integration with http://localhost:5173.
 *
 * Used by: Admin/provider/customer views of customer profiles.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../class/User.php';

// Set CORS and content headers for frontend integration
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json');

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Authenticate user and get JWT payload
$user = require_auth();

// Validate required query parameter
$userId = $_GET['user_id'] ?? null;
if (!$userId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing user_id']);
    exit;
}

// Fetch customer profile from DB
$userObj = new User();
$profile = $userObj->getUserById($userId);
if (!$profile || $profile['user_type'] !== 'customer') {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Customer not found']);
    exit;
}

// Map DB fields to frontend expected keys
$result = [
    'fullName' => $profile['name'] ?? '',
    'address' => $profile['address'] ?? '',
    'phone' => $profile['phone_number'] ?? '',
    'email' => $profile['email'] ?? '',
    'joined' => $profile['registered_date'] ?? '',
    'nic' => $profile['NIC'] ?? ''
];

// Output customer profile as JSON
echo json_encode($result);
