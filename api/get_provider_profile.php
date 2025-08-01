<?php
/**
 * get_provider_profile.php
 *
 * API endpoint to fetch the current authenticated provider's profile information.
 *
 * Flow:
 * - Requires JWT authentication (provider)
 * - Accepts GET request
 * - Fetches provider's profile from the database
 * - Maps DB fields to frontend-expected keys
 * - Returns JSON response with status and data
 *
 * CORS headers and preflight OPTIONS handling included for frontend integration with http://localhost:5173.
 *
 * Used by: Provider dashboard/profile page.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../class/User.php';

// Set CORS and content headers for frontend integration
$allowed_origins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost',
    'http://127.0.0.1'
];
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

// Fetch provider profile from DB
$userObj = new User();
$profile = $userObj->getUserById($user['user_id']);
if (!$profile || $profile['user_type'] !== 'provider') {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Provider not found']);
    exit;
}

// Map DB fields to frontend expected keys
$data = [
    'fullName' => $profile['name'] ?? '',
    'address' => $profile['address'] ?? '',
    'phone' => $profile['phone_number'] ?? '',
    'email' => $profile['email'] ?? '',
    'joined' => $profile['registered_date'] ?? '',
    'nic' => $profile['NIC'] ?? ''
];

// Output provider profile as JSON
echo json_encode([
    'status' => 'success',
    'data' => $data
]);
