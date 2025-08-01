<?php
/**
 * get_providers.php
 *
 * API endpoint for retrieving all service providers for admin or dashboard use.
 *
 * Flow:
 * - Requires JWT authentication (any valid user)
 * - Calls Admin::getAllProviders() to fetch providers
 * - Returns a JSON response with the provider data
 *
 * Used by dashboard or admin panel to list all providers securely.
 */

require_once __DIR__ . '/auth.php';  // Require JWT validation

// Set CORS and content headers for frontend integration
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validate the JWT token from cookie and get user data
$user = require_auth();  // If invalid, this will send 401 and exit

require_once __DIR__ . '/../class/Admin.php';

// Instantiate Admin and fetch all providers
$admin = new Admin();
$providers = $admin->getAllProviders();

// Return provider data as JSON
echo json_encode(['status' => 'success', 'providers' => $providers]);
