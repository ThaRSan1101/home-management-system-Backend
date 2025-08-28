<?php
/**
 * customer_dashboard.php
 *
 * API endpoint for fetching customer dashboard statistics.
 *
 * Required GET parameters:
 * - user_id: The ID of the customer
 *
 * Returns JSON with the following structure:
 * {
 *   "status": "success",
 *   "data": {
 *     "upcoming_bookings": number,
 *     "active_subscriptions": number,
 *     "feedback_given": number,
 *     "total_services_used": number
 *   }
 * }
 */

// Set CORS headers
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/auth.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Require JWT authentication
$user = require_auth();

// Ensure user is a customer
if ($user['user_type'] !== 'customer') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Customers only.']);
    exit();
}

$user_id = (int)$user['user_id'];

try {
    // Get database connection
    $db = new PDO('mysql:host=localhost;dbname=ServiceHub', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Count upcoming bookings (status = 'pending' or 'waiting')
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM service_booking 
        WHERE user_id = :user_id 
        AND serbooking_status IN ('pending', 'waiting')
    ");
    $stmt->execute(['user_id' => $user_id]);
    $upcoming_bookings = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 2. Count active subscriptions (status = 'process' or 'pending')
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM subscription_booking 
        WHERE user_id = :user_id 
        AND subbooking_status IN ('process', 'pending')
    ");
    $stmt->execute(['user_id' => $user_id]);
    $active_subscriptions = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 3. Count feedback given (reviews in both service and subscription reviews)
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM service_review sr 
             JOIN service_provider_allocation spa ON sr.allocation_id = spa.allocation_id
             JOIN service_booking sb ON spa.service_book_id = sb.service_book_id
             WHERE sb.user_id = :user_id)
            +
            (SELECT COUNT(*) FROM subscription_review sr 
             JOIN subscription_provider_allocation spa ON sr.allocation_id = spa.allocation_id
             JOIN subscription_booking sb ON spa.subbook_id = sb.subbook_id
             WHERE sb.user_id = :user_id)
        as total_feedback
    ");
    $stmt->execute(['user_id' => $user_id]);
    $feedback_given = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_feedback'];

    // 4. Count total services used (completed service and subscription bookings)
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM service_booking 
             WHERE user_id = :user_id AND serbooking_status = 'complete')
            +
            (SELECT COUNT(*) FROM subscription_booking 
             WHERE user_id = :user_id AND subbooking_status = 'complete')
        as total_services_used
    ");
    $stmt->execute(['user_id' => $user_id]);
    $total_services_used = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_services_used'];

    // Return the statistics
    echo json_encode([
        'status' => 'success',
        'data' => [
            'upcoming_bookings' => $upcoming_bookings,
            'active_subscriptions' => $active_subscriptions,
            'feedback_given' => $feedback_given,
            'total_services_used' => $total_services_used
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
