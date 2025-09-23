<?php
/**
 * provider_status.php
 *
 * API endpoint for managing provider status (active/inactive) in the Home Management System.
 * Allows getting current status and updating provider availability status.
 *
 * AUTHENTICATION:
 * ===============
 * Currently authentication is included but needs to be properly enforced.
 * TODO: Add proper JWT authentication and role-based authorization.
 *
 * HTTP METHODS SUPPORTED:
 * =======================
 * 
 * GET: Retrieve provider status
 * - Returns current status of a specific provider
 * - Required parameter: provider_id
 * 
 * POST: Update provider status
 * - Changes provider status between 'active' and 'inactive'
 * - Required parameters: provider_id, new_status
 * - Supports both JSON and form-data input
 *
 * PROVIDER STATUS VALUES:
 * ======================
 * - 'active': Provider is available to accept new bookings
 * - 'inactive': Provider is not accepting new bookings
 *
 * REQUEST EXAMPLES:
 * ================
 * 
 * GET Request:
 * GET /api/provider_status.php?provider_id=123
 * 
 * POST Request (JSON):
 * POST /api/provider_status.php
 * Content-Type: application/json
 * {
 *   "provider_id": 123,
 *   "new_status": "active"
 * }
 *
 * RESPONSE FORMAT:
 * ===============
 * Success Response (GET):
 * {
 *   "status": "success",
 *   "provider_status": "active|inactive"
 * }
 *
 * Success Response (POST):
 * {
 *   "status": "success",
 *   "message": "Status updated successfully."
 * }
 *
 * Error Response:
 * {
 *   "status": "error",
 *   "message": "Error description"
 * }
 *
 * CORS CONFIGURATION:
 * ==================
 * Currently allows all origins (*) - should be restricted in production.
 * TODO: Restrict CORS to specific frontend domains for security.
 *
 * DEPENDENCIES:
 * =============
 * - Provider.php: Business logic for status management
 * - auth.php: Authentication utilities (needs proper implementation)
 */

// provider_status.php
/**
 * CORS HEADERS AND CONFIGURATION
 * ==============================
 * Configure cross-origin resource sharing.
 * WARNING: Wildcard (*) origin should be restricted in production.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

/**
 * DEPENDENCIES AND INITIALIZATION
 * ===============================
 * Load required classes and authentication utilities.
 */
require_once __DIR__ . '/../class/Provider.php';
require_once __DIR__ . '/auth.php'; // Add authentication/session validation

/**
 * REQUEST PROCESSING
 * ==================
 * Main logic for handling GET and POST requests.
 */
$method = $_SERVER['REQUEST_METHOD'];
$providerId = null;

/**
 * GET REQUEST HANDLER
 * ==================
 * Retrieve current status of a specific provider.
 */
if ($method === 'GET') {
    // Get provider status
    if (isset($_GET['provider_id'])) {
        $providerId = intval($_GET['provider_id']);
        $provider = new Provider();
        $result = $provider->getProviderStatus($providerId);
        echo json_encode($result);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'provider_id required']);
        exit;
    }
} 

/**
 * POST REQUEST HANDLER
 * ====================
 * Update provider status to active or inactive.
 * Supports both JSON and form-data input formats.
 */
elseif ($method === 'POST') {
    // Change provider status
    // Support both JSON and form-data POST
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    
    $providerId = isset($input['provider_id']) ? intval($input['provider_id']) : null;
    $newStatus = isset($input['new_status']) ? $input['new_status'] : null;
    
    if ($providerId && $newStatus) {
        $provider = new Provider();
        $result = $provider->changeProviderStatus($providerId, $newStatus);
        echo json_encode($result);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'provider_id and new_status required']);
        exit;
    }
} 

/**
 * UNSUPPORTED METHOD HANDLER
 * ==========================
 * Return error for unsupported HTTP methods.
 */
else {
    echo json_encode(['status' => 'error', 'message' => 'Unsupported request method']);
    exit;
}
