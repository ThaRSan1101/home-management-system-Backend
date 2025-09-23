<?php
/**
 * provider_dashboard.php
 *
 * API endpoint to fetch comprehensive provider dashboard statistics.
 * Provides overview metrics for provider's business activities.
 *
 * AUTHENTICATION:
 * ===============
 * - Requires valid JWT authentication via cookie
 * - Provider role verification enforced
 * - Only the authenticated provider can access their own dashboard
 *
 * STATISTICS PROVIDED:
 * ===================
 * - bookings: Total pending booking requests (service + subscription with status 'waiting')
 * - subscriptions: Total active subscriptions (status 'process' or 'cancel')  
 * - services: Total service bookings across all statuses (process, cancel, request, complete)
 * - feedback: Total customer feedback/reviews received (from both service and subscription reviews)
 *
 * RESPONSE FORMAT:
 * ===============
 * Success Response:
 * {
 *   "status": "success",
 *   "data": {
 *     "bookings": number,        // Pending booking requests
 *     "subscriptions": number,   // Active subscriptions  
 *     "services": number,        // Total services handled
 *     "feedback": number         // Total reviews received
 *   }
 * }
 *
 * Error Response:
 * {
 *   "status": "error",
 *   "message": "Error description"
 * }
 *
 * HTTP STATUS CODES:
 * ==================
 * - 200: Success with dashboard data
 * - 403: Access denied (not a provider)
 * - 404: Provider record not found
 * - 500: Server error
 *
 * CORS CONFIGURATION:
 * ==================
 * Configured for frontend integration with localhost:5173.
 * Supports credentials for authentication cookies.
 * Handles preflight OPTIONS requests.
 *
 * BUSINESS LOGIC:
 * ===============
 * The dashboard stats are calculated by:
 * 1. Looking up provider_id from provider table using authenticated user_id
 * 2. Calling Provider::getDashboardStats() which queries multiple tables
 * 3. Aggregating counts from service_booking, subscription_booking, and review tables
 * 4. Returning formatted statistics for frontend dashboard display
 *
 * DEPENDENCIES:
 * =============
 * - auth.php: JWT authentication and role verification
 * - Provider.php: Business logic for dashboard statistics calculation
 * - db.php: Database connection management
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../class/Provider.php';
require_once __DIR__ . '/db.php';

/**
 * CORS HEADERS AND PREFLIGHT HANDLING
 * ===================================
 * Configure cross-origin resource sharing for frontend integration.
 * Handle preflight OPTIONS requests for complex CORS scenarios.
 */
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

/**
 * MAIN DASHBOARD PROCESSING
 * =========================
 * 1. Authenticate user and verify provider role
 * 2. Resolve provider_id from database
 * 3. Calculate dashboard statistics
 * 4. Return formatted response
 */
try {
	/**
	 * STEP 1: AUTHENTICATION AND AUTHORIZATION
	 * Verify JWT token and ensure user has provider role.
	 */
	$user = require_auth();
	if ($user['user_type'] !== 'provider') {
		http_response_code(403);
		echo json_encode(['status' => 'error', 'message' => 'Access denied. Providers only.']);
		exit();
	}

	/**
	 * STEP 2: DATABASE CONNECTION AND PROVIDER RESOLUTION
	 * Connect to database and resolve provider_id from user_id.
	 */
	$db = (new DBConnector())->connect();
	$provider = new Provider($db);

	// Resolve provider_id from provider table using authenticated user_id
	$stmt = $db->prepare('SELECT provider_id FROM provider WHERE user_id = ?');
	$stmt->execute([$user['user_id']]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$row) {
		http_response_code(404);
		echo json_encode(['status' => 'error', 'message' => 'Provider not found.']);
		exit();
	}

	/**
	 * STEP 3: STATISTICS CALCULATION
	 * Calculate comprehensive dashboard statistics using Provider class.
	 */
	$providerId = (int)$row['provider_id'];
	$result = $provider->getDashboardStats($providerId);
	if (($result['status'] ?? '') !== 'success') {
		http_response_code(500);
		echo json_encode($result);
		exit();
	}

	/**
	 * STEP 4: SUCCESS RESPONSE
	 * Return dashboard statistics to frontend.
	 */
	echo json_encode($result);
} catch (Exception $e) {
	/**
	 * ERROR HANDLING
	 * Catch any unexpected errors and return structured error response.
	 */
	http_response_code(500);
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}


