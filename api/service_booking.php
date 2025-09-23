<?php
/**
 * service_booking.php
 *
 * API endpoint for comprehensive service booking management in the Home Management System.
 * Handles creation, retrieval, updates, and cancellation of service bookings.
 *
 * AUTHENTICATION:
 * ===============
 * All requests require valid JWT authentication via cookie.
 * Authentication is enforced using require_auth() from auth.php.
 *
 * HTTP METHODS SUPPORTED:
 * =======================
 * 
 * GET: Retrieve service bookings
 * - Returns list of service bookings based on user role and filtering
 * - Supports query parameters for filtering (status, user_id, provider_id, etc.)
 *
 * POST: Create new bookings and perform booking actions
 * - Create new service booking (customer action)
 * - Move booking to provider (admin action)
 * - Accept booking (provider action) 
 * - Decline booking (provider action)
 * - Admin assignment of bookings to providers
 *
 * PATCH: Update existing bookings
 * - Provider complete booking (mark as complete and set final amount)
 * - Customer accept booking (accept completed booking)
 * - Cancel booking (customer/admin action)
 * - Provider cancel booking (provider cancels assigned booking)
 *
 * POST ACTIONS:
 * =============
 * - action=move: Admin moves pending booking to specific provider
 * - action=accept: Provider accepts assigned booking (status: waiting -> process)
 * - action=decline: Provider declines assigned booking
 * - action=admin_assign: Admin directly assigns booking to provider
 * - (no action): Create new service booking from customer
 *
 * PATCH ACTIONS:
 * ==============
 * - action=provider_complete: Provider marks booking complete with final amount
 * - action=customer_accept: Customer accepts completed booking
 * - action=cancel: General cancellation with reason
 * - action=provider_cancel: Provider cancels their assigned booking
 *
 * REQUIRED PARAMETERS:
 * ===================
 * 
 * For booking creation (POST without action):
 * - service_category_id, customer_name, service_date, service_time
 * - service_address, phoneNo, amount
 *
 * For move action (POST):
 * - service_book_id, provider_id
 *
 * For accept/decline actions (POST):
 * - service_book_id, provider_id
 *
 * For completion (PATCH):
 * - service_book_id, service_amount
 *
 * For cancellation (PATCH):
 * - service_book_id, cancel_reason
 * - For provider_cancel: also requires provider_id
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
 * Configured for frontend integration with localhost:5173 and 127.0.0.1:5173.
 * Supports credentials for authentication cookies.
 *
 * DEPENDENCIES:
 * =============
 * - auth.php: JWT authentication and authorization
 * - ServiceBooking.php: Business logic for booking operations
 */

// --- CORS HEADERS START ---
$allowed_origins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
// --- CORS HEADERS END ---

/**
 * MAIN REQUEST PROCESSING
 * =======================
 * Below is the main logic that processes different HTTP methods and actions.
 * Each section is clearly documented with its purpose and requirements.
 */

// API endpoint for service booking creation, retrieval, and cancellation
// Accepts customer_name in POST data and passes to ServiceBooking::serviceBook

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../class/ServiceBooking.php';

// Require authentication for all requests
$user = require_auth();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$serviceBooking = new ServiceBooking();

/**
 * PATCH REQUEST HANDLING
 * ======================
 * PATCH requests are used for updating existing service bookings.
 * All PATCH operations require specific action parameters.
 */
if ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    /**
     * PROVIDER COMPLETE BOOKING
     * Provider marks a booking as complete and sets the final service amount.
     * This transitions the booking from 'process' to 'complete' status.
     * 
     * Required: service_book_id, service_amount
     * Triggers: Notification to customer about completion
     */
    if (isset($input['action']) && $input['action'] === 'provider_complete') {
        if (!isset($input['service_book_id'], $input['service_amount'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing booking ID or service amount.']);
            exit;
        }
        $result = $serviceBooking->providerCompleteBooking($input['service_book_id'], $input['service_amount']);
        echo json_encode($result);
        exit;
    }
    
    /**
     * CUSTOMER ACCEPT BOOKING
     * Customer accepts a completed booking, confirming satisfaction with the service.
     * This finalizes the booking process.
     * 
     * Required: service_book_id
     * Triggers: Final completion notifications
     */
    if (isset($input['action']) && $input['action'] === 'customer_accept') {
        if (!isset($input['service_book_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing booking ID.']);
            exit;
        }
        $result = $serviceBooking->customerAcceptBooking($input['service_book_id']);
        echo json_encode($result);
        exit;
    }
    
    /**
     * GENERAL BOOKING CANCELLATION
     * Cancel a booking with a reason. Can be used by customers or admins.
     * 
     * Required: service_book_id, cancel_reason
     * Triggers: Cancellation notifications to relevant parties
     */
    if (isset($input['action']) && $input['action'] === 'cancel') {
        if (!isset($input['service_book_id']) || !isset($input['cancel_reason'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing booking ID or cancel reason.']);
            exit;
        }
        $result = $serviceBooking->cancelBooking($input['service_book_id'], $input['cancel_reason']);
        echo json_encode($result);
        exit;
    }
    
    /**
     * PROVIDER CANCELLATION
     * Provider cancels a booking they have been assigned to.
     * This is different from general cancellation as it requires provider verification.
     * 
     * Required: service_book_id, provider_id, cancel_reason
     * Triggers: Notifications to customer, admin, and provider about cancellation
     */
    if (isset($input['action']) && $input['action'] === 'provider_cancel') {
        if (!isset($input['service_book_id'], $input['provider_id'], $input['cancel_reason'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing booking ID, provider ID, or cancel reason.']);
            exit;
        }
        $result = $serviceBooking->cancelBookingByProvider($input['service_book_id'], $input['provider_id'], $input['cancel_reason']);
        echo json_encode($result);
        exit;
    }
}

/**
 * POST REQUEST HANDLING
 * =====================
 * POST requests handle booking creation and various booking management actions.
 * Actions are determined by the 'action' parameter in the JSON payload.
 */
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing JSON payload.']);
        exit;
    }
    
    /**
     * MOVE BOOKING TO PROVIDER
     * Admin action to assign a pending booking to a specific provider.
     * Changes booking status and creates allocation record.
     * 
     * Required: service_book_id, provider_id
     * Authority: Admin only
     * Triggers: Notification to provider about new assignment
     */
    // Move booking to provider
    if (isset($input['action']) && $input['action'] === 'move') {
        if (!isset($input['service_book_id'], $input['provider_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing booking or provider ID.']);
            exit;
        }
        $result = $serviceBooking->moveBooking($input['service_book_id'], $input['provider_id']);
        echo json_encode($result);
        exit;
    }
    
    /**
     * PROVIDER ACCEPTS BOOKING
     * Provider accepts a booking that has been assigned to them.
     * Changes booking status from 'waiting' to 'process'.
     * 
     * Required: service_book_id, provider_id
     * Authority: Provider only (must be assigned to this booking)
     * Triggers: Notification to customer about acceptance
     */
    // Provider accepts booking
    if (isset($input['action']) && $input['action'] === 'accept') {
        if (!isset($input['service_book_id'], $input['provider_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing booking or provider ID.']);
            exit;
        }
        $result = $serviceBooking->acceptBooking($input['service_book_id'], $input['provider_id']);
        echo json_encode($result);
        exit;
    }
    
    /**
     * PROVIDER DECLINES BOOKING
     * Provider declines a booking that has been assigned to them.
     * Returns booking to pending status for reassignment.
     * 
     * Required: service_book_id, provider_id
     * Authority: Provider only (must be assigned to this booking)
     * Triggers: Notification to admin about declined booking
     */
    // Provider declines booking
    if (isset($input['action']) && $input['action'] === 'decline') {
        if (!isset($input['service_book_id'], $input['provider_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing booking or provider ID.']);
            exit;
        }
        $result = $serviceBooking->declineBooking($input['service_book_id'], $input['provider_id']);
        echo json_encode($result);
        exit;
    }
    // Default: Create a new booking
    if (!isset($input['user_id'])) {
        $input['user_id'] = $user['user_id'];
    }
    $result = $serviceBooking->serviceBook($input);
    echo json_encode($result);
    exit;
}

// Provider dashboard: fetch waiting requests
if ($method === 'GET' && isset($_GET['provider_requests']) && isset($_GET['provider_id'])) {
    $provider_id = (int)$_GET['provider_id'];
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $result = $serviceBooking->getProviderRequests($provider_id, $status);
    echo json_encode($result);
    exit;
}


if ($method === 'GET') {
    // Retrieve bookings (optionally filtered by user_id and/or status)
    $filters = [];
    // Use user_id from JWT if not provided in query
    if (isset($_GET['user_id'])) {
        $filters['user_id'] = (int)$_GET['user_id'];
    } else if ($user && $user['user_type'] === 'customer') {
        $filters['user_id'] = $user['user_id'];
    }
    if (isset($_GET['provider_id'])) {
        $filters['provider_id'] = (int)$_GET['provider_id'];
    }
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    if ($status === 'process' || $status === 'cancel') {
        $filters['status'] = $status;
        $result = $serviceBooking->getAdminBookings($filters, $page, $limit);
        echo json_encode($result);
        exit;
    } else {
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        $result = $serviceBooking->getServiceBooking($filters, $page, $limit);
        echo json_encode($result);
        exit;
    }
}

// If not POST, GET, or PATCH
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed. Only GET, POST, and PATCH methods are supported.']);