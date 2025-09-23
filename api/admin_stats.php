<?php
/**
 * admin_stats.php
 *
 * API endpoint for admin dashboard overview statistics.
 * Provides key metrics for system administrators to monitor platform activity.
 *
 * AUTHENTICATION:
 * ===============
 * Currently no authentication required - this may need to be added for security.
 * TODO: Add JWT authentication and admin role verification.
 *
 * STATISTICS PROVIDED:
 * ===================
 * - customers: Total count of registered customers
 * - providers: Total count of registered service providers
 * - totalBookings: Combined count of service and subscription bookings
 * - completedBookings: Combined count of completed service and subscription bookings
 *
 * RESPONSE FORMAT:
 * ===============
 * Success Response:
 * {
 *   "status": "success",
 *   "customers": number,           // Total customer count
 *   "providers": number,           // Total provider count
 *   "totalBookings": number,       // Total bookings (service + subscription)
 *   "completedBookings": number    // Total completed bookings
 * }
 *
 * Error Response:
 * {
 *   "status": "error",
 *   "message": "Error description"
 * }
 *
 * DATABASE QUERIES:
 * ================
 * 1. Count users where user_type = 'customer'
 * 2. Count users where user_type = 'provider'
 * 3. Count all records in service_booking table
 * 4. Count all records in subscription_booking table
 * 5. Count service_booking where serbooking_status = 'complete'
 * 6. Count subscription_booking where subbooking_status = 'complete'
 *
 * CORS CONFIGURATION:
 * ==================
 * Configured for frontend integration with localhost:5173.
 * Supports credentials for potential future authentication.
 *
 * USAGE:
 * ======
 * GET /api/admin_stats.php
 * Returns JSON with platform statistics for admin dashboard.
 *
 * DEPENDENCIES:
 * =============
 * - db.php: Database connection management
 */

// admin_stats.php - Returns counts for admin dashboard overview
/**
 * CORS HEADERS
 * ============
 * Configure cross-origin resource sharing for frontend integration.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');

/**
 * MAIN STATISTICS CALCULATION
 * ===========================
 * Query database for various platform metrics and return formatted response.
 */
try {
    // Establish database connection
    $db = new DBConnector();
    $conn = $db->connect();
    
    /**
     * CUSTOMER COUNT
     * Count all registered customers in the system.
     */
    $stmt1 = $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'customer'");
    $customerCount = $stmt1->fetchColumn();
    
    /**
     * PROVIDER COUNT
     * Count all registered service providers in the system.
     */
    $stmt2 = $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'provider'");
    $providerCount = $stmt2->fetchColumn();
    
    /**
     * TOTAL BOOKINGS CALCULATION
     * Combine service bookings and subscription bookings for total platform activity.
     */
    $stmt3 = $conn->query("SELECT COUNT(*) FROM service_booking");
    $serviceBookings = $stmt3->fetchColumn();
    $stmt4 = $conn->query("SELECT COUNT(*) FROM subscription_booking");
    $subscriptionBookings = $stmt4->fetchColumn();
    $totalBookings = $serviceBookings + $subscriptionBookings;
    
    /**
     * COMPLETED BOOKINGS CALCULATION
     * Count successfully completed services for platform success metrics.
     */
    $stmt5 = $conn->query("SELECT COUNT(*) FROM service_booking WHERE serbooking_status = 'complete'");
    $completedService = $stmt5->fetchColumn();
    $stmt6 = $conn->query("SELECT COUNT(*) FROM subscription_booking WHERE subbooking_status = 'complete'");
    $completedSubscription = $stmt6->fetchColumn();
    $completedBookings = $completedService + $completedSubscription;
    
    /**
     * SUCCESS RESPONSE
     * Return formatted statistics for admin dashboard display.
     */
    echo json_encode([
        'status' => 'success',
        'customers' => (int)$customerCount,
        'providers' => (int)$providerCount,
        'totalBookings' => (int)$totalBookings,
        'completedBookings' => (int)$completedBookings
    ]);
} catch (Exception $e) {
    /**
     * ERROR HANDLING
     * Return structured error response for any database or processing errors.
     */
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
