<?php
/**
 * admin_dashboard_lists.php
 *
 * API endpoint for admin dashboard recent activity lists.
 * Provides the latest customers, providers, service bookings, and subscription bookings.
 *
 * AUTHENTICATION:
 * ===============
 * Currently no authentication required - should be added for security.
 * TODO: Add JWT authentication and admin role verification.
 *
 * PURPOSE:
 * ========
 * Returns recent activity data for admin dashboard widgets:
 * - Latest 5 customers who registered
 * - Latest 5 providers who registered  
 * - Latest 5 service bookings created
 * - Latest 5 subscription bookings created
 *
 * RESPONSE FORMAT:
 * ===============
 * Success Response:
 * {
 *   "status": "success",
 *   "customers": [
 *     {
 *       "user_id": 123,
 *       "name": "John Doe"
 *     }
 *   ],
 *   "providers": [
 *     {
 *       "user_id": 456,
 *       "name": "ABC Services"
 *     }
 *   ],
 *   "serviceBookings": [
 *     {
 *       "service_book_id": 789,
 *       "customer_name": "Jane Smith",
 *       "service_date": "2024-01-15"
 *     }
 *   ],
 *   "subscriptionBookings": [
 *     {
 *       "subbook_id": 101,
 *       "customer_name": "Bob Johnson",
 *       "sub_date": "2024-01-14"
 *     }
 *   ]
 * }
 *
 * Error Response:
 * {
 *   "status": "error",
 *   "message": "Error description"
 * }
 *
 * BUSINESS LOGIC:
 * ===============
 * - Shows recent activity for quick admin overview
 * - Ordered by registration/booking date (most recent first)
 * - Limited to 5 items per category for dashboard widget display
 * - Provides quick access to new customers, providers, and bookings
 *
 * CORS CONFIGURATION:
 * ==================
 * Configured for frontend integration with localhost:5173.
 * Supports credentials for future authentication implementation.
 *
 * USAGE:
 * ======
 * GET /api/admin_dashboard_lists.php
 * Used by admin dashboard to populate recent activity widgets.
 *
 * DEPENDENCIES:
 * =============
 * - db.php: Database connection management
 */

// admin_dashboard_lists.php - Returns latest customers, providers, service bookings, and subscription bookings
/**
 * CORS HEADERS
 * ============
 * Configure cross-origin resource sharing for frontend integration.
 */
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');

/**
 * MAIN DATA RETRIEVAL
 * ===================
 * Fetch recent activity data for admin dashboard display.
 */
try {
    // Establish database connection
    $db = new DBConnector();
    $conn = $db->connect();
    
    /**
     * LATEST CUSTOMERS
     * Get 5 most recently registered customers.
     */
    $stmt1 = $conn->query("SELECT user_id, name FROM users WHERE user_type = 'customer' ORDER BY registered_date DESC LIMIT 5");
    $customers = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    /**
     * LATEST PROVIDERS
     * Get 5 most recently registered service providers.
     */
    $stmt2 = $conn->query("SELECT user_id, name FROM users WHERE user_type = 'provider' ORDER BY registered_date DESC LIMIT 5");
    $providers = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    /**
     * LATEST SERVICE BOOKINGS
     * Get 5 most recent service booking requests.
     */
    $stmt3 = $conn->query("SELECT service_book_id, customer_name, service_date FROM service_booking ORDER BY serbooking_date DESC LIMIT 5");
    $serviceBookings = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    /**
     * LATEST SUBSCRIPTION BOOKINGS
     * Get 5 most recent subscription booking requests.
     */
    $stmt4 = $conn->query("SELECT subbook_id, customer_name, sub_date FROM subscription_booking ORDER BY subbooking_date DESC LIMIT 5");
    $subscriptionBookings = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    
    /**
     * SUCCESS RESPONSE
     * Return all recent activity data for dashboard widgets.
     */
    echo json_encode([
        'status' => 'success',
        'customers' => $customers,
        'providers' => $providers,
        'serviceBookings' => $serviceBookings,
        'subscriptionBookings' => $subscriptionBookings
    ]);
} catch (Exception $e) {
    /**
     * ERROR HANDLING
     * Return structured error response for any database or processing errors.
     */
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
