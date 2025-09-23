<?php
/**
 * subscription_booking.php
 *
 * API endpoint for comprehensive subscription booking management in the Home Management System.
 * Handles creation, retrieval, updates, and cancellation of subscription bookings.
 *
 * AUTHENTICATION:
 * ===============
 * All requests require valid JWT authentication via cookie.
 * Authentication is enforced using require_auth() from auth.php.
 *
 * HTTP METHODS SUPPORTED:
 * =======================
 * 
 * GET: Retrieve subscription bookings
 * - Returns list of subscription bookings based on user role and filtering
 * - Supports query parameters for filtering (status, user_id, provider_id, etc.)
 *
 * POST: Create new bookings and perform booking actions
 * - Create new subscription booking (customer action)
 * - Move booking to provider (admin action)
 * - Accept booking (provider action) 
 * - Decline booking (provider action)
 *
 * PATCH: Update existing bookings
 * - Cancel booking (customer/admin action)
 * - Assign provider to booking (admin action)
 *
 * POST ACTIONS:
 * =============
 * - action=move: Admin moves pending booking to specific provider
 * - action=accept: Provider accepts assigned booking (status: waiting -> process)
 * - action=decline: Provider declines assigned booking
 * - (no action): Create new subscription booking from customer
 *
 * PATCH ACTIONS:
 * ==============
 * - action=cancel: Cancel subscription with reason
 * - action=assign_provider: Admin assigns provider to subscription booking
 *
 * REQUIRED PARAMETERS:
 * ===================
 * 
 * For booking creation (POST without action):
 * - sub_id, customer_name, sub_date, sub_time
 * - sub_address, phoneNo, amount
 *
 * For move action (POST):
 * - subbook_id, provider_id
 *
 * For accept/decline actions (POST):
 * - subbook_id, provider_id
 *
 * For cancellation (PATCH):
 * - subbook_id (or booking_id), cancel_reason (or reason)
 *
 * For provider assignment (PATCH):
 * - subbook_id, provider_id
 *
 * RESPONSE FORMAT:
 * ================
 * All responses are JSON with standard format:
 * {
 *   "status": "success|error",
 *   "message": "Description of result",
 *   "data": { ... } // Optional, for GET requests
 * }
 *
 * CORS CONFIGURATION:
 * ===================
 * Configured for frontend integration with localhost:5173.
 * Supports credentials for authentication cookies.
 *
 * DEPENDENCIES:
 * =============
 * - auth.php: JWT authentication and authorization
 * - SubscriptionBooking.php: Business logic for subscription booking operations
 */

// subscription_booking.php
// API endpoint for subscription booking creation, retrieval, and cancellation
// Accepts POST, GET, PATCH requests

/**
 * CORS HEADERS CONFIGURATION
 * ==========================
 * Configure cross-origin resource sharing for frontend integration.
 */
$allowed_origins = [
    'http://localhost:5173',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/**
 * AUTHENTICATION AND INITIALIZATION
 * =================================
 * Require authentication and initialize necessary components.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../class/SubscriptionBooking.php';

$user = require_auth();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$subscriptionBooking = new SubscriptionBooking();

/**
 * PATCH REQUEST HANDLING
 * ======================
 * PATCH requests are used for updating existing subscription bookings.
 */
if ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    /**
     * SUBSCRIPTION CANCELLATION
     * Cancel a subscription booking with reason.
     * Supports both subbook_id/booking_id and cancel_reason/reason field variations.
     */
    if (isset($input['action']) && $input['action'] === 'cancel') {
        // Accept both frontend field sets
        $bookingId = $input['subbook_id'] ?? $input['booking_id'] ?? null;
        $cancelReason = $input['cancel_reason'] ?? $input['reason'] ?? null;
        if (!$bookingId || !$cancelReason) {
            echo json_encode(['status' => 'error', 'message' => 'Missing booking ID or cancel reason.']);
            exit;
        }
        $result = $subscriptionBooking->cancelBooking($bookingId, $cancelReason);
        echo json_encode($result);
        exit;
    } 
    
    /**
     * PROVIDER ASSIGNMENT
     * Admin assigns a provider to a subscription booking.
     */
    elseif (isset($input['action']) && $input['action'] === 'assign_provider') {
        // Assign provider to subscription booking
        $bookingId = $input['subbook_id'] ?? null;
        $providerId = $input['provider_id'] ?? null;
        if (!$bookingId || !$providerId) {
            echo json_encode(['status' => 'error', 'message' => 'Missing booking ID or provider ID.']);
            exit;
        }
        $result = $subscriptionBooking->assignProvider($bookingId, $providerId);
        echo json_encode($result);
        exit;
    }
}

/**
 * POST REQUEST HANDLING
 * =====================
 * POST requests handle subscription booking creation and management actions.
 */
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing JSON payload.']);
        exit;
    }
    
    /**
     * MOVE BOOKING TO PROVIDER
     * Admin action to assign a pending subscription to a specific provider.
     */
    // Move booking to provider
    if (isset($input['action']) && $input['action'] === 'move') {
        if (!isset($input['subbook_id'], $input['provider_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing booking or provider ID.']);
            exit;
        }
        $result = $subscriptionBooking->moveBooking($input['subbook_id'], $input['provider_id']);
        echo json_encode($result);
        exit;
    }
    
    // Provider accepts booking
    if (isset($input['action']) && $input['action'] === 'accept') {
        if (!isset($input['subbook_id'], $input['provider_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing booking or provider ID.']);
            exit;
        }
        $result = $subscriptionBooking->acceptBooking($input['subbook_id'], $input['provider_id']);
        echo json_encode($result);
        exit;
    }
    
    // Provider declines booking
    if (isset($input['action']) && $input['action'] === 'decline') {
        if (!isset($input['subbook_id'], $input['provider_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing booking or provider ID.']);
            exit;
        }
        $result = $subscriptionBooking->declineBooking($input['subbook_id'], $input['provider_id']);
        echo json_encode($result);
        exit;
    }
    
    // Default: Create a new booking
    if (!isset($input['user_id'])) {
        $input['user_id'] = $user['user_id'];
    }
    $result = $subscriptionBooking->subscriptionBook($input);
    echo json_encode($result);
    exit;
}

// Provider dashboard: fetch waiting requests
if ($method === 'GET' && isset($_GET['provider_requests']) && isset($_GET['provider_id'])) {
    $provider_id = (int)$_GET['provider_id'];
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $result = $subscriptionBooking->getProviderRequests($provider_id, $status);
    echo json_encode($result);
    exit;
}

if ($method === 'GET') {
    $filters = [];
    if (isset($_GET['user_id'])) {
        $filters['user_id'] = (int)$_GET['user_id'];
    } else if ($user && $user['user_type'] === 'customer') {
        $filters['user_id'] = $user['user_id'];
    }
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    if ($status) {
        $filters['status'] = $status;
    }
    $result = $subscriptionBooking->getSubscriptionBooking($filters, $page, $limit);
    echo json_encode($result);
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);